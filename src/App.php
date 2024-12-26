<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica;

use Dice\Dice;
use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\App\Mode;
use Friendica\App\Page;
use Friendica\App\Request;
use Friendica\App\Router;
use Friendica\Capabilities\ICanCreateResponses;
use Friendica\Content\Nav;
use Friendica\Core\Config\Factory\Config;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Database\DBA;
use Friendica\Database\Definition\DbaDefinition;
use Friendica\Database\Definition\ViewDefinition;
use Friendica\DI;
use Friendica\Module\Maintenance;
use Friendica\Security\Authentication;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\Logger;
use Friendica\Core\Logger\Capability\LogChannel;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\System;
use Friendica\Core\Update;
use Friendica\Core\Worker;
use Friendica\Module\Special\HTTPException as ModuleHTTPException;
use Friendica\Network\HTTPException;
use Friendica\Protocol\ATProtocol\DID;
use Friendica\Security\ExAuth;
use Friendica\Security\OpenWebAuth;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\HTTPInputData;
use Friendica\Util\HTTPSignature;
use Friendica\Util\Profiler;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;



/**
 * Our main application structure for the life of this page.
 *
 * Primarily deals with the URL that got us here
 * and tries to make some sense of it, and
 * stores our page contents and config storage
 * and anything else that might need to be passed around
 * before we spit the page out.
 *
 */
class App
{
	const PLATFORM = 'Friendica';
	const CODENAME = 'Yellow Archangel';
	const VERSION  = '2024.12-dev';

	public static function fromDice(Dice $dice): self
	{
		return new self($dice);
	}

	/**
	 * @var Dice
	 */
	private $container;

	/**
	 * @var Mode The Mode of the Application
	 */
	private $mode;

	/**
	 * @var BaseURL
	 */
	private $baseURL;

	/** @var string */
	private $requestId;

	/** @var Authentication */
	private $auth;

	/**
	 * @var IManageConfigValues The config
	 */
	private $config;

	/**
	 * @var LoggerInterface The logger
	 */
	private $logger;

	/**
	 * @var Profiler The profiler of this app
	 */
	private $profiler;

	/**
	 * @var L10n The translator
	 */
	private $l10n;

	/**
	 * @var App\Arguments
	 */
	private $args;

	/**
	 * @var IHandleUserSessions
	 */
	private $session;

	/**
	 * @var AppHelper $appHelper
	 */
	private $appHelper;

	private function __construct(Dice $container)
	{
		$this->container = $container;
	}

	public function processRequest(ServerRequestInterface $request, float $start_time): void
	{
		$this->setupContainerForAddons();

		$this->setupContainerForLogger(LogChannel::DEFAULT);

		$this->container = $this->container->addRule(Mode::class, [
			'call' => [
				['determineRunMode', [false, $request->getServerParams()], Dice::CHAIN_CALL],
			],
		]);

		$this->setupLegacyServerLocator();

		$this->registerErrorHandler();

		$this->requestId = $this->container->create(Request::class)->getRequestId();
		$this->auth      = $this->container->create(Authentication::class);
		$this->config    = $this->container->create(IManageConfigValues::class);
		$this->mode      = $this->container->create(Mode::class);
		$this->baseURL   = $this->container->create(BaseURL::class);
		$this->logger    = $this->container->create(LoggerInterface::class);
		$this->profiler  = $this->container->create(Profiler::class);
		$this->l10n      = $this->container->create(L10n::class);
		$this->args      = $this->container->create(Arguments::class);
		$this->session   = $this->container->create(IHandleUserSessions::class);
		$this->appHelper = $this->container->create(AppHelper::class);

		$this->loadSetupForFrontend(
			$request,
			$this->container->create(DbaDefinition::class),
			$this->container->create(ViewDefinition::class),
		);

		$this->registerTemplateEngine();

		$this->mode->setExecutor(Mode::INDEX);

		$this->runFrontend(
			$this->container->create(Router::class),
			$this->container->create(IManagePersonalConfigValues::class),
			$this->container->create(Page::class),
			$this->container->create(Nav::class),
			$this->container->create(ModuleHTTPException::class),
			new HTTPInputData($request->getServerParams()),
			$start_time,
			$request->getServerParams()
		);
	}

