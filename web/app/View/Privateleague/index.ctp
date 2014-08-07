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
                    <h1 class="red">Daftar Private League</h1>
                    <p>Lorem ipsum dolor sit amet...</p>
                    <a class="button fr" href="<?=$this->Html->url('/privateleague/create')?>">
                        Create Private League
                    </a>
    			</div><!-- end .row-2 -->
                <table class="theTable footable" width="100%" cellpadding="0" cellspacing="0" border="0">
                    <thead>
                        <tr>
                            <th data-class="expand">No.</th>
                            <th>Nama Liga</th>
                            <th data-hide="phone,tablet">Max Player</th>
                            <th data-hide="phone,tablet">Invited Player</th>
                            <th data-hide="phone,tablet">Joined Player</th>
                            <th data-hide="phone,tablet">&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($rs) > 0): ?>
                            <?php $i=1; foreach ($rs as $key => $value): ?>
                                <tr>
                                    <td><?=$i?></td>
                                    <td><?=$value['b']['name']?></td>
                                    <td><?=$value['b']['max_player']?></td>
                                    <td><?=$value['a']['total_invited']?></td>
                                    <td><?=$value['a']['total_joined']?></td>
                                    <td>
                                        <a class="button" 
                                        href="<?=$this->Html->url('/privateleague/leaderboard')?>">
                                            Peringkat
                                        </a>
                                    </td>
                                </tr>
                            <?php $i++; endforeach; ?>
                        <?php else: ?>
                            <tr class="odd">
                                <td colspan="5">Loe belom punya private league.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </form>
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