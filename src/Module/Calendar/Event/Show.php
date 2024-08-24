<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Calendar\Event;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Content\Feature;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\System;
use Friendica\Model\Event;
use Friendica\Model\User;
use Friendica\Module\Response;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Displays one specific event in a <div> container
 */
class Show extends BaseModule
{
	/** @var IHandleUserSessions */
	protected $session;
	/** @var App */
	private $app;

	public function __construct(App $app, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, IHandleUserSessions $session, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->session = $session;
		$this->app     = $app;
	}

	protected function rawContent(array $request = [])
	{
		$nickname = $this->parameters['nickname'] ?? $this->session->getLocalUserNickname();
		if (!$nickname) {
			throw new HTTPException\UnauthorizedException();
		}

		$owner = Event::getOwnerForNickname($nickname);

		$event = Event::getByIdAndUid($owner['uid'], (int)$this->parameters['id'] ?? 0);
		if (empty($event)) {
			throw new HTTPException\NotFoundException($this->t('Event not found.'));
		}

		$tplEvent = Event::prepareForItem($event);

		$event_item = [];
		foreach ($tplEvent['item'] as $k => $v) {
			$event_item[str_replace('-', '_', $k)] = $v;
		}
		$tplEvent['item'] = $event_item;

		$tpl = Renderer::getMarkupTemplate('calendar/event.tpl');

		$o = Renderer::replaceMacros($tpl, [
			'$event' => $tplEvent,
		]);

		$this->httpExit($o);
	}
}
