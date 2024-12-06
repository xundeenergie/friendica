<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica;

use DateTime;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Psr\Http\Message\UriInterface;

/**
 * The Entity classes directly inheriting from this abstract class are meant to represent a single business entity.
 * Their properties may or may not correspond with the database fields of the table we use to represent it.
 * Each model method must correspond to a business action being performed on this entity.
 * Only these methods will be allowed to alter the model data.
 *
 * To persist such a model, the associated Repository must be instantiated and the "save" method must be called
 * and passed the entity as a parameter.
 *
 * Ideally, the constructor should only be called in the associated Factory which will instantiate entities depending
 * on the provided data.
 *
 * Since these objects aren't meant to be using any dependency, including logging, unit tests can and must be
 * written for each and all of their methods
 *
 * @property-read int|null $id
 * @property-read int $uid
 * @property-read string $verb
 * @property-read int|string $type
 * @property-read int $actorId
 * @property-read int $targetUriId
 * @property-read int|null $parentUriId
 * @property-read \DateTime|string $created
 * @property-read bool $seen
 * @property-read bool $dismissed
 * @property-read string $name
 * @property-read UriInterface $url
 * @property-read UriInterface $photo
 * @property-read DateTime $date
 * @property-read string|null $msg
 * @property-read UriInterface $link
 * @property-read int|null $itemId
 * @property-read int|null $parent
 * @property-read string|null $otype
 * @property-read string|null $name_cache
 * @property-read string|null $msg_cache
 * @property-read int|null $uriId
 * @property-read string $cookie_hash
 * @property-read string $user_agent
 * @property-read bool $trusted
 * @property-read string|null $last_used
 * @property-read string $guid
 * @property-read string $addr
 * @property-read UriInterface $alias
 * @property-read string $nick
 * @property-read string $givenName
 * @property-read string $familyName
 * @property-read UriInterface $photoMedium
 * @property-read UriInterface $photoSmall
 * @property-read UriInterface $batch
 * @property-read UriInterface $notify
 * @property-read UriInterface $poll
 * @property-read string $subscribe
 * @property-read bool $searchable
 * @property-read string $pubKey
 * @property-read UriInterface $baseurl
 * @property-read int $gsid
 * @property-read \DateTime $updated
 * @property-read int $interacting_count
 * @property-read int $interacted_count
 * @property-read int $post_count
 */
abstract class BaseEntity extends BaseDataTransferObject
{
	/**
	 * @param string $name
	 * @return mixed
	 * @throws InternalServerErrorException
	 */
	public function __get(string $name)
	{
		if (!property_exists($this, $name)) {
			throw new InternalServerErrorException('Unknown property ' . $name . ' in Entity ' . static::class);
		}

		return $this->$name;
	}

	/**
	 * @param mixed $name
	 * @return bool
	 * @throws InternalServerErrorException
	 */
	public function __isset($name): bool
	{
		if (!property_exists($this, $name)) {
			throw new InternalServerErrorException('Unknown property ' . $name . ' of type ' . gettype($name) . ' in Entity ' . static::class);
		}

		return !empty($this->$name);
	}
}
