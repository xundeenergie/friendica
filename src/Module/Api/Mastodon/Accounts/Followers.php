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

namespace Friendica\Module\Api\Mastodon\Accounts;

use Friendica\Content\Widget;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Module\BaseApi;

/**
 * @see https://docs.joinmastodon.org/methods/accounts/
 */
class Followers extends BaseApi
{
	/**
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_READ);
		$uid = self::getCurrentUserID();

		if (empty($this->parameters['id'])) {
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
		}

		$id = $this->parameters['id'];
		if (!DBA::exists('contact', ['id' => $id, 'uid' => 0])) {
			$this->logAndJsonError(404, $this->errorFactory->RecordNotFound());
		}

		$request = $this->getRequest([
			'max_id'   => 0,  // Return results older than this id
			'since_id' => 0,  // Return results newer than this id
			'min_id'   => 0,  // Return results immediately newer than id
			'limit'    => 40, // Maximum number of results to return. Defaults to 40.
		], $request);

		if ($id == Contact::getPublicIdByUserId($uid)) {
			$params = ['order' => ['pid' => true], 'limit' => $request['limit']];

			$condition = ['uid' => $uid, 'self' => false, 'rel' => [Contact::FOLLOWER, Contact::FRIEND]];

			if (!empty($request['max_id'])) {
				$condition = DBA::mergeConditions($condition, ["`pid` < ?", $request['max_id']]);
			}

			if (!empty($request['since_id'])) {
				$condition = DBA::mergeConditions($condition, ["`pid` > ?", $request['since_id']]);
			}

			if (!empty($request['min_id'])) {
				$condition = DBA::mergeConditions($condition, ["`pid` > ?", $request['min_id']]);

				$params['order'] = ['pid'];
			}

			$networks  = Widget::unavailableNetworks();
			$condition = DBA::mergeConditions($condition, array_merge(["NOT `network` IN (" . substr(str_repeat("?, ", count($networks)), 0, -2) . ")"], $networks));

			$accounts = [];

			foreach (Contact::selectAccountToArray(['pid'], $condition, $params) as $follower) {
				self::setBoundaries($follower['pid']);
				$accounts[] = DI::mstdnAccount()->createFromContactId($follower['pid'], $uid);
			}
		} else {
			$params = ['order' => ['relation-cid' => true], 'limit' => $request['limit']];

			$condition = ['cid' => $id, 'follows' => true];

			if (!empty($request['max_id'])) {
				$condition = DBA::mergeConditions($condition, ["`relation-cid` < ?", $request['max_id']]);
			}

			if (!empty($request['since_id'])) {
				$condition = DBA::mergeConditions($condition, ["`relation-cid` > ?", $request['since_id']]);
			}

			if (!empty($request['min_id'])) {
				$condition = DBA::mergeConditions($condition, ["`relation-cid` > ?", $request['min_id']]);

				$params['order'] = ['relation-cid'];
			}

			$accounts = [];

			$followers = DBA::select('contact-relation', ['relation-cid'], $condition, $params);
			while ($follower = DBA::fetch($followers)) {
				self::setBoundaries($follower['relation-cid']);
				$accounts[] = DI::mstdnAccount()->createFromContactId($follower['relation-cid'], $uid);
			}
			DBA::close($followers);
		}

		if (!empty($request['min_id'])) {
			$accounts = array_reverse($accounts);
		}

		self::setLinkHeader();
		$this->jsonExit($accounts);
	}
}
