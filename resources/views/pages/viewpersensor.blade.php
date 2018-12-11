@extends('layouts.masters.other-layouts')
@section('page-content')
<div class="row">

	<div class="col-xs-12">
		<h1 class="page-header">{{ $sensor->address }}</h1>
	</div>
</div>
<div class="row">
	<div class="col-xs-12">
		<a href="{{action('HydrometController@viewHydrometdata')}}" class="btnback btn" title="Back to Sensors"><i class="fa fa-level-up" aria-hidden="true"></i></a>
		
	</div>
</div>

<div id="persensorchart"></div>

 @stop
@section('page-js')
<script src="{!! url('assets/js/sensorschart.js')!!}"></script>
@stop