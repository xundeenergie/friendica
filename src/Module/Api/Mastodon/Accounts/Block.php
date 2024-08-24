<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon\Accounts;

use Friendica\Core\System;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\User;
use Friendica\Module\BaseApi;

/**
 * @see https://docs.joinmastodon.org/methods/accounts/
 */
class Block extends BaseApi
{
	protected function post(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_FOLLOW);
		$uid = self::getCurrentUserID();

		if (empty($this->parameters['id'])) {
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
		}

		Contact\User::setBlocked($this->parameters['id'], $uid, true);

		$ucid = Contact::getUserContactId($this->parameters['id'], $uid);
		if ($ucid) {
			$contact = Contact::getById($ucid);
			if (!empty($contact)) {
				// Mastodon-expected behavior: relationship is severed on block
				Contact::terminateFriendship($contact);
			}
		}

		$this->jsonExit(DI::mstdnRelationship()->createFromContactId($this->parameters['id'], $uid)->toArray());
	}
}
