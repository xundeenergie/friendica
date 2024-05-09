<?php
/**
 * @copyright Copyright (C) 2010-2024, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Module;

use Friendica\App;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Database\Database;
use Friendica\Model;
use Friendica\Network\HTTPException;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Security\PermissionSet\Repository\PermissionSet;
use Friendica\Util\ACLFormatter;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Outputs the permission tooltip HTML content for the provided item, photo or event id.
 */
class PermissionTooltip extends \Friendica\BaseModule
{
	private Database $dba;
	private ACLFormatter $aclFormatter;
	private IHandleUserSessions $session;
	private IManageConfigValues $config;
	private PermissionSet $permissionSet;

	public function __construct(PermissionSet $permissionSet, IManageConfigValues $config, IHandleUserSessions $session, ACLFormatter $aclFormatter, Database $dba, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->dba = $dba;
		$this->aclFormatter = $aclFormatter;
		$this->session = $session;
		$this->config = $config;
		$this->permissionSet = $permissionSet;
	}

	protected function rawContent(array $request = [])
	{
		$type = $this->parameters['type'];
		$referenceId = $this->parameters['id'];

		$expectedTypes = ['item', 'photo', 'event'];
		if (!in_array($type, $expectedTypes)) {
			throw new HTTPException\BadRequestException($this->t('Wrong type "%s", expected one of: %s', $type, implode(', ', $expectedTypes)));
		}

		$condition = ['id' => $referenceId, 'uid' => [0, $this->session->getLocalUserId()]];
		if ($type == 'item') {
			$fields = ['uid', 'psid', 'private', 'uri-id', 'origin', 'network'];
			$model = Model\Post::selectFirst($fields, $condition, ['order' => ['uid' => true]]);

			if ($model['origin'] || ($model['network'] != Protocol::ACTIVITYPUB)) {
				$permissionSet = $this->permissionSet->selectOneById($model['psid'], $model['uid']);
				$model['allow_cid'] = $permissionSet->allow_cid;
				$model['allow_gid'] = $permissionSet->allow_gid;
				$model['deny_cid']  = $permissionSet->deny_cid;
				$model['deny_gid']  = $permissionSet->deny_gid;
			} else {
				$model['allow_cid'] = [];
				$model['allow_gid'] = [];
				$model['deny_cid']  = [];
				$model['deny_gid']  = [];
			}
		} else {
			$fields = ['uid', 'allow_cid', 'allow_gid', 'deny_cid', 'deny_gid'];
			$model = $this->dba->selectFirst($type, $fields, $condition);
			$model['allow_cid'] = $this->aclFormatter->expand($model['allow_cid']);
			$model['allow_gid'] = $this->aclFormatter->expand($model['allow_gid']);
			$model['deny_cid']  = $this->aclFormatter->expand($model['deny_cid']);
			$model['deny_gid']  = $this->aclFormatter->expand($model['deny_gid']);
		}

		if (!$this->dba->isResult($model)) {
			throw new HttpException\NotFoundException($this->t('Model not found'));
		}

		// Kept for backwards compatibility
		Hook::callAll('lockview_content', $model);

		if ($type == 'item') {
			$receivers = $this->fetchReceivers($model['uri-id']);
			if (empty($receivers)) {
				switch ($model['private']) {
					case Model\Item::PUBLIC:
						$receivers = $this->t('Public');
						break;

					case Model\Item::UNLISTED:
						$receivers = $this->t('Unlisted');
						break;

					case Model\Item::PRIVATE:
						$receivers = $this->t('Limited/Private');
						break;
				}
			}
		} else {
			$receivers = '';
		}

		if (empty($model['allow_cid'])
			&& empty($model['allow_gid'])
			&& empty($model['deny_cid'])
			&& empty($model['deny_gid'])
			&& empty($receivers))
		{
			echo $this->t('Remote privacy information not available.');
			exit;
		}

		if (!empty($model['allow_cid']) || !empty($model['allow_gid']) || !empty($model['deny_cid']) || !empty($model['deny_gid'])) {
			$receivers = $this->fetchReceiversFromACL($model);
		}

		$this->httpExit($this->t('Visible to:') . '<br />' . $receivers);
	}

