<?php

namespace Friendica\System;

use Friendica\Database\Database;
use Psr\Log\LoggerInterface;

final class Daemon
{
	private LoggerInterface $logger;
	private Database $dba;
	private ?string $pidfile = null;
	private ?int $pid = null;

	public function getPid(): ?int
	{
		return $this->pid;
	}

	public function getPidfile(): ?string
	{
		return $this->pidfile;
	}

	public function __construct(LoggerInterface $logger, Database $dba)
	{
		$this->logger = $logger;
		$this->dba    = $dba;
	}

	public function init($pidfile = null): void
	{
		if (!empty($pidfile)) {
			$this->pid     = null;
			$this->pidfile = $pidfile;
		}

		if (!empty($this->pid)) {
			return;
		}

		if (is_readable($this->pidfile)) {
			$this->pid = intval(file_get_contents($this->pidfile));
		}
	}

	public function start(callable $daemonLogic, bool $foreground = false): bool
	{
		$this->init();

		if (!empty($this->pid)) {
			$this->logger->notice('process is already running', ['pid' => $this->pid, 'pidfile' => $this->pidfile]);
			return false;
		}

		$this->logger->notice('starting daemon', ['pid' => $this->pid, 'pidfile' => $this->pidfile]);

		if (!$foreground) {
			$this->dba->disconnect();

			// fork a daemon process
			$this->pid = pcntl_fork();
			if ($this->pid < 0) {
				$this->logger->warning('Could not fork daemon');
				return false;
			} elseif ($this->pid) {
				// The parent process continues here
				if (!file_put_contents($this->pidfile, $this->pid)) {
					$this->logger->warning('Could not store pid file', ['pid' => $this->pid, 'pidfile' => $this->pidfile]);
					posix_kill($this->pid, SIGTERM);
					return false;
				}
				$this->logger->notice('Child process started', ['pid' => $this->pid, 'pidfile' => $this->pidfile]);
				return true;
			}

			// We now are in the child process
			register_shutdown_function(function (): void {
				posix_kill(posix_getpid(), SIGTERM);
				posix_kill(posix_getpid(), SIGHUP);
			});

			// Make the child the main process, detach it from the terminal
			if (posix_setsid() < 0) {
				return true;
			}

			// Closing all existing connections with the outside
			fclose(STDIN);

			// And now connect the database again
			$this->dba->connect();
		}

		// Just to be sure that this script really runs endlessly
		set_time_limit(0);

		$daemonLogic();

		return true;
	}

	public function isRunning(): bool
	{
		$this->init();

		if (empty($this->pid)) {
			$this->logger->notice("Pid wasn't found");

			if (is_readable($this->pidfile)) {
				unlink($this->pidfile);
				$this->logger->notice("Pidfile removed", ['pidfile' => $this->pidfile]);
			}
			return false;
		}

		if (posix_kill($this->pid, 0)) {
			$this->logger->notice("daemon process is running");
			return true;
		} else {
			unlink($this->pidfile);
			$this->logger->notice("daemon process isn't running");
			return false;
		}
	}

	public function stop(): bool
	{
		$this->init();

		if (empty($this->pid)) {
			$this->logger->notice("Pidfile wasn't found", ['pidfile' => $this->pidfile]);
			return true;
		}

		posix_kill($this->pid, SIGTERM);
		unlink($this->pidfile);

		$this->logger->notice('daemon process was killed', ['pid' => $this->pid, 'pidfile' => $this->pidfile]);

		return true;
	}

	public function sleep(int $duration)
	{
		usleep($duration);

		$this->pid = pcntl_waitpid(-1, $status, WNOHANG);
		if ($this->pid > 0) {
			$this->logger->info('Children quit via pcntl_waitpid', ['pid' => $this->pid, 'status' => $status]);
		}
	}
}
