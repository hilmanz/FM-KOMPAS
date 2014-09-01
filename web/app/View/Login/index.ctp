<div id="loginContainer">
    <div  id="login" class="widgets tr">
        <h4>Login dengan email</h4>
        <?php echo $this->Session->flash();?>
        <form method="post" action="<?=$this->Html->url('/login');?>" enctype="application/x-www-form-urlencoded">
            <div class="row">
                <label>Email</label>
                <input type="text" name="email" >
            </div>
            <div class="row">
                <label>Password</label>
                <input type="password" name="password" >
                <a href="<?=$this->Html->url("/profile/forgot_password")?>">Lupa Password ?</a>
            </div>
            <div class="row-last">
                <input value="Login" class="button" type="submit">
            </div>
            <p>Belum punya akun ? Klik <a href="<?=$this->Html->url('/login/register')?>">disini</a> untuk mendaftar</p>
        </form>
    </div>
    <div  id="loginFB"  class="widgets tr">
        <h4>Atau lo bisa login dengan menggunakan facebook</h4>
        <a href="javascript:fb_login();" class="boxButton loginFacebook">&nbsp;</a>
    </div>
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
	    
	     FB.api('/me', function(response) {
	       console.log('Good to see you, ' + response.name + '.');
           window.location = window.location;
	       document.location = "<?=$FB_AFTER_LOGIN_URL?>";
	     });
	   } else {
	     
	       document.location = "<?=$FB_AFTER_LOGIN_URL?>";
	   }
	 },{scope: 'email,user_location,user_birthday'});
	
}
</script>