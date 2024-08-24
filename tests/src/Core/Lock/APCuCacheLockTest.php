<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Core\Lock;

use Friendica\Core\Cache\Type\APCuCache;
use Friendica\Core\Lock\Type\CacheLock;

/**
 * @group APCU
 */
class APCuCacheLockTest extends LockTest
{
	protected function setUp(): void
	{
		if (!APCuCache::isAvailable()) {
			static::markTestSkipped('APCu is not available');
		}

		parent::setUp();
	}

	protected function getInstance()
	{
		return new \Friendica\Core\Lock\Type\CacheLock(new APCuCache('localhost'));
	}
}
