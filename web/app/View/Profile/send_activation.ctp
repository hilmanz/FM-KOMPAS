<div id="boxgrey" >
	<?php echo $this->Session->flash(); ?>
	Anda harus mengaktifkan akun anda dengan mengirimkan kode aktivasi ke email yang valid
	<form method="post" action="<?=$this->Html->url('/profile/send_activation')?>" 
		enctype="application/x-www-form-urlencoded">
		<div class="row">
	        <label>Email Anda</label>
	        <input type="text" name="email" value="<?=@$user_data['email']?>" >
	    </div>
	    <div class="row">
	        <input value="Kirim Kode Aktivasi" class="button" type="submit">
	    </div>
    </form>
</div><!-- -->