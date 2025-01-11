<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Core\Logger;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Logger\Capability\LogChannel;
use Friendica\Core\Logger\Type\ProfilerLogger;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

/**
 * Manager for the core logging instances
 */
final class LoggerManager
{
	/**
	 * Workaround: $logger must be static
	 * because Dice always creates a new LoggerManager object
	 *
	 * @var LoggerInterface|null
	 */
	private static $logger = null;

	private IManageConfigValues $config;

	private bool $debug;

	private string $logLevel;

	private bool $profiling;

	private string $logChannel;

	public function __construct(IManageConfigValues $config)
	{
		$this->config = $config;

		$this->debug      = (bool) $config->get('system', 'debugging') ?? false;
		$this->logLevel   = (string) $config->get('system', 'loglevel') ?? LogLevel::NOTICE;
		$this->profiling  = (bool) $config->get('system', 'profiling') ?? false;
		$this->logChannel = LogChannel::DEFAULT;
	}

	public function changeLogChannel(string $logChannel): void
	{
		$this->logChannel = $logChannel;

		self::$logger = null;
	}

	/**
	 * (Creates and) Returns the logger instance
	 */
	public function getLogger(): LoggerInterface
	{
		if (self::$logger === null) {
			self::$logger = $this->createProfiledLogger();
		}

		return self::$logger;
	}

	private function createProfiledLogger(): LoggerInterface
	{
		// Always return NullLogger if debug is disabled
		if ($this->debug === false) {
			$logger = new NullLogger();
		} else {
			$logger = $this->createLogger($this->logLevel, $this->logChannel);
		}

		if ($this->profiling === true) {
			$profiler = new Profiler($this->config);

			$logger = new ProfilerLogger($logger, $profiler);
		}

		return $logger;
	}

	private function createLogger(string $logLevel, string $logChannel): LoggerInterface
	{
		return new NullLogger();
	}
}
