<div id='adminpage'>
	<h1>{{$title}} - {{$page}} ({{$count}})</h1>
	
	<p>{{$info}}</p>
	<table>
		<tr>
			<th>{{$id_header}}</th>
			<th>{{$command_header}}</th>
			<th>{{$param_header}}</th>
			<th>{{$created_header}}</th>
			<th>{{$next_try_header}}</th>
			<th>{{$prio_header}}</th>
		</tr>
		{{foreach $entries as $e}}
		<tr>
			<td>{{$e.id}}</td>
			<td>{{$e.command}}</td>
			<td>{{$e.parameter}}</td>
			<td>{{$e.created}}</td>
			<td>{{$e.next_try}}</td>
			<td>{{$e.priority}}</td>
		</tr>
		{{/foreach}}
	</table>
</div>
