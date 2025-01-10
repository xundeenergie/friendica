<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Core\Logger\Factory;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * The logger factory for the core logging instances
 */
final class LoggerFactory
{
	private LoggerInterface $logger;

	public function create(): LoggerInterface
	{
		if (! isset($this->logger)) {
			$this->logger = new NullLogger();
		}

		return $this->logger;
	}
}
