<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Module\Api\Twitter\DirectMessages;

use Friendica\App\Router;
use Friendica\DI;
use Friendica\Factory\Api\Twitter\DirectMessage;
use Friendica\Module\Api\Twitter\DirectMessages\Conversation;
use Friendica\Test\src\Module\Api\ApiTest;

class ConversationTest extends ApiTest
{
	/**
	 * Test the api_direct_messages_box() function.
	 *
	 * @return void
	 */
	public function testApiDirectMessagesBoxWithConversation()
	{
		$directMessage = new DirectMessage(DI::logger(), DI::dba(), DI::twitterUser());

		$response = (new Conversation($directMessage, DI::dba(), DI::mstdnError(), DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], ['extension' => 'json']))
			->run($this->httpExceptionMock, [
				'friendica_verbose' => true,
			]);

		$json = $this->toJson($response);

		self::assertEquals('error', $json->result);
		self::assertEquals('no mails available', $json->message);
	}
}