	public function processEjabberd(): void
	{
		$this->setupContainerForAddons();

		$this->setupContainerForLogger(LogChannel::AUTH_JABBERED);

		$this->setupLegacyServerLocator();

		$this->registerErrorHandler();

		// Check the database structure and possibly fixes it
		Update::check(DI::basePath(), true);

		$appMode = $this->container->create(Mode::class);

		if ($appMode->isNormal()) {
			/** @var ExAuth $oAuth */
			$oAuth = $this->container->create(ExAuth::class);
			$oAuth->readStdin();
		}
	}

	public function processConsole(array $argv): void
	{
		$this->setupContainerForAddons();

		$this->setupContainerForLogger(LogChannel::CONSOLE);

		$this->setupLegacyServerLocator();

		$this->registerErrorHandler();

		$this->registerTemplateEngine();

		(new \Friendica\Core\Console($this->container, $argv))->execute();
	}

	public function processDaemon(array $argv, array $options): void
	{
		$this->setupContainerForAddons();

		$this->setupContainerForLogger(LogChannel::DAEMON);

		$this->setupLegacyServerLocator();

		$this->registerErrorHandler();

		if (DI::mode()->isInstall()) {
			die("Friendica isn't properly installed yet.\n");
		}

		DI::mode()->setExecutor(Mode::DAEMON);

		DI::config()->reload();

		if (empty(DI::config()->get('system', 'pidfile'))) {
			die(<<< TXT
					Please set system.pidfile in config/local.config.php. For example:

						'system' => [
							'pidfile' => '/path/to/daemon.pid',
						],
					TXT
			);
		}

		$pidfile = DI::config()->get('system', 'pidfile');

		if (in_array('start', $argv)) {
			$mode = 'start';
		}

		if (in_array('stop', $argv)) {
			$mode = 'stop';
		}

		if (in_array('status', $argv)) {
			$mode = 'status';
		}

		$foreground = array_key_exists('f', $options) || array_key_exists('foreground', $options);

		if (!isset($mode)) {
			die("Please use either 'start', 'stop' or 'status'.\n");
		}

		if (empty($argv[0])) {
			die("Unexpected script behaviour. This message should never occur.\n");
		}

		$pid = null;

		if (is_readable($pidfile)) {
			$pid = intval(file_get_contents($pidfile));
		}

		if (empty($pid) && in_array($mode, ['stop', 'status'])) {
			DI::keyValue()->set('worker_daemon_mode', false);
			die("Pidfile wasn't found. Is the daemon running?\n");
		}

		if ($mode == 'status') {
			if (posix_kill($pid, 0)) {
				die("Daemon process $pid is running.\n");
			}

			unlink($pidfile);

			DI::keyValue()->set('worker_daemon_mode', false);
			die("Daemon process $pid isn't running.\n");
		}

		if ($mode == 'stop') {
			posix_kill($pid, SIGTERM);

			unlink($pidfile);

			Logger::notice('Worker daemon process was killed', ['pid' => $pid]);

			DI::keyValue()->set('worker_daemon_mode', false);
			die("Worker daemon process $pid was killed.\n");
		}

		if (!empty($pid) && posix_kill($pid, 0)) {
			die("Daemon process $pid is already running.\n");
		}

		Logger::notice('Starting worker daemon.', ['pid' => $pid]);

		if (!$foreground) {
			echo "Starting worker daemon.\n";

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
			register_shutdown_function(function () {
				posix_kill(posix_getpid(), SIGTERM);
				posix_kill(posix_getpid(), SIGHUP);
			});

			// Make the child the main process, detach it from the terminal
			if (posix_setsid() < 0) {
				return;
			}

			// Closing all existing connections with the outside
			fclose(STDIN);

			// And now connect the database again
			DBA::connect();
		}

		DI::keyValue()->set('worker_daemon_mode', true);

		// Just to be sure that this script really runs endlessly
		set_time_limit(0);

		$wait_interval = intval(DI::config()->get('system', 'cron_interval', 5)) * 60;

		$do_cron = true;
		$last_cron = 0;

		// Now running as a daemon.
		while (true) {
			// Check the database structure and possibly fixes it
			Update::check(DI::basePath(), true);

			if (!$do_cron && ($last_cron + $wait_interval) < time()) {
				Logger::info('Forcing cron worker call.', ['pid' => $pid]);
				$do_cron = true;
			}

			if ($do_cron || (!DI::system()->isMaxLoadReached() && Worker::entriesExists() && Worker::isReady())) {
				Worker::spawnWorker($do_cron);
			} else {
				Logger::info('Cool down for 5 seconds', ['pid' => $pid]);
				sleep(5);
			}

			if ($do_cron) {
				// We force a reconnect of the database connection.
				// This is done to ensure that the connection don't get lost over time.
				DBA::reconnect();

				$last_cron = time();
			}

			$start = time();
			Logger::info('Sleeping', ['pid' => $pid, 'until' => gmdate(DateTimeFormat::MYSQL, $start + $wait_interval)]);

			do {
				$seconds = (time() - $start);

				// logarithmic wait time calculation.
				// Background: After jobs had been started, they often fork many workers.
				// To not waste too much time, the sleep period increases.
				$arg = (($seconds + 1) / ($wait_interval / 9)) + 1;
				$sleep = min(1000000, round(log10($arg) * 1000000, 0));
				usleep($sleep);

				$pid = pcntl_waitpid(-1, $status, WNOHANG);
				if ($pid > 0) {
					Logger::info('Children quit via pcntl_waitpid', ['pid' => $pid, 'status' => $status]);
				}

				$timeout = ($seconds >= $wait_interval);
			} while (!$timeout && !Worker\IPC::JobsExists());

			if ($timeout) {
				$do_cron = true;
				Logger::info('Woke up after $wait_interval seconds.', ['pid' => $pid, 'sleep' => $wait_interval]);
			} else {
				$do_cron = false;
				Logger::info('Worker jobs are calling to be forked.', ['pid' => $pid]);
			}
		}
	}

