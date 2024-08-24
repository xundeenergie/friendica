<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test;

use Dice\Dice;
use Friendica\App\Arguments;
use Friendica\App\Router;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Config\Factory\Config;
use Friendica\Core\Config\Util\ConfigFileManager;
use Friendica\Core\Session\Capability\IHandleSessions;
use Friendica\Core\Session\Type\Memory;
use Friendica\Database\Database;
use Friendica\Database\DBStructure;
use Friendica\DI;
use Friendica\Test\Util\Database\StaticDatabase;
use Friendica\Test\Util\VFSTrait;

/**
 * Parent class for test cases requiring fixtures
 */
abstract class FixtureTest extends MockedTest
{
	use FixtureTestTrait;

	protected function setUp(): void
	{
		parent::setUp();

		$this->setUpFixtures();
	}

	protected function tearDown(): void
	{
		$this->tearDownFixtures();

		parent::tearDown();
	}
}
