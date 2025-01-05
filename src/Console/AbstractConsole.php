<?php

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
	public const LOG_CHANNEL = LogChannel::class;
}