	private function setupContainerForAddons(): void
	{
		/** @var \Friendica\Core\Addon\Capability\ICanLoadAddons $addonLoader */
		$addonLoader = $this->container->create(\Friendica\Core\Addon\Capability\ICanLoadAddons::class);

		$this->container = $this->container->addRules($addonLoader->getActiveAddonConfig('dependencies'));
	}

	private function setupContainerForLogger(string $logChannel): void
	{
		$this->container = $this->container->addRule(LoggerInterface::class, [
			'constructParams' => [$logChannel],
		]);
	}

	private function setupLegacyServerLocator(): void
	{
		DI::init($this->container);
	}

	private function registerErrorHandler(): void
	{
		\Friendica\Core\Logger\Handler\ErrorHandler::register($this->container->create(LoggerInterface::class));
	}

	private function registerTemplateEngine(): void
	{
		Renderer::registerTemplateEngine('Friendica\Render\FriendicaSmartyEngine');
	}

	/**
	 * Load the whole app instance
	 */
	private function loadSetupForFrontend(ServerRequestInterface $request, DbaDefinition $dbaDefinition, ViewDefinition $viewDefinition)
	{
		if ($this->config->get('system', 'ini_max_execution_time') !== false) {
			set_time_limit((int)$this->config->get('system', 'ini_max_execution_time'));
		}

		if ($this->config->get('system', 'ini_pcre_backtrack_limit') !== false) {
			ini_set('pcre.backtrack_limit', (int)$this->config->get('system', 'ini_pcre_backtrack_limit'));
		}

		// Normally this constant is defined - but not if "pcntl" isn't installed
		if (!defined('SIGTERM')) {
			define('SIGTERM', 15);
		}

		// Ensure that all "strtotime" operations do run timezone independent
		date_default_timezone_set('UTC');

		$this->profiler->reset();

		if ($this->mode->has(Mode::DBAVAILABLE)) {
			Core\Hook::loadHooks();
			$loader = (new Config())->createConfigFileManager($this->appHelper->getBasePath(), $request->getServerParams());
			Core\Hook::callAll('load_config', $loader);

			// Hooks are now working, reload the whole definitions with hook enabled
			$dbaDefinition->load(true);
			$viewDefinition->load(true);
		}

		$this->loadDefaultTimezone();
	}

	/**
	 * Loads the default timezone
	 *
	 * Include support for legacy $default_timezone
	 *
	 * @global string $default_timezone
	 */
	private function loadDefaultTimezone()
	{
		if ($this->config->get('system', 'default_timezone')) {
			$timezone = $this->config->get('system', 'default_timezone', 'UTC');
		} else {
			global $default_timezone;
			$timezone = $default_timezone ?? '' ?: 'UTC';
		}

		$this->appHelper->setTimeZone($timezone);
	}

