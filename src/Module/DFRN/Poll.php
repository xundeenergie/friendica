<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\DFRN;

use Friendica\BaseModule;
use Friendica\Core\System;
use Friendica\Model\User;
use Friendica\Module\Response;
use Friendica\Network\HTTPException;
use Friendica\Protocol\OStatus;

/**
 * DFRN Poll
 */
class Poll extends BaseModule
{
	protected function rawContent(array $request = [])
	{
		$owner = User::getByNickname(
			$this->parameters['nickname'] ?? '',
			['nickname', 'blocked', 'account_expired', 'account_removed']
		);
		if (!$owner || $owner['account_expired'] || $owner['account_removed']) {
			throw new HTTPException\NotFoundException($this->t('User not found.'));
		}

		if ($owner['blocked']) {
			throw new HTTPException\UnauthorizedException($this->t('Access to this profile has been restricted.'));
		}

		$last_update = $request['last_update'] ?? '';
		$this->httpExit(OStatus::feed($owner['nickname'], $last_update, 10) ?? '', Response::TYPE_ATOM);
	}
}
