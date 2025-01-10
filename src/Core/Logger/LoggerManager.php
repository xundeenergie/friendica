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
	private IManageConfigValues $config;

	private bool $debug;

	private string $logLevel;

	private string $logChannel;

	private bool $profiling;

	private LoggerInterface $logger;

	public function __construct(IManageConfigValues $config)
	{
		$this->config = $config;

		$this->debug      = (bool) $config->get('system', 'debugging') ?? false;
		$this->logLevel   = (string) $config->get('system', 'loglevel') ?? LogLevel::NOTICE;
		$this->logChannel = LogChannel::DEFAULT;
		$this->profiling  = (bool) $config->get('system', 'profiling') ?? false;
	}

	/**
	 * (Creates and) Returns the logger instance
	 */
	public function getLogger(): LoggerInterface
	{
		if (! isset($this->logger)) {
			$this->logger = $this->createProfiledLogger();
		}

		return $this->logger;
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
