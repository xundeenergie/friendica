<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Core\Storage;

use Friendica\Core\Storage\Capability\ICanConfigureStorage;
use Friendica\Test\MockedTest;

abstract class StorageConfigTest extends MockedTest
{
	/** @return ICanConfigureStorage */
	abstract protected function getInstance();

	abstract protected function assertOption(ICanConfigureStorage $storage);

	/**
	 * Test if the "getOption" is asserted
	 */
	public function testGetOptions()
	{
		$instance = $this->getInstance();

		$this->assertOption($instance);
	}
}
