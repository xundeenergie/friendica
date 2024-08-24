{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
{{if $image->preview}}
<a data-fancybox="{{$image->uriId}}" href="{{$image->url}}"><img src="{{$image->preview}}" alt="{{$image->description}}" title="{{$image->description}}" loading="lazy"></a>
{{else}}
<figure>
	<img src="{{$image->url}}" alt="{{$image->description}}" title="{{$image->description}}" loading="lazy">
	{{if $image->description}}
	<figcaption>{{$image->description}}</figcaption>
    {{/if}}
</figure>
{{/if}}
