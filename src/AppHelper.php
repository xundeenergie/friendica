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
	private $profile_owner = 0;

	private $timezone = '';

	private $contact_id = 0;

	/**
	 * Set the profile owner ID
	 */
	public function setProfileOwner(int $owner_id): void
	{
		$this->profile_owner = $owner_id;
	}

	/**
	 * Get the profile owner ID
	 */
	public function getProfileOwner(): int
	{
		return $this->profile_owner;
	}

	/**
	 * Set the timezone
	 *
	 * @param string $timezone A valid time zone identifier, see https://www.php.net/manual/en/timezones.php
	 */
	public function setTimeZone(string $timezone): void
	{
		$this->timezone = (new DateTimeZone($timezone))->getName();

		DateTimeFormat::setLocalTimeZone($this->timezone);
	}

	/**
	 * Get the timezone name
	 */
	public function getTimeZone(): string
	{
		return $this->timezone;
	}

	/**
	 * Set the contact ID
	 */
	public function setContactId(int $contact_id): void
	{
		$this->contact_id = $contact_id;
	}

	/**
	 * Get the contact ID
	 */
	public function getContactId(): int
	{
		return $this->contact_id;
	}
}
