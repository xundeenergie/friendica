<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Core\Logger\Factory;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * The logger factory for the core logging instances
 */
final class LoggerFactory
{
	private bool $debug;

	private LoggerInterface $logger;

	public function __construct(IManageConfigValues $config)
	{
		$this->debug = (bool) $config->get('system', 'debugging') ?? false;
	}

	public function create(): LoggerInterface
	{
		if (! isset($this->logger)) {
			$this->logger = $this->createLogger();
		}

		return $this->logger;
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
