<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core;

use Friendica\DI;
use Friendica\Core\Logger\Type\WorkerLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Logger functions
 */
class Logger
{
	/**
	 * @var LoggerInterface The default Logger type
	 */
	const TYPE_LOGGER = LoggerInterface::class;
	/**
	 * @var WorkerLogger A specific worker logger type, which can be enabled
	 */
	const TYPE_WORKER = WorkerLogger::class;
	/**
	 * @var LoggerInterface The current logger type
	 */
	private static $type = self::TYPE_LOGGER;

	/**
	 * @return LoggerInterface
	 */
	private static function getInstance()
	{
		if (self::$type === self::TYPE_LOGGER) {
			return DI::logger();
		} else {
			return DI::workerLogger();
		}
	}

	/**
	 * Enable additional logging for worker usage
	 *
	 * @param string $functionName The worker function, which got called
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function enableWorker(string $functionName)
	{
		self::$type = self::TYPE_WORKER;
		self::getInstance()->setFunctionName($functionName);
	}

	/**
	 * Disable additional logging for worker usage
	 */
	public static function disableWorker()
	{
		self::$type = self::TYPE_LOGGER;
	}

	/**
	 * System is unusable.
	 *
	 * @see LoggerInterface::emergency()
	 *
	 * @param string $message Message to log
	 * @param array  $context Optional variables
	 * @return void
	 * @throws \Exception
	 */
	public static function emergency(string $message, array $context = [])
	{
		self::getInstance()->emergency($message, $context);
	}

	/**
	 * Action must be taken immediately.
	 * @see LoggerInterface::alert()
	 *
	 * Example: Entire website down, database unavailable, etc. This should
	 * trigger the SMS alerts and wake you up.
	 *
	 * @param string $message Message to log
	 * @param array  $context Optional variables
	 * @return void
	 * @throws \Exception
	 */
	public static function alert(string $message, array $context = [])
	{
		self::getInstance()->alert($message, $context);
	}

	/**
	 * Critical conditions.
	 * @see LoggerInterface::critical()
	 *
	 * Example: Application component unavailable, unexpected exception.
	 *
	 * @param string $message Message to log
	 * @param array  $context Optional variables
	 * @return void
	 * @throws \Exception
	 */
	public static function critical(string $message, array $context = [])
	{
		self::getInstance()->critical($message, $context);
	}

	/**
	 * Runtime errors that do not require immediate action but should typically
	 * be logged and monitored.
	 * @see LoggerInterface::error()
	 *
	 * @param string $message Message to log
	 * @param array  $context Optional variables
	 * @return void
	 * @throws \Exception
	 */
	public static function error(string $message, array $context = [])
	{
		self::getInstance()->error($message, $context);
	}

	/**
	 * Exceptional occurrences that are not errors.
	 * @see LoggerInterface::warning()
	 *
	 * Example: Use of deprecated APIs, poor use of an API, undesirable things
	 * that are not necessarily wrong.
	 *
	 * @param string $message Message to log
	 * @param array  $context Optional variables
	 * @return void
	 * @throws \Exception
	 */
	public static function warning(string $message, array $context = [])
	{
		self::getInstance()->warning($message, $context);
	}

	/**
	 * Normal but significant events.
	 * @see LoggerInterface::notice()
	 *
	 * @param string $message Message to log
	 * @param array  $context Optional variables
	 * @return void
	 * @throws \Exception
	 */
	public static function notice(string $message, array $context = [])
	{
		self::getInstance()->notice($message, $context);
	}

	/**
	 * Interesting events.
	 * @see LoggerInterface::info()
	 *
	 * Example: User logs in, SQL logs.
	 *
	 * @param string $message
	 * @param array  $context
	 *
	 * @return void
	 * @throws \Exception
	 */
	public static function info(string $message, array $context = [])
	{
		self::getInstance()->info($message, $context);
	}

	/**
	 * Detailed debug information.
	 * @see LoggerInterface::debug()
	 *
	 * @param string $message Message to log
	 * @param array  $context Optional variables
	 * @return void
	 * @throws \Exception
	 */
	public static function debug(string $message, array $context = [])
	{
		self::getInstance()->debug($message, $context);
	}

	/**
	 * An alternative logger for development.
	 *
	 * Works largely as log() but allows developers
	 * to isolate particular elements they are targeting
	 * personally without background noise
	 *
	 * @param string $message Message to log
	 * @param string $level Logging level
	 * @return void
	 * @throws \Exception
	 */
	public static function devLog(string $message, string $level = LogLevel::DEBUG)
	{
		DI::devLogger()->log($level, $message);
	}
}
