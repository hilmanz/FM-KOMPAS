<div class="fbexpired">
	<div class="box tr last">
		<h3>Maaf, sesi login sudah habis,<br/> mohon login-ulang.</h3>
    <div id="loginContainer">
    <div id="login">
          <form method="post" action="<?=$this->Html->url('/login')?>" enctype="application/x-www-form-urlencoded">
              <div class="row">
                  <label>Email</label>
                  <input name="email" type="text">
              </div>
              <div class="row">
                  <label>Password</label>
                  <input name="password" type="password">
              </div>
              <div class="row">
                  <input value="Login" class="button" type="submit">
              </div>
          </form>
      </div>
      <div id="loginFB">
          <a href="javascript:fb_login();" class="boxButton loginFacebook">&nbsp;</a>
      </div>
  </div>
	</div><!-- end .box -->
</div>
<!-- -->
<script>
  window.fbAsyncInit = function() {
    // init the FB JS SDK
    FB.init({
      appId      : '<?=$FB_APP_ID?>',                        // App ID from the app dashboard
      channelUrl : '//<?=$DOMAIN?>/channel.html', // Channel file for x-domain comms
      status     : true,                                 // Check Facebook Login status
      xfbml      : true,                                  // Look for social plugins on the page
      cookie : true
    });

    // Additional initialization code such as adding Event Listeners goes here
    
  };

  // Load the SDK asynchronously
  (function(d, s, id){
     var js, fjs = d.getElementsByTagName(s)[0];
     if (d.getElementById(id)) {return;}
     js = d.createElement(s); js.id = id;
     js.src = "//connect.facebook.net/en_US/all.js";
     fjs.parentNode.insertBefore(js, fjs);
   }(document, 'script', 'facebook-jssdk'));
</script>


<script>
function fb_login(){
	FB.login(function(response) {
	   if (response.authResponse) {
	     console.log('Welcome!  Fetching your information.... ');
	     FB.api('/me', function(response) {
	       console.log('Good to see you, ' + response.name + '.');
	       document.location = "<?=$FB_AFTER_LOGIN_URL?>";
	     });
	   } else {
	     console.log('User cancelled login or did not fully authorize.');
	       document.location = "<?=$FB_AFTER_LOGIN_URL?>";
	   }
	 },{scope: 'email,user_location,user_birthday'});
	
}
</script>