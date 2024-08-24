<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Core\Cache;

use Friendica\Core\Cache\Type\APCuCache;

/**
 * @group APCU
 */
class APCuCacheTest extends MemoryCacheTest
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
		$this->cache = new APCuCache('localhost');
		return $this->cache;
	}

	protected function tearDown(): void
	{
		$this->cache->clear(false);
		parent::tearDown();
	}
}
