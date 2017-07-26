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
                  <h1 class="red">Upgrade Member</h1>
                  <p>Lorem ipsum dolor sit amet...</p>
                  <p>
                      <?php if(!isset($rs['data'])): ?>
                        Tidak bisa terhubung dengan eCash Bank Mandiri
                        <a class="button" href="<?=$this->Html->url('/upgrade/member')?>">Refresh</a>
                      <?php elseif($rs['data'] == '#'): ?>
                        Tidak bisa terhubung dengan eCash Bank Mandiri
                        <a class="button" href="<?=$this->Html->url('/upgrade/member')?>">Refresh</a>
                      <?php else: ?>
                        <a class="button" href="<?=$rs['data']?>">Upgrade</a>
                      <?php endif; ?>
                  </p>
    			</div><!-- end .row-2 -->
			</div><!-- end .content -->
        </div><!-- end #content -->
	<div id="sidebar" class="tr">
	    
	    <div class="widget">
	        <div class="cash-left">
	        </div>
	    </div><!-- end .widget -->
       
	</div><!-- end #sidebar -->
    </div><!-- end #thecontent -->
</div><!-- end #fillDetailsPage -->