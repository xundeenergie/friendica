<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Core\KeyValueStorage;

use Friendica\Core\KeyValueStorage\Capability\IManageKeyValuePairs;
use Friendica\Core\KeyValueStorage\Type\DBKeyValueStorage;
use Friendica\Database\Database;
use Friendica\Test\Util\CreateDatabaseTrait;

class DBKeyValueStorageTest extends KeyValueStorageTest
{
	use CreateDatabaseTrait;

	/** @var Database */
	protected $database;

	protected function setUp(): void
	{
		parent::setUp();

		$this->setUpVfsDir();
		$this->setUpDb();
	}

	protected function tearDown(): void
	{
		parent::tearDown();

		$this->tearDownDb();
	}

	public function getInstance(): IManageKeyValuePairs
	{
		$this->database = $this->getDbInstance();

		return new DBKeyValueStorage($this->database);
	}

	/** @dataProvider dataTests */
	public function testUpdatedAt($k, $v)
	{
		$instance = $this->getInstance();

		$instance->set($k, $v);

		self::assertEquals($v, $instance->get($k));
		self::assertEquals($v, $instance[$k]);

		$entry = $this->database->selectFirst(DBKeyValueStorage::DB_KEY_VALUE_TABLE, ['updated_at'], ['k' => $k]);
		self::assertNotEmpty($entry);

		$updateAt = $entry['updated_at'];

		$instance->set($k, 'another_value');

		self::assertEquals('another_value', $instance->get($k));
		self::assertEquals('another_value', $instance[$k]);

		$entry = $this->database->selectFirst(DBKeyValueStorage::DB_KEY_VALUE_TABLE, ['updated_at'], ['k' => $k]);
		self::assertNotEmpty($entry);

		$updateAtAfter = $entry['updated_at'];

		self::assertGreaterThanOrEqual($updateAt, $updateAtAfter);
	}
}
