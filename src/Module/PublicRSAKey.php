<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\System;
use Friendica\Model\User;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Protocol\Salmon;

/**
 * prints the public RSA key of a user
 */
class PublicRSAKey extends BaseModule
{
	protected function rawContent(array $request = [])
	{
		if (empty($this->parameters['nick'])) {
			throw new BadRequestException();
		}

		$nick = $this->parameters['nick'];

		$user = User::getByNickname($nick, ['spubkey']);
		if (empty($user) || empty($user['spubkey'])) {
			throw new BadRequestException();
		}

		$this->httpExit(
			Salmon::salmonKey($user['spubkey']),
			Response::TYPE_BLANK,
			'application/magic-public-key'
		);
	}
}
