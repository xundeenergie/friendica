{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<div id="sidebar-calendar" class="widget">
	<h3>{{$etitle}}</h3>

	<ul class="sidebar-calendar-export-ul">
		<li role="menuitem" class="sidebar-calendar-export-li"><a href="calendar/export/{{$user}}/ical">{{$export_ical}}</a></li>
		<li role="menuitem" class="sidebar-calendar-export-li"><a href="calendar/export/{{$user}}/csv">{{$export_csv}}</a></li>
	</ul>
</div>
