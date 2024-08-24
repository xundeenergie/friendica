<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module;

use DOMDocument;
use DOMElement;
use Friendica\BaseModule;
use Friendica\Core\System;
use Friendica\DI;
use Friendica\Util\XML;

/**
 * Prints the opensearch description document
 * @see https://github.com/dewitt/opensearch/blob/master/opensearch-1-1-draft-6.md#opensearch-description-document
 */
class OpenSearch extends BaseModule
{
	/**
	 * @throws \Exception
	 */
	protected function rawContent(array $request = [])
	{
		$hostname = DI::baseUrl()->getHost();
		$baseUrl  = (string)DI::baseUrl();

		/** @var DOMDocument $xml */
		XML::fromArray([
			'OpenSearchDescription' => [
				'@attributes' => [
					'xmlns' => 'http://a9.com/-/spec/opensearch/1.1',
				],
				'ShortName'      => "Friendica $hostname",
				'Description'    => "Search in Friendica $hostname",
				'Contact'        => 'https://github.com/friendica/friendica/issues',
				'InputEncoding'  => 'UTF-8',
				'OutputEncoding' => 'UTF-8',
				'Developer'      => 'Friendica Developer Team',
			],
		], $xml);

		/** @var DOMElement $parent */
		$parent = $xml->getElementsByTagName('OpenSearchDescription')[0];

		XML::addElement($xml, $parent, 'Image',
			"$baseUrl/images/friendica-16.png", [
				'height' => 16,
				'width'  => 16,
				'type'   => 'image/png',
			]);

		XML::addElement($xml, $parent, 'Image',
			"$baseUrl/images/friendica-64.png", [
				'height' => 64,
				'width'  => 64,
				'type'   => 'image/png',
			]);

		XML::addElement($xml, $parent, 'Url', '', [
			'type'     => 'text/html',
			'template' => "$baseUrl/search?search={searchTerms}",
		]);

		XML::addElement($xml, $parent, 'Url', '', [
			'type'     => 'application/opensearchdescription+xml',
			'rel'      => 'self',
			'template' => "$baseUrl/opensearch",
		]);

		$this->httpExit($xml->saveXML(), Response::TYPE_XML, 'application/opensearchdescription+xml');
	}
}
