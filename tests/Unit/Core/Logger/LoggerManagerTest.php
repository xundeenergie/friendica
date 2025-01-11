<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Core\Logger;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Logger\Capability\LogChannel;
use Friendica\Core\Logger\Factory\LoggerFactory;
use Friendica\Core\Logger\LoggerManager;
use Friendica\Core\Logger\Type\ProfilerLogger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class LoggerManagerTest extends TestCase
{
	public function testGetLoggerReturnsPsrLogger(): void
	{
		$reflectionProperty = new \ReflectionProperty(LoggerManager::class, 'logger');
		$reflectionProperty->setAccessible(true);
		$reflectionProperty->setValue(null, null);

		$factory = new LoggerManager(
			$this->createStub(IManageConfigValues::class),
			$this->createStub(LoggerFactory::class)
		);

		$this->assertInstanceOf(LoggerInterface::class, $factory->getLogger());
	}

	public function testGetLoggerReturnsSameObject(): void
	{
		$reflectionProperty = new \ReflectionProperty(LoggerManager::class, 'logger');
		$reflectionProperty->setAccessible(true);
		$reflectionProperty->setValue(null, null);

		$factory = new LoggerManager(
			$this->createStub(IManageConfigValues::class),
			$this->createStub(LoggerFactory::class)
		);

		$this->assertSame($factory->getLogger(), $factory->getLogger());
	}

	public function testGetLoggerWithDebugDisabledReturnsNullLogger(): void
	{
		$config = $this->createStub(IManageConfigValues::class);
		$config->method('get')->willReturnMap([
			['system', 'debugging', null, false],
		]);

		$reflectionProperty = new \ReflectionProperty(LoggerManager::class, 'logger');
		$reflectionProperty->setAccessible(true);
		$reflectionProperty->setValue(null, null);

		$factory = new LoggerManager(
			$config,
			$this->createStub(LoggerFactory::class)
		);

		$this->assertInstanceOf(NullLogger::class, $factory->getLogger());
	}

	public function testGetLoggerWithProfilerEnabledReturnsProfilerLogger(): void
	{
		$config = $this->createStub(IManageConfigValues::class);
		$config->method('get')->willReturnMap([
			['system', 'debugging', null, false],
			['system', 'profiling', null, true],
		]);

		$reflectionProperty = new \ReflectionProperty(LoggerManager::class, 'logger');
		$reflectionProperty->setAccessible(true);
		$reflectionProperty->setValue(null, null);

		$factory = new LoggerManager(
			$config,
			$this->createStub(LoggerFactory::class)
		);

		$this->assertInstanceOf(ProfilerLogger::class, $factory->getLogger());
	}

	public function testChangeChannelReturnsDifferentLogger(): void
	{
		$config = $this->createStub(IManageConfigValues::class);
		$config->method('get')->willReturnMap([
			['system', 'debugging', null, false],
			['system', 'profiling', null, true],
		]);

		$reflectionProperty = new \ReflectionProperty(LoggerManager::class, 'logger');
		$reflectionProperty->setAccessible(true);
		$reflectionProperty->setValue(null, null);

		$factory = new LoggerManager(
			$config,
			$this->createStub(LoggerFactory::class)
		);

		$logger1 = $factory->getLogger();

		$factory->changeLogChannel(LogChannel::CONSOLE);

		$this->assertNotSame($logger1, $factory->getLogger());
	}
}
