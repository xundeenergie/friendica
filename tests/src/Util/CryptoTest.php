<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Util;

use Friendica\Util\Crypto;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;

class CryptoTest extends TestCase
{
	use PHPMock;

	public static function tearDownAfterClass(): void
	{
		// Reset mocking
		global $phpMock;
		$phpMock = [];

		parent::tearDownAfterClass();
	}

	public function testRandomDigitsRandomInt()
	{
		$random_int = $this->getFunctionMock('Friendica\Util', 'random_int');
		$random_int->expects($this->any())->willReturnCallback(function($min, $max) {
			return 1;
		});

		self::assertSame(1, Crypto::randomDigits(1));
		self::assertSame(11111111, Crypto::randomDigits(8));
	}

	public function dataRsa(): array
	{
		return [
			'diaspora' => [
				'key' => file_get_contents(__DIR__ . '/../../datasets/crypto/rsa/diaspora-public-rsa-base64'),
				'expected' => file_get_contents(__DIR__ . '/../../datasets/crypto/rsa/diaspora-public-pem'),
			],
		];
	}

	/**
	 * @dataProvider dataRsa
	 */
	public function testPubRsaToMe(string $key, string $expected)
	{
		self::assertSame($expected, Crypto::rsaToPem(base64_decode($key)));
	}


	public function dataPEM()
	{
		return [
			'diaspora' => [
				'key' => file_get_contents(__DIR__ . '/../../datasets/crypto/rsa/diaspora-public-pem'),
			],
		];
	}
}
