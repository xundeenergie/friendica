#!/usr/bin/env php
<?php
/**
 * Copyright (C) 2010-2024, the Friendica project
 * SPDX-FileCopyrightText: 2010-2024 the Friendica project
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * @deprecated 2025.01 use bin/console.php jetstream instead
 */

use Dice\Dice;

if (php_sapi_name() !== 'cli') {
	header($_SERVER["SERVER_PROTOCOL"] . ' 403 Forbidden');
	exit();
}

// Ensure that Jetstream.php is executed from the base path of the installation
chdir(dirname(__DIR__));

require dirname(__DIR__) . '/vendor/autoload.php';

$dice = (new Dice())->addRules(require(dirname(__DIR__) . '/static/dependencies.config.php'));

$app = \Friendica\App::fromDice($dice);

$argv = $_SERVER['argv'] ?? [];
array_splice($argv, 1, 0, "jetstream");

$app->processConsole($argv);