	/**
	 * Frontend App script
	 *
	 * The App object behaves like a container and a dispatcher at the same time, including a representation of the
	 * request and a representation of the response.
	 *
	 * This probably should change to limit the size of this monster method.
	 *
	 * @param Router                      $router
	 * @param IManagePersonalConfigValues $pconfig
	 * @param Page                        $page       The Friendica page printing container
	 * @param ModuleHTTPException         $httpException The possible HTTP Exception container
	 * @param HTTPInputData               $httpInput  A library for processing PHP input streams
	 * @param float                       $start_time The start time of the overall script execution
	 * @param array                       $server     The $_SERVER array
	 *
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private function runFrontend(
		Router $router,
		IManagePersonalConfigValues $pconfig,
		Page $page,
		Nav $nav,
		ModuleHTTPException $httpException,
		HTTPInputData $httpInput,
		float $start_time,
		array $server
	) {
		$requeststring = ($server['REQUEST_METHOD'] ?? '') . ' ' . ($server['REQUEST_URI'] ?? '') . ' ' . ($server['SERVER_PROTOCOL'] ?? '');
		$this->logger->debug('Request received', ['address' => $server['REMOTE_ADDR'] ?? '', 'request' => $requeststring, 'referer' => $server['HTTP_REFERER'] ?? '', 'user-agent' => $server['HTTP_USER_AGENT'] ?? '']);
		$request_start = microtime(true);
		$request = $_REQUEST;

		$this->profiler->set($start_time, 'start');
		$this->profiler->set(microtime(true), 'classinit');

		$moduleName = $this->args->getModuleName();
		$page->setLogging($this->args->getMethod(), $this->args->getModuleName(), $this->args->getCommand());

		try {
			// Missing DB connection: ERROR
			if ($this->mode->has(Mode::LOCALCONFIGPRESENT) && !$this->mode->has(Mode::DBAVAILABLE)) {
				throw new HTTPException\InternalServerErrorException($this->l10n->t('Apologies but the website is unavailable at the moment.'));
			}

			if (!$this->mode->isInstall()) {
				// Force SSL redirection
				if ($this->config->get('system', 'force_ssl') &&
					(empty($server['HTTPS']) || $server['HTTPS'] === 'off') &&
					(empty($server['HTTP_X_FORWARDED_PROTO']) || $server['HTTP_X_FORWARDED_PROTO'] === 'http') &&
					!empty($server['REQUEST_METHOD']) &&
					$server['REQUEST_METHOD'] === 'GET') {
					System::externalRedirect($this->baseURL . '/' . $this->args->getQueryString());
				}
				Core\Hook::callAll('init_1');
			}

			DID::routeRequest($this->args->getCommand(), $server);

			if ($this->mode->isNormal() && !$this->mode->isBackend()) {
				$requester = HTTPSignature::getSigner('', $server);
				if (!empty($requester)) {
					OpenWebAuth::addVisitorCookieForHandle($requester);
				}
			}

			// ZRL
			if (!empty($_GET['zrl']) && $this->mode->isNormal() && !$this->mode->isBackend() && !$this->session->getLocalUserId()) {
				// Only continue when the given profile link seems valid.
				// Valid profile links contain a path with "/profile/" and no query parameters
				if ((parse_url($_GET['zrl'], PHP_URL_QUERY) == '') &&
					strpos(parse_url($_GET['zrl'], PHP_URL_PATH) ?? '', '/profile/') !== false) {
					$this->auth->setUnauthenticatedVisitor($_GET['zrl']);
					OpenWebAuth::zrlInit();
				} else {
					// Someone came with an invalid parameter, maybe as a DDoS attempt
					// We simply stop processing here
					$this->logger->debug('Invalid ZRL parameter.', ['zrl' => $_GET['zrl']]);
					throw new HTTPException\ForbiddenException();
				}
			}

			if (!empty($_GET['owt']) && $this->mode->isNormal()) {
				$token = $_GET['owt'];
				OpenWebAuth::init($token);
			}

			if (!$this->mode->isBackend()) {
				$this->auth->withSession();
			}

			if ($this->session->isUnauthenticated()) {
				header('X-Account-Management-Status: none');
			}

			/*
			 * check_config() is responsible for running update scripts. These automatically
			 * update the DB schema whenever we push a new one out. It also checks to see if
			 * any addons have been added or removed and reacts accordingly.
			 */

			// in install mode, any url loads install module
			// but we need "view" module for stylesheet
			if ($this->mode->isInstall() && $moduleName !== 'install') {
				$this->baseURL->redirect('install');
			} else {
				Core\Update::check($this->appHelper->getBasePath(), false);
				Core\Addon::loadAddons();
				Core\Hook::loadHooks();
			}

			// Compatibility with Hubzilla
			if ($moduleName == 'rpost') {
				$this->baseURL->redirect('compose');
			}

