<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Core\Logger\Factory;

use Friendica\Core\Logger\Factory\LoggerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class LoggerFactoryTest extends TestCase
{
	public function testLoggerFactoryCreateReturnsPsrLogger(): void
	{
		$factory = new LoggerFactory();

		$this->assertInstanceOf(LoggerInterface::class, $factory->create());
	}

	public function testLoggerFactoryCreateReturnsSameObject(): void
	{
		$factory = new LoggerFactory();

		$this->assertSame($factory->create(), $factory->create());
	}
}
