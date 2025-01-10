<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Core\Logger\Factory;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Logger\Type\ProfilerLogger;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * The logger factory for the core logging instances
 */
final class LoggerFactory
{
	private IManageConfigValues $config;

	private bool $debug;

	private bool $profiling;

	private LoggerInterface $logger;

	public function __construct(IManageConfigValues $config)
	{
		$this->config = $config;

		$this->debug     = (bool) $config->get('system', 'debugging') ?? false;
		$this->profiling = (bool) $config->get('system', 'profiling') ?? false;
	}

	public function create(): LoggerInterface
	{
		if (! isset($this->logger)) {
			$this->logger = $this->createProfiledLogger();
		}

		return $this->logger;
	}

	private function createProfiledLogger(): LoggerInterface
	{
		$logger = $this->createLogger();

		if ($this->profiling === true) {
			$profiler = new Profiler($this->config);

			$logger = new ProfilerLogger($logger, $profiler);
		}

		return $logger;
	}

	private function createLogger(): LoggerInterface
	{
		// Always return NullLogger if debug is disabled
		if ($this->debug === false) {
			return new NullLogger();
		}

		return new NullLogger();
	}
}
