<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Session\Factory;

use Friendica\App\BaseURL;
use Friendica\App\Mode;
use Friendica\Core\Cache\Factory\Cache;
use Friendica\Core\Cache\Type\DatabaseCache;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Session\Capability\IHandleSessions;
use Friendica\Core\Session\Type;
use Friendica\Core\Session\Handler;
use Friendica\Database\Database;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Factory for creating a valid Session for this run
 */
class Session
{
	/** @var string The plain, PHP internal session management */
	const HANDLER_NATIVE = 'native';
	/** @var string Using the database for session management */
	const HANDLER_DATABASE = 'database';
	/** @var string Using the cache for session management */
	const HANDLER_CACHE = 'cache';

	const HANDLER_DEFAULT = self::HANDLER_DATABASE;

	/**
	 * @param Mode                $mode
	 * @param BaseURL             $baseURL
	 * @param IManageConfigValues $config
	 * @param Database            $dba
	 * @param Cache               $cacheFactory
	 * @param LoggerInterface     $logger
	 * @param Profiler            $profiler
	 * @param array               $server
	 * @return IHandleSessions
	 */
	public function create(Mode $mode, BaseURL $baseURL, IManageConfigValues $config, Database $dba, Cache $cacheFactory, LoggerInterface $logger, Profiler $profiler, array $server = []): IHandleSessions
	{
		$profiler->startRecording('session');
		$session_handler = $config->get('system', 'session_handler', self::HANDLER_DEFAULT);

		try {
			if ($mode->isInstall() || $mode->isBackend()) {
				$session = new Type\Memory();
			} else {
				switch ($session_handler) {
					case self::HANDLER_DATABASE:
						$handler = new Handler\Database($dba, $logger, $server);
						break;
					case self::HANDLER_CACHE:
						$cache = $cacheFactory->createDistributed();

						// In case we're using the db as cache driver, use the native db session, not the cache
						if ($config->get('system', 'cache_driver') === DatabaseCache::NAME) {
							$handler = new Handler\Database($dba, $logger, $server);
						} else {
							$handler = new Handler\Cache($cache, $logger);
						}
						break;
					default:
						$handler = null;
				}

				$session = new Type\Native($baseURL, $handler);
			}
		} catch (\Throwable $e) {
			$logger->notice('Unable to create session', ['mode' => $mode, 'session_handler' => $session_handler, 'exception' => $e]);
			$session = new Type\Memory();
		} finally {
			$profiler->stopRecording();
			return $session;
		}
	}
}
