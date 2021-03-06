<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <title>Monitoring & Early Warning Systems for Landslides & Other Hazards</title>
    
    <link rel="icon" href="{{ URL::asset('assets/images/favicon.png') }}" type="image/x-icon" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />  
    <link rel="stylesheet" href="{{ asset('css/jquery-ui.css') }}">
    <link  href="{{ URL::asset('css/sb-admin.css') }}" rel="stylesheet">   
    <link rel="stylesheet" href="{{ URL::asset('assets/css/skin.css') }}">
    <link rel="stylesheet" href="{{ URL::asset('assets/css/responsive.css') }}">
    <link  href="{{ URL::asset('css/plugins/morris.css') }}" rel="stylesheet">  
    <link href="{{ URL::asset('font-awesome/css/font-awesome.min.css') }}" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="{{ URL::asset('assets/fonts/allfonts/stylesheet.css') }}">
    <link rel="stylesheet" href="{{ URL::asset('assets/dropzone/dropzone.css') }}">

	
	<link rel="stylesheet" href="{!! url('css/plugins/kendo-ui/kendo.common.min.css') !!}">
    <link rel="stylesheet" href="{!! url('css/plugins/kendo-ui/kendo.flat.min.css') !!}">
    <link rel="stylesheet" href="{!! url('css/plugins/kendo-ui/kendo.flat.mobile.min.css') !!}">
    <link rel="stylesheet" href="{!! url('css/plugins/kendo-ui/kendo.dataviz.flat.min.css') !!}">
	<link rel="stylesheet" href="{!! url('assets/css/sms.css') !!}">

</head>
<body class="bckclass">
    @if($currentUser->role_id == 1)

        @include('layouts.partials.nav')
        @include('layoutsmobile.partials.navrootmobile')

    @elseif($currentUser->role_id == 2)

        @include('layouts.partials.navadmin')
        @include('layoutsmobile.partials.navadmin')
    @elseif(($currentUser->role_id == 3) || ($currentUser->role_id == 4))

        @include('layouts.partials.navmdrrm')
        @include('layoutsmobile.partials.navlgumobile')
    @elseif($currentUser->role_id == 5)

        @include('layouts.partials.navuser')
        @include('layoutsmobile.partials.navusermobile')
    @endif
    <aside  id="wrapper">
        <div id="page-wrapper">
            <div class="container-fluid">
                @yield('page-content')
               
            </div>
        </div>
    </aside>

    <script src="{!! url('js/jquery1-11-3.min.js')!!}"></script> 
    <script>window.jQuery || document.write('<script src="{!!url('../../assets/js/vendor/jquery.min.js') !!}"><\/script>')</script>
    <script src="//code.jquery.com/jquery-1.12.3.js"></script>
    <script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>

    <script src="{!! url('js/jquery.js') !!}"></script>
    <script src="{!! url('js/bootstrap.min.js') !!}"></script>
	<script src="{!! url('js/moment.js') !!}"></script>
    <script src="{!! url('assets/js/notification.js')!!}"></script>
    <script type="text/javascript" src="{!! url('js/plugins/kendo-ui/kendo.all.min.js') !!}"></script>
    <script type="text/javascript" src="{!! url('js/plugins/kendo-ui/jszip.min.js') !!}"></script>
    <script type="text/javascript" src="{!! url('js/plugins/kendo-ui/pako_deflate.min.js') !!}"></script>
    <script type="text/javascript" src="{!! url('js/plugins/morris/raphael.min.js') !!}"></script>
    <script type="text/javascript" src="{!! url('js/plugins/morris/morris.js') !!}"></script>
    @yield('page-js-files')
</body>
</html>
