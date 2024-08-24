<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Module\Api\Twitter\Users;

use Friendica\App\Router;
use Friendica\DI;
use Friendica\Module\Api\Twitter\Users\Lookup;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Test\src\Module\Api\ApiTest;

class LookupTest extends ApiTest
{
	/**
	 * Test the api_users_lookup() function.
	 *
	 * @return void
	 */
	public function testApiUsersLookup()
	{
		$this->expectException(NotFoundException::class);

		(new Lookup(DI::mstdnError(), DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock);
	}

	/**
	 * Test the api_users_lookup() function with an user ID.
	 *
	 * @return void
	 */
	public function testApiUsersLookupWithUserId()
	{
		$response = (new Lookup(DI::mstdnError(), DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock, [
				'user_id' => static::OTHER_USER['id']
			]);

		$json = $this->toJson($response);

		self::assertOtherUser($json[0]);
	}
}
