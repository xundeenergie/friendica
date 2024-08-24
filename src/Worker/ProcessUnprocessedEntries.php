<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\Core\Logger;
use Friendica\Protocol\ActivityPub\Queue;

class ProcessUnprocessedEntries
{
	/**
	 * Process all unprocessed entries
	 *
	 * @return void
	 */
	public static function execute()
	{
		Logger::info('Start processing unprocessed entries');
		Queue::processAll();
		Logger::info('Successfully processed unprocessed entries');
	}
}