	/**
	 * Fetch a list of receivers based on the ACL data
	 * @throws \Exception
	 */
	private function fetchReceiversFromACL(array $model): string
	{
		$allowed_users   = $model['allow_cid'];
		$allowed_circles = $model['allow_gid'];
		$deny_users      = $model['deny_cid'];
		$deny_circles    = $model['deny_gid'];

		$l = [];

		if (count($allowed_circles)) {
			$key = array_search(Model\Circle::FOLLOWERS, $allowed_circles);
			if ($key !== false) {
				$l[] = '<b>' . $this->t('Followers') . '</b>';
				unset($allowed_circles[$key]);
			}

			$key = array_search(Model\Circle::MUTUALS, $allowed_circles);
			if ($key !== false) {
				$l[] = '<b>' . $this->t('Mutuals') . '</b>';
				unset($allowed_circles[$key]);
			}

			foreach ($this->dba->selectToArray('group', ['name'], ['id' => $allowed_circles]) as $circle) {
				$l[] = '<b>' . $circle['name'] . '</b>';
			}
		}

		foreach ($this->dba->selectToArray('contact', ['name'], ['id' => $allowed_users]) as $contact) {
			$l[] = $contact['name'];
		}

		if (count($deny_circles)) {
			$key = array_search(Model\Circle::FOLLOWERS, $deny_circles);
			if ($key !== false) {
				$l[] = '<b><strike>' . $this->t('Followers') . '</strike></b>';
				unset($deny_circles[$key]);
			}

			$key = array_search(Model\Circle::MUTUALS, $deny_circles);
			if ($key !== false) {
				$l[] = '<b><strike>' . $this->t('Mutuals') . '</strike></b>';
				unset($deny_circles[$key]);
			}

			foreach ($this->dba->selectToArray('group', ['name'], ['id' => $allowed_circles]) as $circle) {
				$l[] = '<b><strike>' . $circle['name'] . '</strike></b>';
			}
		}

		foreach ($this->dba->selectToArray('contact', ['name'], ['id' => $deny_users]) as $contact) {
			$l[] = '<strike>' . $contact['name'] . '</strike>';
		}

		return implode(', ', $l);
	}

	/**
	 * Fetch a list of receivers
	 * @throws InternalServerErrorException
	 */
	private function fetchReceivers(int $uriId): string
	{
		$own_url = '';
		$uid = $this->session->getLocalUserId();
		if ($uid) {
			$owner = Model\User::getOwnerDataById($uid);
			if (!empty($owner['url'])) {
				$own_url = $owner['url'];
			}
		}

		$receivers = [];
		foreach (Model\Tag::getByURIId($uriId, [Model\Tag::TO, Model\Tag::CC, Model\Tag::BCC, Model\Tag::AUDIENCE, Model\Tag::ATTRIBUTED]) as $receiver) {
			// We only display BCC when it contains the current user
			if (($receiver['type'] == Model\Tag::BCC) && ($receiver['url'] != $own_url)) {
				continue;
			}

			switch (Model\Tag::getTargetType($receiver['url'], false)) {
				case Model\Tag::PUBLIC_COLLECTION:
					$receivers[$receiver['type']][] = $this->t('Public');
					break;
				case Model\Tag::GENERAL_COLLECTION:
					$receivers[$receiver['type']][] = $this->t('Collection (%s)', $receiver['name']);
					break;
				case Model\Tag::FOLLOWER_COLLECTION:
					$apcontact = $this->dba->selectFirst('apcontact', ['name'], ['followers' => $receiver['url']]);
					$receivers[$receiver['type']][] = $this->t('Followers (%s)', $apcontact['name'] ?? $receiver['name']);
					break;
				case Model\Tag::ACCOUNT:
					$apcontact = Model\APContact::getByURL($receiver['url'], false);
					$receivers[$receiver['type']][] = $apcontact['name'] ?? $receiver['name'];
					break;
				default:
					$receivers[$receiver['type']][] = $receiver['name'];
					break;
			}
		}

		$output = '';

		foreach ($receivers as $type => $receiver) {
			$max = $this->config->get('system', 'max_receivers');
			$total = count($receiver);
			if ($total > $max) {
				$receiver = array_slice($receiver, 0, $max);
				$receiver[] = $this->t('%d more', $total - $max);
			}
			switch ($type) {
				case Model\Tag::TO:
					$output .= $this->t('<b>To:</b> %s<br>', implode(', ', $receiver));
					break;
				case Model\Tag::CC:
					$output .= $this->t('<b>CC:</b> %s<br>', implode(', ', $receiver));
					break;
				case Model\Tag::BCC:
					$output .= $this->t('<b>BCC:</b> %s<br>', implode(', ', $receiver));
					break;
				case Model\Tag::AUDIENCE:
					$output .= $this->t('<b>Audience:</b> %s<br>', implode(', ', $receiver));
					break;
				case Model\Tag::ATTRIBUTED:
					$output .= $this->t('<b>Attributed To:</b> %s<br>', implode(', ', $receiver));
					break;
			}
		}

		return $output;
	}
}
