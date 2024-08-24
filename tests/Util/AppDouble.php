<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Util;

use Friendica\App;

/**
 * Making the App class overridable for specific situations
 *
 * @see App
 */
class AppDouble extends App
{
	/** @var bool Marks/Overwrites if the user is currently logged in */
	protected $isLoggedIn = false;

	/**
	 * Manually overwrite the "isLoggedIn" behavior
	 *
	 * @param bool $isLoggedIn
	 */
	public function setIsLoggedIn(bool $isLoggedIn)
	{
		$this->isLoggedIn = $isLoggedIn;
	}

	public function isLoggedIn(): bool
	{
		return $this->isLoggedIn;
	}
}
