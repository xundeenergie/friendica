<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Core\Logger\Factory;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Logger\Factory\LoggerFactory;
use Friendica\Core\Logger\Type\ProfilerLogger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class LoggerFactoryTest extends TestCase
{
	public function testCreateReturnsPsrLogger(): void
	{
		$factory = new LoggerFactory($this->createStub(IManageConfigValues::class));

		$this->assertInstanceOf(LoggerInterface::class, $factory->create());
	}

	public function testCreateReturnsSameObject(): void
	{
		$factory = new LoggerFactory($this->createStub(IManageConfigValues::class));

		$this->assertSame($factory->create(), $factory->create());
	}

	public function testCreateWithDebugDisabledReturnsNullLogger(): void
	{
		$config = $this->createStub(IManageConfigValues::class);
		$config->method('get')->willReturnMap([
			['system', 'debugging', null, false],
		]);

		$factory = new LoggerFactory($config);

		$this->assertInstanceOf(NullLogger::class, $factory->create());
	}

	public function testCreateWithProfilerEnabledReturnsProfilerLogger(): void
	{
		$config = $this->createStub(IManageConfigValues::class);
		$config->method('get')->willReturnMap([
			['system', 'debugging', null, false],
			['system', 'profiling', null, true],
		]);

		$factory = new LoggerFactory($config);

		$this->assertInstanceOf(ProfilerLogger::class, $factory->create());
	}
}
