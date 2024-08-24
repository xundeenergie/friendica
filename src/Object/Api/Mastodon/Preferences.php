<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object\Api\Mastodon;

use Friendica\App\BaseURL;
use Friendica\BaseDataTransferObject;

/**
 * Class Preferences
 *
 * @see https://docs.joinmastodon.org/entities/preferences/
 */
class Preferences extends BaseDataTransferObject
{
//	/** @var string (Enumerable, oneOf) */
//	protected $posting_default_visibility;
//	/** @var bool */
//	protected $posting_default_sensitive;
//	/** @var string (ISO 639-1 language two-letter code), or null*/
//	protected $posting_default_language;
//	/** @var string (Enumerable, oneOf) */
//	protected $reading_expand_media;
//	/** @var bool */
//	protected $reading_expand_spoilers;

	/**
	 * Creates a preferences record.
	 *
	 * @param BaseURL $baseUrl
	 * @param array   $publicContact Full contact table record with uid = 0
	 * @param array   $apcontact     Optional full apcontact table record
	 * @param array   $userContact   Optional full contact table record with uid != 0
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function __construct(string $visibility, bool $sensitive, string $language, string $media, bool $spoilers)
	{
		$this->{'posting:default:visibility'} = $visibility;
		$this->{'posting:default:sensitive'}  = $sensitive;
		$this->{'posting:default:language'}   = $language;
		$this->{'reading:expand:media'}       = $media;
		$this->{'reading:expand:spoilers'}    = $spoilers;
	}
}
