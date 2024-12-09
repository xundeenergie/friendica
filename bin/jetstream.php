#!/usr/bin/env php
<?php
/**
 * Copyright (C) 2010-2024, the Friendica project
 * SPDX-FileCopyrightText: 2010-2024 the Friendica project
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 */

use Dice\Dice;
use Friendica\Core\Addon;
use Friendica\Core\Hook;
use Friendica\Core\Logger;
use Friendica\Database\DBA;
use Friendica\DI;
use Psr\Log\LoggerInterface;
use Friendica\Protocol\ATProtocol\Jetstream;

if (php_sapi_name() !== 'cli') {
	header($_SERVER["SERVER_PROTOCOL"] . ' 403 Forbidden');
	exit();
}

// Ensure that Jetstream.php is executed from the base path of the installation
if (!file_exists('index.php') && (sizeof((array)$_SERVER['argv']) != 0)) {
	$directory = dirname($_SERVER['argv'][0]);

	if (substr($directory, 0, 1) != '/') {
		$directory = $_SERVER['PWD'] . '/' . $directory;
	}
	$directory = realpath($directory . '/..');

	chdir($directory);
}

require dirname(__DIR__) . '/vendor/autoload.php';

$dice = (new Dice())->addRules(include __DIR__ . '/../static/dependencies.config.php');
/** @var \Friendica\Core\Addon\Capability\ICanLoadAddons $addonLoader */
$addonLoader = $dice->create(\Friendica\Core\Addon\Capability\ICanLoadAddons::class);
$dice        = $dice->addRules($addonLoader->getActiveAddonConfig('dependencies'));
$dice        = $dice->addRule(LoggerInterface::class, ['constructParams' => [Logger\Capability\LogChannel::DAEMON]]);

DI::init($dice);
\Friendica\Core\Logger\Handler\ErrorHandler::register($dice->create(\Psr\Log\LoggerInterface::class));
Addon::loadAddons();
Hook::loadHooks();
DI::config()->reload();

if (DI::mode()->isInstall()) {
	die("Friendica isn't properly installed yet.\n");
}

if (empty(DI::config()->get('jetstream', 'pidfile'))) {
	die(<<<TXT
Please set jetstream.pidfile in config/local.config.php. For example:

    'jetstream' => [
        'pidfile' => '/path/to/jetstream.pid',
    ],
TXT);
}

if (!Addon::isEnabled('bluesky')) {
	die("Bluesky has to be enabled.\n");
}

$pidfile = DI::config()->get('jetstream', 'pidfile');

if (in_array('start', (array)$_SERVER['argv'])) {
	$mode = 'start';
}

if (in_array('stop', (array)$_SERVER['argv'])) {
	$mode = 'stop';
}

if (in_array('status', (array)$_SERVER['argv'])) {
	$mode = 'status';
}

if (!isset($mode)) {
	die("Please use either 'start', 'stop' or 'status'.\n");
}

// Get options
$shortopts = 'f';
$longopts  = ['foreground'];
$options   = getopt($shortopts, $longopts);

$foreground = array_key_exists('f', $options) || array_key_exists('foreground', $options);

if (empty($_SERVER['argv'][0])) {
	die("Unexpected script behaviour. This message should never occur.\n");
}

$pid = null;

if (is_readable($pidfile)) {
	$pid = intval(file_get_contents($pidfile));
}

if (empty($pid) && in_array($mode, ['stop', 'status'])) {
	die("Pidfile wasn't found. Is jetstream running?\n");
}

if ($mode == 'status') {
	if (posix_kill($pid, 0)) {
		die("Jetstream process $pid is running.\n");
	}

	unlink($pidfile);

	die("Jetstream process $pid isn't running.\n");
}

if ($mode == 'stop') {
	posix_kill($pid, SIGTERM);

	unlink($pidfile);

	Logger::notice('Jetstream process was killed', ['pid' => $pid]);

	die("Jetstream process $pid was killed.\n");
}

if (!empty($pid) && posix_kill($pid, 0)) {
	die("Jetstream process $pid is already running.\n");
}

Logger::notice('Starting jetstream daemon.', ['pid' => $pid]);

if (!$foreground) {
	echo "Starting jetstream daemon.\n";

	DBA::disconnect();

	// Fork a daemon process
	$pid = pcntl_fork();
	if ($pid == -1) {
		echo "Daemon couldn't be forked.\n";
		Logger::warning('Could not fork daemon');
		exit(1);
	} elseif ($pid) {
		// The parent process continues here
		if (!file_put_contents($pidfile, $pid)) {
			echo "Pid file wasn't written.\n";
			Logger::warning('Could not store pid file');
			posix_kill($pid, SIGTERM);
			exit(1);
		}
		echo 'Child process started with pid ' . $pid . ".\n";
		Logger::notice('Child process started', ['pid' => $pid]);
		exit(0);
	}

	// We now are in the child process
	register_shutdown_function('shutdown');

	// Make the child the main process, detach it from the terminal
	if (posix_setsid() < 0) {
		return;
	}

	// Closing all existing connections with the outside
	fclose(STDIN);

	// And now connect the database again
	DBA::connect();
}

// Just to be sure that this script really runs endlessly
set_time_limit(0);

// Now running as a daemon.
$jetstream = $dice->create(Jetstream::class);
$jetstream->listen();

function shutdown()
{
	posix_kill(posix_getpid(), SIGTERM);
	posix_kill(posix_getpid(), SIGHUP);
}