			// Compatibility with the Android Diaspora client
			if ($moduleName == 'stream') {
				$this->baseURL->redirect('network?order=post');
			}

			if ($moduleName == 'conversations') {
				$this->baseURL->redirect('message');
			}

			if ($moduleName == 'commented') {
				$this->baseURL->redirect('network?order=comment');
			}

			if ($moduleName == 'liked') {
				$this->baseURL->redirect('network?order=comment');
			}

			if ($moduleName == 'activity') {
				$this->baseURL->redirect('network?conv=1');
			}

			if (($moduleName == 'status_messages') && ($this->args->getCommand() == 'status_messages/new')) {
				$this->baseURL->redirect('bookmarklet');
			}

			if (($moduleName == 'user') && ($this->args->getCommand() == 'user/edit')) {
				$this->baseURL->redirect('settings');
			}

			if (($moduleName == 'tag_followings') && ($this->args->getCommand() == 'tag_followings/manage')) {
				$this->baseURL->redirect('search');
			}

			// Initialize module that can set the current theme in the init() method, either directly or via App->setProfileOwner
			$page['page_title'] = $moduleName;

			// The "view" module is required to show the theme CSS
			if (!$this->mode->isInstall() && !$this->mode->has(Mode::MAINTENANCEDISABLED) && $moduleName !== 'view') {
				$module = $router->getModule(Maintenance::class);
			} else {
				// determine the module class and save it to the module instance
				// @todo there's an implicit dependency due SESSION::start(), so it has to be called here (yet)
				$module = $router->getModule();
			}

			// Display can change depending on the requested language, so it shouldn't be cached whole
			header('Vary: Accept-Language', false);

			// Processes data from GET requests
			$httpinput = $httpInput->process();
			$input     = array_merge($httpinput['variables'], $httpinput['files'], $request);

			// Let the module run its internal process (init, get, post, ...)
			$timestamp = microtime(true);
			$response  = $module->run($httpException, $input);
			$this->profiler->set(microtime(true) - $timestamp, 'content');

			// Wrapping HTML responses in the theme template
			if ($response->getHeaderLine(ICanCreateResponses::X_HEADER) === ICanCreateResponses::TYPE_HTML) {
				$response = $page->run($this->appHelper, $this->session, $this->baseURL, $this->args, $this->mode, $response, $this->l10n, $this->profiler, $this->config, $pconfig, $nav, $this->session->getLocalUserId());
			}

			$this->logger->debug('Request processed sucessfully', ['response' => $response->getStatusCode(), 'address' => $server['REMOTE_ADDR'] ?? '', 'request' => $requeststring, 'referer' => $server['HTTP_REFERER'] ?? '', 'user-agent' => $server['HTTP_USER_AGENT'] ?? '', 'duration' => number_format(microtime(true) - $request_start, 3)]);
			$this->logSlowCalls(microtime(true) - $request_start, $response->getStatusCode(), $requeststring, $server['HTTP_USER_AGENT'] ?? '');
			System::echoResponse($response);
		} catch (HTTPException $e) {
			$this->logger->debug('Request processed with exception', ['response' => $e->getCode(), 'address' => $server['REMOTE_ADDR'] ?? '', 'request' => $requeststring, 'referer' => $server['HTTP_REFERER'] ?? '', 'user-agent' => $server['HTTP_USER_AGENT'] ?? '', 'duration' => number_format(microtime(true) - $request_start, 3)]);
			$this->logSlowCalls(microtime(true) - $request_start, $e->getCode(), $requeststring, $server['HTTP_USER_AGENT'] ?? '');
			$httpException->rawContent($e);
		}
		$page->logRuntime($this->config, 'runFrontend');
	}

	/**
	 * Log slow page executions
	 *
	 * @param float $duration
	 * @param integer $code
	 * @param string $request
	 * @param string $agent
	 * @return void
	 */
	private function logSlowCalls(float $duration, int $code, string $request, string $agent)
	{
		$logfile  = $this->config->get('system', 'page_execution_logfile');
		$loglimit = $this->config->get('system', 'page_execution_log_limit');
		if (empty($logfile) || empty($loglimit) || ($duration < $loglimit)) {
			return;
		}

		@file_put_contents(
			$logfile,
			DateTimeFormat::utcNow() . "\t" . round($duration, 3) . "\t" .
			$this->requestId . "\t" . $code . "\t" .
			$request . "\t" . $agent . "\n",
			FILE_APPEND
		);
	}
}
