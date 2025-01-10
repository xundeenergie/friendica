<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Core\Logger;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Logger\LoggerManager;
use Friendica\Core\Logger\Type\ProfilerLogger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class LoggerManagerTest extends TestCase
{
	public function testGetLoggerReturnsPsrLogger(): void
	{
		$factory = new LoggerManager($this->createStub(IManageConfigValues::class));

		$this->assertInstanceOf(LoggerInterface::class, $factory->getLogger());
	}

	public function testGetLoggerReturnsSameObject(): void
	{
		$factory = new LoggerManager($this->createStub(IManageConfigValues::class));

		$this->assertSame($factory->getLogger(), $factory->getLogger());
	}

	public function testGetLoggerWithDebugDisabledReturnsNullLogger(): void
	{
		$config = $this->createStub(IManageConfigValues::class);
		$config->method('get')->willReturnMap([
			['system', 'debugging', null, false],
		]);

		$factory = new LoggerManager($config);

		$this->assertInstanceOf(NullLogger::class, $factory->getLogger());
	}

	public function testGetLoggerWithProfilerEnabledReturnsProfilerLogger(): void
	{
		$config = $this->createStub(IManageConfigValues::class);
		$config->method('get')->willReturnMap([
			['system', 'debugging', null, false],
			['system', 'profiling', null, true],
		]);

		$factory = new LoggerManager($config);

		$this->assertInstanceOf(ProfilerLogger::class, $factory->getLogger());
	}
}
