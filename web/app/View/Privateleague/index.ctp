<div id="fillDetailsPage">
      <div class="rowd">
     	 <?php echo $this->element('infobar'); ?>
      </div>
      <div class="rowd">
       
    <div id="thecontent">
        <?=$this->Session->flash();?>
        <div id="content">
        	<div class="content">
            	<div class="row-2">
                    <h1 class="red">Create Private League</h1>
                    <p>Tampilan informasi seputar profil Fantasy Football League Anda. Selain melihat statistik personal, Anda juga dapat mengubah info dan foto kapan saja.</p>
    			</div><!-- end .row-2 -->
                <form class="theForm" action="<?=$this->Html->url('/profile/update')?>" 
                  enctype="multipart/form-data" method="post">
                    <div class="tr avatarBox">
                        <div class="avatar-big">
                           
                            <?php if(strlen($user['avatar_img'])==0 || $user['avatar_img']=='0'):?>
                            <img src="http://graph.facebook.com/<?=$USER_DATA['fb_id']?>/picture" />
                            <?php else:?>
                            <img src="<?=$this->Html->url('/files/120x120_'.$user['avatar_img'])?>" />
                            <?php endif;?>
                        </div>

                        <a href="#popup-upload" class="button" id="btn_upload">Ganti Logo Klab</a>

                    </div>
                    <div class="row">
                        <label>Nama Lengkap</label>
                        <input type="text" name="name" value="<?=h($user['name'])?>" maxlength="30"/>
                    </div><!-- end .row -->
                    <div class="row">
                        <label>Nama Klab</label>
                        <input maxlength="20" type="text" name="team_name" value="<?=h($team['team_name'])?>"/>
                    </div><!-- end .row -->
                    <div class="row">
                        <label>Email</label>
                        <input type="text" name="email" value="<?=h($user['email'])?>" READONLY/>
                    </div><!-- end .row -->
                    <div class="row">
                        <label>Nomor HP</label>
                        <input type="text" name="phone_number" value="<?=h(@$user['phone_number'])?>"/>
                    </div><!-- end .row -->
                    <div class="row">
                        <label>Lokasi</label>
                        <input type="text" name="location" value="<?=h(@$user['location'])?>"/>
                    </div><!-- end .row -->
                   
                    <div class="row">
                        <input type="hidden" name="save" value="1"/>
                        <input type="submit" value="Simpan Perubahan" class="button" />
                    </div><!-- end .row -->
                </form>
			</div><!-- end .content -->
        </div><!-- end #content -->
	<div id="sidebar" class="tr">
	    
	    <div class="widget">
	        <div class="cash-left">
	            <h3 class="red">SISA BUDGET</h3>
	            <h1>ss$ <?=number_format($team_bugdet)?></h1>
	            <h3 class="red">JUMLAH POINT</h3>
	            <h1><?=number_format($USER_POINTS)?> pts</h1> 
                <h3 class="red">JUMLAH COINS</h3>
                <h1><?=number_format($USER_COINS)?></h1> 
                <h3 class="red">PERINGKAT SAAT INI</h3>
                <h1><?=number_format($USER_RANK)?></h1> 
	        </div>
	    </div><!-- end .widget -->
       
	</div><!-- end #sidebar -->
    </div><!-- end #thecontent -->
</div><!-- end #fillDetailsPage -->