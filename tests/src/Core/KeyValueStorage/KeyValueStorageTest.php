<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Core\KeyValueStorage;

use Friendica\Core\KeyValueStorage\Capability\IManageKeyValuePairs;
use Friendica\Test\MockedTest;

abstract class KeyValueStorageTest extends MockedTest
{
	abstract public function getInstance(): IManageKeyValuePairs;

	public function testInstance()
	{
		$instance = $this->getInstance();

		self::assertInstanceOf(IManageKeyValuePairs::class, $instance);
	}

	public function dataTests(): array
	{
		return [
			'string'       => ['k' => 'data', 'v' => 'it'],
			'boolTrue'     => ['k' => 'data', 'v' => true],
			'boolFalse'    => ['k' => 'data', 'v' => false],
			'integer'      => ['k' => 'data', 'v' => 235],
			'decimal'      => ['k' => 'data', 'v' => 2.456],
			'array'        => ['k' => 'data', 'v' => ['1', 2, '3', true, false]],
			'boolIntTrue'  => ['k' => 'data', 'v' => 1],
			'boolIntFalse' => ['k' => 'data', 'v' => 0],
		];
	}

	/**
	 * @dataProvider dataTests
	 */
	public function testGetSetDelete($k, $v)
	{
		$instance = $this->getInstance();

		$instance->set($k, $v);

		self::assertEquals($v, $instance->get($k));
		self::assertEquals($v, $instance[$k]);

		$instance->delete($k);

		self::assertNull($instance->get($k));
		self::assertNull($instance[$k]);
	}

	/**
	 * @dataProvider dataTests
	 */
	public function testSetOverride($k, $v)
	{
		$instance = $this->getInstance();

		$instance->set($k, $v);

		self::assertEquals($v, $instance->get($k));
		self::assertEquals($v, $instance[$k]);

		$instance->set($k, 'another_value');

		self::assertEquals('another_value', $instance->get($k));
		self::assertEquals('another_value', $instance[$k]);
	}

	/**
	 * @dataProvider dataTests
	 */
	public function testOffsetSetDelete($k, $v)
	{
		$instance = $this->getInstance();

		$instance[$k] = $v;

		self::assertEquals($v, $instance->get($k));
		self::assertEquals($v, $instance[$k]);

		unset($instance[$k]);

		self::assertNull($instance->get($k));
		self::assertNull($instance[$k]);
	}
}
