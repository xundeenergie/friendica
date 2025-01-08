<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Core;

use Dice\Dice;
use Friendica\Core\Container;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ContainerTest extends TestCase
{
	public function testFromBasePathReturnsContainer(): void
	{
		$root = vfsStream::setup('friendica', null, [
			'static' => [
				'dependencies.config.php' => '<?php return [];',
			],
		]);

		$container = Container::fromBasePath($root->url());

		$this->assertInstanceOf(Container::class, $container);
	}

	public function testCreateReturnsObject(): void
	{
		$root = vfsStream::setup('friendica', null, [
			'static' => [
				'dependencies.config.php' => <<< PHP
					<?php return [
						\Psr\Log\LoggerInterface::class => [
							'instanceOf' => \Psr\Log\NullLogger::class,
						],
					];
					PHP,
			],
		]);

		$container = Container::fromBasePath($root->url());

		$this->assertInstanceOf(NullLogger::class, $container->create(LoggerInterface::class));
	}

	public function testFromDiceReturnsContainer(): void
	{
		$dice = $this->createMock(Dice::class);
		$dice->expects($this->never())->method('create');

		$container = Container::fromDice($dice);

		$this->assertInstanceOf(Container::class, $container);
	}

	public function testCreateFromContainer(): void
	{
		$dice = $this->createMock(Dice::class);
		$dice->expects($this->once())->method('create')->with(LoggerInterface::class)->willReturn(new NullLogger());

		$container = Container::fromDice($dice);

		$this->assertInstanceOf(NullLogger::class, $container->create(LoggerInterface::class));
	}

	public function testAddRuleFromContainer(): void
	{
		$dice = $this->createMock(Dice::class);
		$dice->expects($this->once())->method('addRule')->with(LoggerInterface::class, ['constructParams' => ['console']])->willReturn($dice);

		$container = Container::fromDice($dice);
		$container->addRule(LoggerInterface::class, ['constructParams' => ['console']]);
	}
}
