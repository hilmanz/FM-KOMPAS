<div class="tr" style="width:450px;" >
	<?php echo $this->Session->flash(); ?>
	Kode Aktivasi Telah dikirim ke email Anda <?=$user_data['email']?><br />
	Silahkan buka email
	<form method="post" action="<?=$this->Html->url('/profile/activation')?>" 
		enctype="application/x-www-form-urlencoded">
		<div class="row">
	        <label>Activation Code</label>
	        <input type="text" name="act_code" >
	    </div>
	    <div class="row">
	        <input value="Submit" class="button" type="submit">
	    </div>
    </form>
</div><!-- -->