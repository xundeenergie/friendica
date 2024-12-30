<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Console;

use Friendica\App\Mode;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Asika\SimpleConsole\Console;
use Friendica\Core\KeyValueStorage\Capability\IManageKeyValuePairs;
use Friendica\Core\System;
use Friendica\Core\Update;
use Friendica\Core\Worker;
use Friendica\Database\Database;
use Friendica\Util\BasePath;
use Friendica\Util\DateTimeFormat;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Console command for starting
 */
final class Daemon extends Console
{
	private Mode $mode;
	private IManageConfigValues $config;
	private IManageKeyValuePairs $keyValue;
	private BasePath $basePath;
	private System $system;
	private LoggerInterface $logger;
	private Database $dba;

	/**
	 * @param Mode                 $mode
	 * @param IManageConfigValues  $config
	 * @param IManageKeyValuePairs $keyValue
	 * @param BasePath             $basePath
	 * @param System               $system
	 * @param LoggerInterface      $logger
	 * @param Database             $dba
	 * @param array|null           $argv
	 */
	public function __construct(Mode $mode, IManageConfigValues $config, IManageKeyValuePairs $keyValue, BasePath $basePath, System $system, LoggerInterface $logger, Database $dba, array $argv = null)
	{
		parent::__construct($argv);

		$this->mode     = $mode;
		$this->config   = $config;
		$this->keyValue = $keyValue;
		$this->basePath = $basePath;
		$this->system   = $system;
		$this->logger   = $logger;
		$this->dba       = $dba;
	}

	protected function getHelp(): string
	{
		return <<<HELP
Daemon - Interacting with the Friendica daemons
Synopsis
	bin/console daemon [-h|--help|-?] [-v] [-a] [-f]

Description
    Interacting with the Friendica daemons

Options
    -h|--help|-?            Show help information
    -v                      Show more debug information.
    -f|--foreground         Runs the daemon in the forgeground

Examples
	bin/console daemon start -f
		Starts the daemon in the foreground

	bin/console daemon status
		Gets the status of the daemon
HELP;
	}

	protected function doExecute()
	{
		if ($this->mode->isInstall()) {
			throw new RuntimeException("Friendica isn't properly installed yet");
		}

		$this->mode->setExecutor(Mode::DAEMON);

		$this->config->reload();

		if (empty($this->config->get('system', 'pidfile'))) {
			throw new RuntimeException(<<< TXT
					Please set system.pidfile in config/local.config.php. For example:

						'system' => [
							'pidfile' => '/path/to/daemon.pid',
						],
					TXT);
		}

		$pidfile = $this->config->get('system', 'pidfile');

		$daemonMode = $this->getArgument(0);
		$foreground = $this->getOption(['f', 'foreground']);

		if (empty($daemonMode)) {
			throw new RuntimeException("Please use either 'start', 'stop' or 'status'");
		}

		$pid = null;
		if (is_readable($pidfile)) {
			$pid = intval(file_get_contents($pidfile));
		}

		if (empty($pid) && in_array($daemonMode, ['stop', 'status'])) {
			$this->keyValue->set('worker_daemon_mode', false);
			throw new RuntimeException("Pidfile wasn't found. Is the daemon running?");
		}

		if ($daemonMode == 'status') {
			if (posix_kill($pid, 0)) {
				$this->out("Daemon process $pid is running");
				return 0;
			}

			unlink($pidfile);

			$this->keyValue->set('worker_daemon_mode', false);
			$this->out("Daemon process $pid isn't running.");
			return 0;
		}

		if ($daemonMode == 'stop') {
			posix_kill($pid, SIGTERM);
			unlink($pidfile);

			$this->logger->notice('Worker daemon process was killed', ['pid' => $pid]);

			$this->keyValue->set('worker_daemon_mode', false);
			$this->out("Daemon process $pid was killed.");
			return 0;
		}

		$this->logger->notice('Starting worker daemon', ['pid' => $pid]);

		if (!$foreground) {
			$this->out("Starting worker daemon");
			$this->dba->disconnect();

			// Fork a daemon process
			$pid = pcntl_fork();
			if ($pid == -1) {
				$this->logger->warning('Could not fork daemon');
				throw new RuntimeException("Daemon couldn't be forked");
			} elseif ($pid) {
				// The parent process continues here
				if (!file_put_contents($pidfile, $pid)) {
					posix_kill($pid, SIGTERM);
					$this->logger->warning('Could not store pid file');
					throw new RuntimeException("Pid file wasn't written");
				}
				$this->out("Child process started with pid $pid");
				$this->logger->notice('Child process started', ['pid' => $pid]);
				return 0;
			}

			// We now are in the child process
			register_shutdown_function(function () {
				posix_kill(posix_getpid(), SIGTERM);
				posix_kill(posix_getpid(), SIGHUP);
			});

			// Make the child the main process, detach it from the terminal
			if (posix_setsid() < 0) {
				return 0;
			}

			// Closing all existing connections with the outside
			fclose(STDIN);

			// And now connect the database again
			$this->dba->connect();
		}

		$this->keyValue->set('worker_daemon_mode', true);

		// Just to be sure that this script really runs endlessly
		set_time_limit(0);

		$wait_interval = intval($this->config->get('system', 'cron_interval', 5)) * 60;

		$do_cron = true;
		$last_cron = 0;

		$path = $this->basePath->getPath();

		// Now running as a daemon.
		while (true) {
			// Check the database structure and possibly fixes it
			Update::check($path, true);

			if (!$do_cron && ($last_cron + $wait_interval) < time()) {
				$this->logger->info('Forcing cron worker call.', ['pid' => $pid]);
				$do_cron = true;
			}

			if ($do_cron || (!$this->system->isMaxLoadReached() && Worker::entriesExists() && Worker::isReady())) {
				Worker::spawnWorker($do_cron);
			} else {
				$this->logger->info('Cool down for 5 seconds', ['pid' => $pid]);
				sleep(5);
			}

			if ($do_cron) {
				// We force a reconnect of the database connection.
				// This is done to ensure that the connection don't get lost over time.
				$this->dba->reconnect();

				$last_cron = time();
			}

			$start = time();
			$this->logger->info('Sleeping', ['pid' => $pid, 'until' => gmdate(DateTimeFormat::MYSQL, $start + $wait_interval)]);

			do {
				$seconds = (time() - $start);

				// logarithmic wait time calculation.
				// Background: After jobs had been started, they often fork many workers.
				// To not waste too much time, the sleep period increases.
				$arg = (($seconds + 1) / ($wait_interval / 9)) + 1;
				$sleep = min(1000000, round(log10($arg) * 1000000, 0));
				usleep((int)$sleep);

				$pid = pcntl_waitpid(-1, $status, WNOHANG);
				if ($pid > 0) {
					$this->logger->info('Children quit via pcntl_waitpid', ['pid' => $pid, 'status' => $status]);
				}

				$timeout = ($seconds >= $wait_interval);
			} while (!$timeout && !Worker\IPC::JobsExists());

			if ($timeout) {
				$do_cron = true;
				$this->logger->info('Woke up after $wait_interval seconds.', ['pid' => $pid, 'sleep' => $wait_interval]);
			} else {
				$do_cron = false;
				$this->logger->info('Worker jobs are calling to be forked.', ['pid' => $pid]);
			}
		}
	}
}
