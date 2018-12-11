<div class="loginform">
  <div class="container-fluid">
      <nav class="navbar hidden-xs navbar-default" id="login-nav">        
      @if( !$currentUser )
        <ul class="nav navbar-nav navbar-right">    
          <li class="login">{!! link_to_route('get_login', 'Login') !!}</li>
          <li class="register">{!! link_to_route('get_register', 'Register') !!}</li>          
        </ul>
      @else
      <ul class="nav navbar-nav navbar-left"> 
       <li><a href="{{ action("HydrometController@dashboard") }}"><i class="fa fa-tachometer"></i> Dashboard</a></li>
      </ul>
      <ul class="nav navbar-nav navbar-right">    
       <li>{!! link_to_route('get_logout', 'Log out') !!}</li>
       </ul>
         @endif  
         
      </nav>
  </div>
</div>