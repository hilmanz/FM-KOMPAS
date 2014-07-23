<?php
$can_update_formation = true;

if(isset($close_time)):

if(time() > @$close_time['ts'] && Configure::read('debug') == 0){
    $can_update_formation = false;
    if(time() > $open_time){
     
        $can_update_formation = true;
    }
}else{
    if(time() < @$open_time){
       
        $can_update_formation = false;
    }
}
endif;

if($is_new_user){
    $can_update_formation = true;
}

?>



<?php
 $home_logo = "http://widgets-images.s3.amazonaws.com/football/team/badges_65/".str_replace('t','',@$next_match['home_id']).".png";
  $away_logo = "http://widgets-images.s3.amazonaws.com/football/team/badges_65/".str_replace('t','',@$next_match['away_id']).".png";

if(strlen(@$user['avatar_img'])!=0 && @$user['avatar_img']!='0'){
    if($next_match['home_id']==$club['team_id']){
        $home_logo = $this->Html->url('/files/120x120_'.@$user['avatar_img']);
    }else{
       $away_logo = $this->Html->url('/files/120x120_'.@$user['avatar_img']);
    }
}
?>
<div id="fillDetailsPage">
	<h1 class="fake-title">Mengelola Tim</h1>
      <div class="rowd">
     	 <?php echo $this->element('infobar',array('next_match'=>$next_match)); ?>
      </div>
    <div id="thecontent">
        <div class="box4 fl" id="bannerBox">
           
        	<div class="banner250x250">
			<?=$this->element('sponsor_banner',array('slot'=>'TEAM_SMALL','game_team_id'=>$game_team_id));?>
            </div>
            <?php for($i=0;$i<sizeof($sidebar_banner);$i++):?>
        	<div class="banner250x250">
                <a 
                href="<?=$this->Html->url('/sponsors/jump/1/'.$sidebar_banner[$i]['Banners']['id'])?>" 
                    target="_blank">
                    <img src="<?=$this->Html->url(Configure::read('avatar_web_url').
                                $sidebar_banner[$i]['Banners']['banner_file'])?>" />
                </a>
            </div>
            <?php endfor;?>
        	
            
        </div><!-- end .box4 -->
        <div class="box3 tr fl" id="formationBox">
            <h3>
                Set Up Tactics for Matchday <?=$next_match['matchday']?>
            </h3>

            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                <thead>
                    <tr>
                        <th width="160px" align="center">Pemain</th>
                        <th class="aligncenter" width="60" align="center">Instruction</th>
                        <th class="aligncenter" width="1" align="center">Amount</th>
                    </tr>
                </thead>
                <tbody id="myplayerlist">
                    <?php for($i=0;$i<sizeof($lineup);$i++):?>
                    <tr>
                        <td><?=$lineup[$i]['name']?></td>
                        <td>
                            <select name="instruction">
                                <option value="0">Tidak Ada</option>
                                <option value="1">More Shoots</option>
                                <option value="2">More Crosses</option>
                                <option value="3">Focus on Through Ball</option>
                                <option value="4">Create Chances</option>
                                <option value="5">More Tackles</option>
                                <option value="6">More Aerial Defense</option>
                                <option value="7">More Blocks</option>
                                <option value="8">More Hard Challenges</option>
                            </select>
                        </td>
                        <td>
                            <input type="text" name="points" value="0"/>
                        </td>
                    </tr>
                    <?php endfor;?>
                </tbody>
            </table>
            <a href="<?=$this->Html->url('/manage/team')?>" class="button">KEMBALI KE FORMASI</a><a href="#" class="button">SIMPAN TAKTIK</a>
        </div><!-- end .box3 -->
        <div class="box4 fr" id="boxSquad">
            <div class="widget tr squad-team-name">
                <h3><?=h($club['team_name'])?></h3>
                <div class="RingkasanKlab" id="RingkasanKlab">
                    <div class="RingkasanKlabWidget">
                        <table width="100%" border="0" cellspacing="0" cellpadding="0">
                          <tr>
                            <td align="center">
                                <a href="#">
                                <?php if(strlen(@$user['avatar_img'])==0 || @$user['avatar_img']=='0'):?>
                                <img src="http://widgets-images.s3.amazonaws.com/football/team/badges_65/<?=str_replace('t','',$club['team_id'])?>.png"/>
                                <?php else:?>
                                <img width="65" src="<?=$this->Html->url('/files/120x120_'.@$user['avatar_img'])?>" />
                                <?php endif;?>
                             </a>
                            </td>
                            <td>
                                <span>Rank: <strong><?=number_format($USER_RANK)?></strong></span>
                                <span>ss$: <strong><?=number_format($team_bugdet)?> </strong></span>
                                <span>Point: <strong><?=number_format($USER_POINTS)?></strong></span>
                            </td>
                          </tr>
                        </table>
                    </div><!-- end .entry -->
                </div><!-- end .widget -->

            </div><!-- end .widget -->
            <div class="widget tr squad-team-name">
                <h3>INSTRUCTION POINTS <span class="yellow">10</span></h3>
            </div><!-- end .widget -->
        </div><!-- end .box4 -->
    </div><!-- end #thecontent -->
</div><!-- end #fillDetailsPage -->
<div id="draggable" class="jersey-player" style="display:none;position:absolute;">
</div>
<!--popups-->
<div class="popup">
    <div class="popupContainer popup-small" id="popup-messages">
        <div class="popupHeader">
        </div><!-- END .popupHeader -->
        <div class="popupContent">
            <div class="entry-popup">
                
            </div><!--END .entry-popup-->
        </div><!-- END .popupContent -->
    </div><!-- END .popupContainer -->
</div><!-- END .popup --> 

