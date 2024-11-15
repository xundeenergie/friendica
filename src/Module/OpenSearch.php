<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module;

use DOMDocument;
use DOMElement;
use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Util\BasePath;
use Friendica\Util\Profiler;
use Friendica\Util\XML;
use Psr\Log\LoggerInterface;

/**
 * Prints the opensearch description document
 * @see https://github.com/dewitt/opensearch/blob/master/opensearch-1-1-draft-6.md#opensearch-description-document
 */
class OpenSearch extends BaseModule
{
	/** @var IManageConfigValues */
	private $config;

	/** @var string */
	private $basePath;

	public function __construct(BasePath $basePath, IManageConfigValues $config, L10n $l10n, App\baseUrl $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->config   = $config;
		$this->basePath = $basePath->getPath();
	}

	/**
	 * @throws \Exception
	 */
	protected function rawContent(array $request = [])
	{
		/** @var DOMDocument $xml */
		XML::fromArray([
			'OpenSearchDescription' => [
				'@attributes' => [
					'xmlns' => 'http://a9.com/-/spec/opensearch/1.1/',
				],
				'ShortName'      => $this->baseUrl->getHost(),
				'Description'    => $this->l10n->t('Search in Friendica %s', $this->baseUrl->getHost()),
				'Contact'        => 'https://github.com/friendica/friendica/issues',
				'InputEncoding'  => 'UTF-8',
				'OutputEncoding' => 'UTF-8',
				'Developer'      => 'Friendica Developer Team',
			],
		], $xml);

		/** @var DOMElement $parent */
		$parent = $xml->getElementsByTagName('OpenSearchDescription')[0];

		if (file_exists($this->basePath . '/favicon.ico')) {
			$shortcut_icon = '/favicon.ico';
		} else {
			$shortcut_icon = $this->config->get('system', 'shortcut_icon');
		}

		if (!empty($shortcut_icon)) {
			$imagedata = getimagesize($this->baseUrl . $shortcut_icon);
		}

		if (!empty($imagedata)) {
			XML::addElement($xml, $parent, 'Image', $this->baseUrl . $shortcut_icon, [
				'width'  => $imagedata[0],
				'height' => $imagedata[1],
				'type'   => $imagedata['mime'],
			]);
		} else {
			XML::addElement($xml, $parent, 'Image',
			$this->baseUrl . '/images/friendica-16.png', [
				'height' => 16,
				'width'  => 16,
				'type'   => 'image/png',
			]);
		}

		XML::addElement($xml, $parent, 'Url', '', [
			'type'     => 'text/html',
			'method'   => 'get',
			'template' => $this->baseUrl . '/search?q={searchTerms}',
		]);

		XML::addElement($xml, $parent, 'Url', '', [
			'type'     => 'application/opensearchdescription+xml',
			'rel'      => 'self',
			'template' => $this->baseUrl . '/opensearch',
		]);

		$this->httpExit($xml->saveXML(), Response::TYPE_XML, 'application/opensearchdescription+xml');
	}
}
