<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\ActivityPub;

use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\User;
use Friendica\Module\BaseApi;
use Friendica\Module\Special\HTTPException;
use Friendica\Protocol\ActivityPub;
use Friendica\Util\HTTPSignature;
use Friendica\Util\Network;
use Psr\Http\Message\ResponseInterface;

/**
 * ActivityPub Inbox
 */
class Inbox extends BaseApi
{
	public function run(HTTPException $httpException, array $request = [], bool $scopecheck = true): ResponseInterface
	{
		return parent::run($httpException, $request, false);
	}

	protected function rawContent(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_READ);
		$uid  = self::getCurrentUserID();
		$page = $request['page'] ?? null;

		if (empty($page) && empty($request['max_id'])) {
			$page = 1;
		}

		if (!empty($this->parameters['nickname'])) {
			$owner = User::getOwnerDataByNick($this->parameters['nickname']);
			if (empty($owner)) {
				throw new \Friendica\Network\HTTPException\NotFoundException();
			}
			if ($owner['uid'] != $uid) {
				throw new \Friendica\Network\HTTPException\ForbiddenException();
			}
			$inbox = ActivityPub\ClientToServer::getInbox($uid, $page, $request['max_id'] ?? null);
		} else {
			$inbox = ActivityPub\ClientToServer::getPublicInbox($uid, $page, $request['max_id'] ?? null);
		}

		$this->jsonExit($inbox, 'application/activity+json');
	}

	protected function post(array $request = [])
	{
		    // Roh-Body auslesen
		    // Roh-Body auslesen
    $raw = file_get_contents('php://input');

    // JSON decodieren
    $data = json_decode($raw, true);

    // Pretty Print oder fallback
    if ($data === null) {
        $pretty = $raw;
    } else {
        $pretty = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    // User-ID aus Parametern ermitteln (falls vorhanden)
   // $user_id = isset($parameters['uid']) ? $parameters['uid'] : 'unknown';
    // Actor extrahieren
    $actor_uri = $data['actor'] ?? 'unknown';

    // URL zerlegen
    $parsed = parse_url($actor_uri);
    $domain = $parsed['host'] ?? 'unknown';

    // letzten Pfadteil als Username
    $path_parts = explode('/', rtrim($parsed['path'] ?? '', '/'));
    $username = end($path_parts) ?: 'unknown';

    // Verzeichnis: /tmp/inbox_actors/{domain}/{username}
    $dir = "/var/www/soc.schuerz.at/log/ap_inbox_from_{$domain}/{$username}";
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    // Dateiname mit Timestamp
    $timestamp = date('Ymd_His');
    $aplogfilename = "{$dir}/activity_{$timestamp}.json";


    // Inbox URL (falls bekannt)
    $inbox_url = isset($parameters['inbox_url']) ? $parameters['inbox_url'] : 'unknown';

    // Inhalt für die Datei
    $content = "User-ID: {$user_id}\nInbox-URL: {$inbox_url}\n\n{$pretty}\n";
    // Inhalt für die Datei
    $content = "Actor: {$actor_uri}\nInbox URL: {$_SERVER['REQUEST_URI']}\n\n{$pretty}\n";

    // Schreiben
    file_put_contents($aplogfilename, $content, LOCK_EX);

		$postdata = Network::postdata();

		if (empty($postdata)) {
			throw new \Friendica\Network\HTTPException\BadRequestException();
		}

		if (!HTTPSignature::isValidContentType($this->server['CONTENT_TYPE'] ?? '')) {
			$this->logger->notice('Unexpected content type', ['content-type' => $this->server['CONTENT_TYPE'] ?? '', 'agent' => $this->server['HTTP_USER_AGENT'] ?? '']);
			throw new \Friendica\Network\HTTPException\UnsupportedMediaTypeException();
		}

		if (DI::config()->get('debug', 'ap_inbox_log')) {
			if (HTTPSignature::getSigner($postdata, $_SERVER)) {
				$filename = 'signed-activitypub';
			} else {
				$filename = 'failed-activitypub';
			}
			$tempfile = tempnam(System::getTempPath(), $filename);
			file_put_contents($tempfile, json_encode(['parameters' => $this->parameters, 'header' => $_SERVER, 'body' => $postdata], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
			$this->logger->notice('Incoming message stored', ['file' => $tempfile]);
		}

		if (!empty($this->parameters['nickname'])) {
			$user = DBA::selectFirst('user', ['uid'], ['nickname' => $this->parameters['nickname']]);
			if (!DBA::isResult($user)) {
				throw new \Friendica\Network\HTTPException\NotFoundException();
			}
			$uid = $user['uid'];
		} else {
			$uid = 0;
		}

		Item::incrementInbound(Protocol::ACTIVITYPUB);
		ActivityPub\Receiver::processInbox($postdata, $_SERVER, $uid);

		throw new \Friendica\Network\HTTPException\AcceptedException();
	}
}
