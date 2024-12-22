<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types = 1);

namespace Friendica\Test\Unit\Util;

use Friendica\Util\BasePath;
use PHPUnit\Framework\TestCase;

class BasePathTest extends TestCase
{
	public static function getDataPaths(): array
	{
		return [
			'fullPath' => [
				'server' => [],
				'baseDir' => dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'config',
				'expected' => dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'config',
			],
			'relative' => [
				'server' => [],
				'baseDir' => 'config',
				'expected' => dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'config',
			],
			'document_root' => [
				'server' => [
					'DOCUMENT_ROOT' => dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'config',
				],
				'baseDir' => '/noooop',
				'expected' => dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'config',
			],
			'pwd' => [
				'server' => [
					'PWD' => dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'config',
				],
				'baseDir' => '/noooop',
				'expected' => dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'config',
			],
			'no_overwrite' => [
				'server' => [
					'DOCUMENT_ROOT' => dirname(__DIR__, 3),
					'PWD' => dirname(__DIR__, 3),
				],
				'baseDir' => 'config',
				'expected' => dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'config',
			],
			'no_overwrite_if_invalid' => [
				'server' => [
					'DOCUMENT_ROOT' => '/nopopop',
					'PWD' => dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'config',
				],
				'baseDir' => '/noatgawe22fafa',
				'expected' => dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'config',
			]
		];
	}

	/**
	 * Test the basepath determination
	 * @dataProvider getDataPaths
	 */
	public function testDetermineBasePath(array $server, string $baseDir, string $expected): void
	{
		$basepath = new BasePath($baseDir, $server);
		self::assertEquals($expected, $basepath->getPath());
	}

	/**
	 * Test the basepath determination with a complete wrong path
	 */
	public function testFailedBasePath(): void
	{
		$basepath = new BasePath('/now23452sgfgas', []);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('\'/now23452sgfgas\' is not a valid basepath');

		$basepath->getPath();
	}
}
