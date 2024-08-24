<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\Core\Logger;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;

/**
 * Check the git repository VERSION file and save the version to the DB
 *
 * Checking the upstream version is optional (opt-in) and can be done to either
 * the stable or the develop branch in the repository.
 */
class CheckVersion
{
	public static function execute()
	{
		Logger::notice('checkversion: start');

		$checkurl = DI::config()->get('system', 'check_new_version_url', 'none');

		switch ($checkurl) {
			case 'master':
			case 'stable':
				$checked_url = 'https://raw.githubusercontent.com/friendica/friendica/stable/VERSION';
				break;
			case 'develop':
				$checked_url = 'https://raw.githubusercontent.com/friendica/friendica/develop/VERSION';
				break;
			default:
				// don't check
				return;
		}
		Logger::info("Checking VERSION from: ".$checked_url);

		// fetch the VERSION file
		$gitversion = DBA::escape(trim(DI::httpClient()->fetch($checked_url, HttpClientAccept::TEXT)));
		Logger::notice("Upstream VERSION is: ".$gitversion);

		DI::keyValue()->set('git_friendica_version', $gitversion);

		Logger::notice('checkversion: end');

		return;
	}
}
