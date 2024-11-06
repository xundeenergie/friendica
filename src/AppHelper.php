<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica;

use DateTimeZone;
use Friendica\Util\DateTimeFormat;

/**
 * Helper for our main application structure for the life of this page.
 *
 * Primarily deals with the URL that got us here
 * and tries to make some sense of it, and
 * stores our page contents and config storage
 * and anything else that might need to be passed around
 * before we spit the page out.
 *
 */
final class AppHelper
{
	private $timezone = '';

	/**
	 * Set the timezone
	 *
	 * @param string $timezone A valid time zone identifier, see https://www.php.net/manual/en/timezones.php
	 * @return void
	 */
	public function setTimeZone(string $timezone)
	{
		$this->timezone = (new DateTimeZone($timezone))->getName();

		DateTimeFormat::setLocalTimeZone($this->timezone);
	}

	/**
	 * Get the timezone
	 *
	 * @return int
	 */
	public function getTimeZone(): string
	{
		return $this->timezone;
	}
}
