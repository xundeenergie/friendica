<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Factory\Api\Mastodon;

use Friendica\BaseFactory;
use Friendica\Database\DBA;
use Friendica\Model\Subscription as ModelSubscription;

class Subscription extends BaseFactory
{
	/**
	 * @param int $applicationid Application Id
	 * @param int $uid           Item user
	 *
	 * @return \Friendica\Object\Api\Mastodon\Status
	 */
	public function createForApplicationIdAndUserId(int $applicationid, int $uid): \Friendica\Object\Api\Mastodon\Subscription
	{
		$subscription = DBA::selectFirst('subscription', [], ['application-id' => $applicationid, 'uid' => $uid]);
		return new \Friendica\Object\Api\Mastodon\Subscription($subscription, ModelSubscription::getPublicVapidKey());
	}
}
