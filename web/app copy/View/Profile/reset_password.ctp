<div class="tr" style="width:450px;" >
	<?php echo $this->Session->flash(); ?>
	<form method="post" action="<?=$this->Html->url('/profile/reset_password')?>" 
		enctype="application/x-www-form-urlencoded">
		<div class="row">
	        <label>Password Baru</label>
	        <input type="password" name="password" >
	    </div>
	    <div class="row">
	        <label>Ulangi Password</label>
	        <input type="password" name="password_repeat" >
	    </div>
	    <input type="hidden" name="trxcode" value="<?=$trxcode?>" />
	    <div class="row">
	        <input value="Submit" class="button" type="submit">
	    </div>
    </form>
</div><!-- -->