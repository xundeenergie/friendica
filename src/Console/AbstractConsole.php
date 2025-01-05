<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Console;

use Asika\SimpleConsole\Console;
use Friendica\Core\Logger\Capability\LogChannel;

/**
 * Abstract Console class for common settings
 */
abstract class AbstractConsole extends Console
{
	/**
	 * Overwrite this const in case you want to switch the LogChannel for this console command
	 *
	 * @var string
	 */
	public const LOG_CHANNEL = LogChannel::CONSOLE;
}
