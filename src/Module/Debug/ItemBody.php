<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Debug;

use Friendica\BaseModule;
use Friendica\Core\System;
use Friendica\DI;
use Friendica\Model\Post;
use Friendica\Network\HTTPException;

/**
 * Print the body of an Item
 */
class ItemBody extends BaseModule
{
	protected function content(array $request = []): string
	{
		if (!DI::userSession()->getLocalUserId()) {
			throw new HTTPException\UnauthorizedException(DI::l10n()->t('Access denied.'));
		}

		if (empty($this->parameters['item'])) {
			throw new HTTPException\NotFoundException(DI::l10n()->t('Item not found.'));
		}

		$itemId = intval($this->parameters['item']);

		$item = Post::selectFirst(['body'], ['uid' => [0, DI::userSession()->getLocalUserId()], 'uri-id' => $itemId]);

		if (!empty($item)) {
			if (DI::mode()->isAjax()) {
				echo str_replace("\n", '<br />', $item['body']);
				System::exit();
			} else {
				return str_replace("\n", '<br />', $item['body']);
			}
		} else {
			throw new HTTPException\NotFoundException(DI::l10n()->t('Item not found.'));
		}
	}
}
