<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

/// @todo Use right namespace - needs alternative way of mocking random_int()
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

	/**
	 * Replaces random_int results with given mocks
	 *
	 */
	private function assertRandomInt($min, $max)
	{
		global $phpMock;
		$phpMock['random_int'] = function ($mMin, $mMax) use ($min, $max) {
			self::assertEquals($min, $mMin);
			self::assertEquals($max, $mMax);
			return 1;
		};
	}

	public function testRandomDigitsRandomInt()
	{
		$random_int = $this->getFunctionMock(__NAMESPACE__, 'random_int');
        $random_int->expects($this->any())->willReturnCallback(function($min, $max) {
			global $phpMock;
			if (isset($phpMock['random_int'])) {
				return call_user_func_array($phpMock['random_int'], func_get_args());
			}
		});

		self::assertRandomInt(0, 9);

		$test = Crypto::randomDigits(1);
		self::assertEquals(1, strlen($test));
		self::assertEquals(1, $test);

		$test = Crypto::randomDigits(8);
		self::assertEquals(8, strlen($test));
		self::assertEquals(11111111, $test);
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
		self::assertEquals($expected, Crypto::rsaToPem(base64_decode($key)));
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
