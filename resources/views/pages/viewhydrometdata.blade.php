@extends('layouts.masters.backend-layout')
@section('page-content')
    <div class="row">
        <div id="dashboard" class="col-xs-12">
            <h1 class="page-header">Hydromet Data</h1>
        </div>
        <div class="col-xs-12 filtersection" style="margin-bottom:10px;">
        	<div class="col-xs-12 col-sm-2 filtertable np">
        		<select class="form-control" id="filterstatus">
        			<option value="">All Status</option>
        			<option value="with_data">Working</option>
        			<option value="no_data">Not Working</option>
        		</select>
        	</div>
        	<div class="col-xs-12 col-sm-2 filtersensor">
        		<select class="form-control">
        			<option value="rain">Rain Gauge</option>
        			<option value="stream">Waterlevel</option>
        		</select>
        	</div>
        	<div class="col-xs-12 col-sm-4 searchhydro">
				<div class="input-group">				  
				  <input class="form-control" id="hydrometsearch" type="text" name="searchall" placeholder="Search">
				  <span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-search"></span></span>
				</div>
			</div>
        </div>
        <form action="{{ action('HydrometController@Filterdata')}}">
        
	        <div class="col-xs-12  col-sm-12">
	         	<table class="table table-hover tbldashboard"  id="hydromettable">
    				<thead>	
    					
    				</thead>
    				<tbody>	
    			
    				</tbody>
    			</table>
	        </div>
        </form>
    </div>


@stop
@section('page-js-files')
<script src="{{ asset('assets/js/viewsensor.js') }}"></script>

@stop
