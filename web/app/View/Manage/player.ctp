<script>
function profileLoaded(widget, data, id){
    $('.player-detail .opta-widget-container h2 span').html('Player Profile');
    $(".opta-widget-container div.profile-container div.profile dl").find('dt').each(
        function(k,item){
            if($(item).html()=='Name'){
                $(item).next().remove();
                $(item).remove();
            }
        });
}
_optaParams.callbacks = [profileLoaded];
var club_url = "<?=$this->Html->url('/manage/club/?rooster=1')?>";
<?php
if(isset($data['daily_stats'])):
?>
var daily_stats = JSON.parse('<?=json_encode($data['daily_stats'])?>');
<?php
else:
?>
var daily_stats = {};
<?php endif;?>

</script>
<?php
require_once APP . 'Vendor' . DS. 'stats.locale.php';
//mapping statistics
$games = array(
        'game_started'=>'game_started',
        'sub_on'=>'total_sub_on'
    );

$passing_and_attacking = array(
        'Freekick Goal'=>'att_freekick_goal',
        'Goal inside the box'=>'att_ibox_goal',
        'Goal Outside the Box'=>'att_obox_goal',
        'Penalty Goal'=>'att_pen_goal',
        'Freekick Shots'=>'att_freekick_post',
        'On Target Scoring Attempt'=>'ontarget_scoring_att',
        'Shot From Outside the Box'=>'att_obox_target',
        'big_chance_created'=>'big_chance_created',
        'big_chance_scored'=>'big_chance_scored',
        'goal_assist'=>'goal_assist',
        'total_assist_attempt'=>'total_att_assist',
        'Second Goal Assist'=>'second_goal_assist',
        'final_third_entries'=>'final_third_entries',
        'fouled_final_third'=>'fouled_final_third',
        'pen_area_entries'=>'pen_area_entries',
        'won_contest'=>'won_contest',
        'won_corners'=>'won_corners',
        'penalty_won'=>'penalty_won',
        'last_man_contest'=>'last_man_contest',
        'accurate_corners_intobox'=>'accurate_corners_intobox',
        'accurate_cross_nocorner'=>'accurate_cross_nocorner',
        'accurate_freekick_cross'=>'accurate_freekick_cross',
        'accurate_launches'=>'accurate_launches',
        'long_pass_own_to_opp_success'=>'long_pass_own_to_opp_success',
        'successful_final_third_passes'=>'successful_final_third_passes',
        'accurate_flick_on'=>'accurate_flick_on'
    );


$defending = array(
        'aerial_won'=>'aerial_won',
        'ball_recovery'=>'ball_recovery',
        'duel_won'=>'duel_won',
        'effective_blocked_cross'=>'effective_blocked_cross',
        'effective_clearance'=>'effective_clearance',
        'effective_head_clearance'=>'effective_head_clearance',
        'interceptions_in_box'=>'interceptions_in_box',
        'interception_won' => 'interception_won',
        'possession_won_def_3rd' => 'poss_won_def_3rd',
        'possession_won_mid_3rd' => 'poss_won_mid_3rd',
        'possession_won_att_3rd' => 'poss_won_att_3rd',
        'won_tackle' => 'won_tackle',
        'offside_provoked' => 'offside_provoked',
        'last_man_tackle' => 'last_man_tackle',
        'outfielder_block' => 'outfielder_block'
    );

$goalkeeper = array(
                'dive_catch'=> 'dive_catch',
                'dive_save'=> 'dive_save',
                'stand_catch'=> 'stand_catch',
                'stand_save'=> 'stand_save',
                'cross_not_claimed'=> 'cross_not_claimed',
                'good_high_claim'=> 'good_high_claim',
                'punches'=> 'punches',
                'good_one_on_one'=> 'good_one_on_one',
                'accurate_keeper_sweeper'=> 'accurate_keeper_sweeper',
                'gk_smother'=> 'gk_smother',
                'saves'=> 'saves',
                'goals_conceded'=>'goals_conceded'
                    );


$mistakes_and_errors = array(
            'penalty_conceded'=>'penalty_conceded',
            'red_card'=>'red_card',
            'yellow_card'=>'yellow_card',
            'challenge_lost'=>'challenge_lost',
            'dispossessed'=>'dispossessed',
            'fouls'=>'fouls',
            'overrun'=>'overrun',
            'total_offside'=>'total_offside',
            'unsuccessful_touch'=>'unsuccessful_touch',
            'error_lead_to_shot'=>'error_lead_to_shot',
            'error_lead_to_goal'=>'error_lead_to_goal'
            );
$map = array('games'=>$games,
              'passing_and_attacking'=>$passing_and_attacking,
              'defending'=>$defending,
              'goalkeeper'=>$goalkeeper,
              'mistakes_and_errors'=>$mistakes_and_errors
             );


switch($data['player']['position']){
    case 'Forward':
        $pos = "f";
    break;
    case 'Midfielder':
        $pos = "m";
    break;
    case 'Defender':
        $pos = "d";
    break;
    default:
        $pos = 'g';
    break;
}
$total_points = 0;
$main_stats_vals = array('games'=>0,
                            'passing_and_attacking'=>0,
                            'defending'=>0,
                            'goalkeeper'=>0,
                            'mistakes_and_errors'=>0,
                         );

if(isset($data['overall_stats'])){
    foreach($data['overall_stats'] as $stats){
        $total_points += $stats['points'];
        $main_stats_vals[$stats['stats_category']]+= $stats['points'];
    }
    //foreach($main_stats_vals as $n){
    //    $total_points += $n;
    //}

}

/*
if(isset($data['overall_stats'])){
    foreach($data['overall_stats'] as $stats){
        $total_points += $stats['points'];
        foreach($map as $mainstats=>$substats){
            foreach($substats as $n=>$v){
                
                if($v==$stats['stats_name']){
                    if(!isset($main_stats_vals[$mainstats])){
                        $main_stats_vals[$mainstats] = 0;
                        $main_stats_ori[$mainstats] = 0;
                    }
                    $main_stats_vals[$mainstats] += ($stats['total'] *
                                                    getModifierValue($modifiers,
                                                                            $v,
                                                                            $pos));

                   
                }
            }
        }
    }
    //foreach($main_stats_vals as $n){
    //    $total_points += $n;
    //}

}*/

function getModifierValue($modifiers,$statsName,$pos){
    foreach($modifiers as $m){
        if($m['Modifier']['name']==$statsName){
            return ($m['Modifier'][$pos]);
        }
    }
    return 0;
}
function getStats($category,$pos,$modifiers,$map,$stats){
    $collection = array();
    $statTypes = $map[$category];
    foreach($stats as $st){
        if($st['stats_category']==$category){
            foreach($statTypes as $n=>$v){
                if(!isset($collection[$n])){
                    $collection[$n] = 0;
                }
                if($st['stats_name'] == $v){

                    $collection[$n] = array('total'=>$st['total'],
                                            'points'=>$st['points']);
                }
            }
        }
    }
    return $collection;
}
/*
function getStats($category,$pos,$modifiers,$map,$stats){
    
    
    $statTypes = $map[$category];
    //pr($statTypes);
    $collection = array();
    foreach($stats as $s){
        foreach($statTypes as $n=>$v){
            if(!isset($collection[$n])){
                $collection[$n] = 0;
            }
            if($s['stats_name'] == $v){
                $collection[$n] = array('total'=>$s['total'],
                                    'points'=>$s['total'] * getModifierValue($modifiers,$v,$pos));
            }
        }
    }
    
    return $collection;
}
*/
//get last performance value.
$weekly_performance = array();
foreach($data['stats'] as $n=>$v){
    if(!isset($weekly_performance[$v['matchday']])){
        $weekly_performance[$v['matchday']] = $v;
    }else{
        if( $weekly_performance[$v['matchday']]['performance'] == 0){
             $weekly_performance[$v['matchday']] = $v;
        }
    }
}
$data['stats'] = null;
while(sizeof($weekly_performance)>0){
    $data['stats'][] = array_shift($weekly_performance);
}

$weekly_performance = null;

?>
<div id="myClubPage">
     <?php echo $this->element('infobar'); ?>
     <?php if($data['player']!=null):?>
    <div class="headbar tr">
        <div class="club-info fl player-club">
            <div class="fl club-info-entry">
                <h3 class="clubname"><?=h($data['player']['name'])?></h3>
            </div>
        </div>
        <!--<div class="club-info fl">
            <p>Gaji Mingguan</p>
            <h4><?=number_format($data['player']['salary'])?></h4>
        </div>-->
        <div class="club-info fl">
            <p>Nilai Transfer</p>
            <h4>
                <?php
                    $performance = 0;

                    if(sizeof($data['stats'])>0){
                        if(intval(@$data['stats'][sizeof($data['stats'])-1]['points'])!=0){

                        $performance = getTransferValueBonus(
                                            $data['stats'][sizeof($data['stats'])-1]['performance'],
                                            $data['player']['transfer_value']);
                        }  
                    }
                    
                    $transfer_value = $data['player']['transfer_value'] + $performance;
                    echo number_format($transfer_value);
                ?>
            </h4>
        </div>
        <div class="club-info fl">
            <p>Status</p>
            <h4>N/A</h4>
        </div>
        <div class="club-info fl">
            <h5>
                <?php
                    print ($total_points);
                ?>
                Poin
            </h5>
        </div>
        <div class="club-money fr">
            <a data-team-name="<?=h($club['team_name'])?>" data-price="<?=number_format($transfer_value)?>" data-player-name="<?=$data['player']['name']?>" data-team="<?=$data['player']['original_team_id']?>" data-player="<?=$data['player']['player_id']?>" id="btnSale" class="icon-cart buttons" href="#popup-messages"><span>JUAL</span></a>
            <a class="buttons" href="<?=$this->Html->url('/manage/club/?rooster=1')?>"><span>KEMBALI</span></a>
        </div>
    </div><!-- end .headbar -->
    <div id="thecontent">
        <div class="content">
            <div id="tabs-Info">
                <div class="player-detail">
                    <opta widget="playerprofile" sport="football" competition="8" season="2013" 
                        team="<?=str_replace('t','',$data['player']['original_team_id'])?>" player="<?=str_replace("p","",$data['player']['player_id'])?>" show_image="true" show_nationality="true" opta_logo="false" narrow_limit="400"></opta>
                </div>
                
            	<div class="profileStats-container" style="display: block;">
                  <h2><span>Overall Performance</span></h2>
                  <div class="profileStatsContainer">
                    <div class="profileStats" style="overflow:hidden;">
                        <a href="#" class="statsbox">
                            <h4>Games</h4>
                            <p><?=($main_stats_vals['games'])?></p>
                        </a>
                        <a href="#" class="statsbox">
                            <h4>Passing and Attacking</h4>
                            <p><?=($main_stats_vals['passing_and_attacking'])?></p>
                        </a>
                        <a href="#" class="statsbox">
                            <h4>Defending</h4>
                            <p><?=($main_stats_vals['defending'])?></p>
                        </a>
                       
                        <a href="#" class="statsbox">
                            <h4>Goalkeeping</h4>
                            <p><?=($main_stats_vals['goalkeeper'])?></p>
                        </a>
                       
                        <a href="#" class="statsbox">
                            <h4>Mistakes and Errors</h4>
                            <p><?=($main_stats_vals['mistakes_and_errors'])?></p>
                        </a>
                       
                    </div><!-- end .profileStats -->
                  </div><!-- end .profileStats-container -->
                </div><!-- end .profileStats-container -->     
            </div><!-- end #Info -->
            <?php if(sizeof($data['daily_stats'])>0):?>
            <div id="chartbox" class="row">
                <div class="stats"></div>
            </div>
            <div id="profiletabs">
              <div class="rowTItle">
             	 <h3 class="tabtitle"><span class="fl">PERFORMANCE DETAILS</span></h3>
              </div>
              <div class="tabContainer">
              <div class="col2">
				  <div id="tabs-Games" class="boxTab">
					<div class="profileStatsContainer">
						<div class="profileStats">
						<div class="titleTab">Games</div>
						<table width="100%" border="0" cellspacing="0" cellpadding="0" class="theTable">
							<thead>
								<tr>
									<th>Aksi</th>
									<th class="aligncenter" width="1">Frequency</th>
									<th class="aligncenter" width="1">Poin</th>
								</tr>
							</thead>
							<tbody>
							<?php 
								$profileStats = getStats('games',$pos,$modifiers,$map,$data['overall_stats']);
								if(isset($profileStats)):
									foreach($profileStats as $statsName=>$statsVal):
										//$statsName = ucfirst(str_replace('_',' ',$statsName));
                                        $statsName = stats_translated($statsName,'id');
							?>
							  <tr>
								<td><p class="s-title"><?=$statsName?></p></td>
								<td class="aligncenter">
									<?=number_format($statsVal['total'])?>
								</td>
								<td class="aligncenter">
								   <?=floatval($statsVal['points'])?>
								</td>
							  </tr>
							<?php
								endforeach;
								endif;
							?>
							</tbody>
						</table>
						</div><!-- end .profileStats -->
					</div><!-- end .profileStats-container -->
				  </div><!-- end #Info -->
				  <div id="tabs-Passing" class="boxTab">
					  <div class="profileStatsContainer">
						<div class="profileStats">
						<div class="titleTab">Passing &amp; Attacking</div>
						<table width="100%" border="0" cellspacing="0" cellpadding="0" class="theTable">
							<thead>
								<tr>
									<th>Aksi</th>
									<th class="aligncenter" width="1">Frequency</th>
									<th class="aligncenter" width="1">Poin</th>
								</tr>
							</thead>
							<tbody>
							<?php 
								$profileStats = getStats('passing_and_attacking',$pos,$modifiers,$map,$data['overall_stats']);
								if(isset($profileStats)):
									foreach($profileStats as $statsName=>$statsVal):
										$statsName = stats_translated($statsName,'id');
							?>
							  <tr>
								<td><p class="s-title"><?=$statsName?></p></td>
								<td class="aligncenter">
								   <?=number_format($statsVal['total'])?>
								</td>
								<td class="aligncenter">
									<?=floatval($statsVal['points'])?>
								</td>
							  </tr>
							<?php
								endforeach;
								endif;
							?>
							</tbody>
						</table>
						</div><!-- end .profileStats -->
					</div><!-- end .profileStats-container -->
				  </div><!-- end #tabs-Keuagan -->
			  </div>
              <div class="col2 col2Right">
              <div id="tabs-Defending" class="boxTab">
               <div class="profileStatsContainer">
                    <div class="profileStats">
					<div class="titleTab">Defending</div>
        			<table width="100%" border="0" cellspacing="0" cellpadding="0" class="theTable">
						<thead>
							<tr>
								<th>Aksi</th>
								<th class="aligncenter" width="1">Frequency</th>
								<th class="aligncenter" width="1">Poin</th>
							</tr>
						</thead>
						<tbody>
                        <?php 
                            $profileStats = getStats('defending',$pos,$modifiers,$map,$data['overall_stats']);
                            if(isset($profileStats)):
                                foreach($profileStats as $statsName=>$statsVal):
                                    $statsName = stats_translated($statsName,'id');
                        ?>
                          <tr>
                            <td><p class="s-title"><?=$statsName?></p></td>
                            <td class="aligncenter">
                                <?=number_format($statsVal['total'])?>
                            </td>
                            <td class="aligncenter">
                                <?=floatval($statsVal['points'])?>
                            </td>
                          </tr>
                        <?php
                            endforeach;
                            endif;
                        ?>
						</tbody>
					</table>
                    </div><!-- end .profileStats -->
                </div><!-- end .profileStats-container -->
              </div><!-- end #tabs-Squad -->
              <?php if($pos=='g'):?>
              <div id="tabs-Goalkeeping" class="boxTab">
                   <div class="profileStatsContainer">
                    <div class="profileStats">
					<div class="titleTab">Goalkeeping</div>
        			<table width="100%" border="0" cellspacing="0" cellpadding="0" class="theTable">
						<thead>
							<tr>
								<th>Aksi</th>
								<th class="aligncenter" width="1">Frequency</th>
								<th class="aligncenter" width="1">Poin</th>
							</tr>
						</thead>
						<tbody>
                        <?php 
                            $profileStats = getStats('goalkeeper',$pos,$modifiers,$map,$data['overall_stats']);
                            if(isset($profileStats)):
                                foreach($profileStats as $statsName=>$statsVal):
                                     $statsName = stats_translated($statsName,'id');
                        ?>
                          <tr>
                            <td><p class="s-title"><?=$statsName?></p></td>
                            <td class="aligncenter">
                                <?=number_format($statsVal['total'])?>
                            </td>
                            <td class="aligncenter">
                                <?=floatval($statsVal['points'])?>
                            </td>
                          </tr>
                        <?php
                            endforeach;
                            endif;
                        ?>
						</tbody>
					</table>
                    </div><!-- end .profileStats -->
                </div><!-- end .profileStats-container -->
              </div><!-- end #tabs-Staff -->
             <?php endif;?>
              <div id="tabs-Mistakes" class="boxTab">
                   <div class="profileStatsContainer">
                    <div class="profileStats">
					<div class="titleTab">Mistakes &amp; Errors</div>
        			<table width="100%" border="0" cellspacing="0" cellpadding="0" class="theTable">
						<thead>
							<tr>
								<th>Aksi</th>
								<th class="aligncenter" width="1">Frequency</th>
								<th class="aligncenter" width="1">Poin</th>
							</tr>
						</thead>
						<tbody>
                        <?php 
                            $profileStats = getStats('mistakes_and_errors',$pos,$modifiers,$map,$data['overall_stats']);
                            if(isset($profileStats)):
                                foreach($profileStats as $statsName=>$statsVal):
                                    $statsName = stats_translated($statsName,'id');
                        ?>
                          <tr>
                            <td><p class="s-title"><?=$statsName?></p></td>
                            <td class="aligncenter">
                                <?=number_format($statsVal['total'])?>
                            </td>
                            <td class="aligncenter">
                                <?=floatval($statsVal['points'])?>
                            </td>
                          </tr>
                        <?php
                            endforeach;
                            endif;
                        ?>
						</tbody>
					</table>
                    </div><!-- end .profileStats -->
                </div><!-- end .profileStats-container -->
              </div><!-- end #tabs-Staff -->
              </div><!-- end #tabcontainer -->
			  </div>
            </div><!-- end #clubtabs -->
            <?php endif;?>
        </div><!-- end .content -->



    </div><!-- end #thecontent -->
    <?php else:?>
    <div id="thecontent">
        <div class="content">
            <div>
                <h1 class="yellow">Pemain ini bukan anggota Klab.</h1>
               
               
            </div><!-- end #logoutpage -->
        </div>
    </div>
    <?php endif;?>
</div><!-- end #myClubPage -->
<!--popups-->
<div class="popup">
    <div class="popupContainer popup-small" id="popup-messages">
        <div class="popupHeader">
        </div><!-- END .popupHeader -->
        <div class="popupContent">
            <div class="entry-popup">
                yellow
            </div><!--END .entry-popup-->
        </div><!-- END .popupContent -->
    </div><!-- END .popupContainer -->
</div><!-- END .popup --> 




<?=$this->Html->script(array('highcharts'))?>
<script>
var stats  = [];
for(var i in daily_stats){
    stats.push({
        ts:daily_stats[i].fixture.ts*1000,
        games:daily_stats[i].games,
        passing_and_attacking:daily_stats[i].passing_and_attacking,
       
        defending:daily_stats[i].defending,
        goalkeeping:daily_stats[i].goalkeeper,
        mistakes:daily_stats[i].mistakes_and_errors
    });
}
var categories = [];
var games = [];
var passing_and_attacking = [];
var defending = [];
var goalkeeping = [];
var mistakes = [];

$.each(stats,function(k,v){
  categories.push(v.ts);
  games.push(parseFloat(v.games));
  passing_and_attacking.push(parseFloat(v.passing_and_attacking));
  defending.push(parseFloat(v.defending));
  goalkeeping.push(parseFloat(v.goalkeeping));
  mistakes.push(parseFloat(v.mistakes) * -1);
});

$('.stats').highcharts({
    chart: {
        type: 'area',
        backgroundColor:'#ccc',
        style: {
            color: "#000"
        },
    },
    title: {
        text: 'Weekly Performance',
        style: {
          color: '#000'
        }
    },
   
    xAxis: {
        type: 'datetime',
        dateTimeLabelFormats: { // don't display the dummy year
            day: '%e. %b',
            month: '%e. %b',
            year: '%b'
        },
        categories: categories,
        labels: {
            align:'left',
            rotation:60,
            formatter: function() {
                
                return Highcharts.dateFormat('%d/%m', this.value);
            }
        }
    },
    yAxis: {
        title: {
            text: 'Total',
            style:{
              color:'#000'
            }
        },

    },
    tooltip: {
        enabled: true,
        formatter: function() {
            console.log(this);
            return '<strong>'+Highcharts.dateFormat('%d/%m/%Y', this.x)+'</strong><br/>'+
                    this.series.name+': '+ this.y +'';
        }
    },
    plotOptions: {
        area: {
            stacking: 'normal',
            lineColor: '#666666',
            lineWidth: 1,
            marker: {
                lineWidth: 1,
                lineColor: '#666666'
            }
        }
    },
    credits:false,
    series: [
        {
            name: 'Games',
            data: games
        },
        {
            name: 'Passing & Attacking',
            data: passing_and_attacking
        },
        {
            name: 'Defending',
            data: defending
        },
       
        {
            name: 'Goalkeeping',
            data: goalkeeping
        },
       
        {
            name: 'Mistakes and Errors',
            data: mistakes
        },

    ]
});
</script>
<script>
$( "#profiletabs" ).tabs({
    create:function(event,ui){
        var thisTab = $(ui.tab).find('a').html();
        $("#profiletabs h3").html(thisTab);
        //console.log('create');
    },
    activate:function(event,ui){
        var thisTab = $(ui.newTab).find('a').html();
        $("#profiletabs h3").html(thisTab);
       // console.log('activate');
    }
});


</script>

<script>
$("#btnSale").fancybox({
    beforeLoad : function(){
      $("#popup-messages .popupContent .entry-popup").html('');
      $('.saving').hide();
      $('.confirm').show();
      $('.success').hide();
      $('.failure').hide();
      render_view(tplsale,"#popup-messages .popupContent .entry-popup",{
        player_id:$(this.element).data('player'),
        team_id:$(this.element).data('team'),
        player_name:$(this.element).data('player-name'),
        team_name:$(this.element).data('team-name'),
        transfer_value:$(this.element).data('price')
      });
      $jqOpta.widgetStart(_optaParams);
    },
});
</script>

<script type="text/template" id="tplsale">
    <%
      var uid = player_id.replace('p','');
      var team = team_id.replace('t','');
    %>
    <div class="confirm">
        <h1>Apakah kamu ingin menjual pemain ini?</h1>
        <h3>Pemain yang sudah dijual akan hilang dari lineup dan tidak dapat di undo</h3>
        <h4><%=transfer_value%> Star</h4>
        <opta widget="playerprofile" sport="football" competition="8" season="2013" team="<%=team%>" 
          player="<%=uid%>" show_image="true" show_nationality="true" opta_logo="false" 
          narrow_limit="400"></opta>
        <p><a href="#/sale/<%=player_id%>/1" class="button">Jual</a>
            <a href="#" class="button" onclick="$.fancybox.close();return false;">Batal</a></p>
    </div>
    <div class="saving" style="display:none;">
        <h1>Menjual Pemain.</h1>
        <h3>Harap tunggu sebentar..</h3>
        <p><img src="<?=$this->Html->url('/css/fancybox/fancybox_loading@2x.gif')?>"/></p>
    </div>
    <div class="success" style="display:none;">
        <h1>Penjualan Berhasil</h1>
        <h3><%=player_name%> sudah dijual dari <%=team_name%></h3>
    </div>
    <div class="failure" style="display:none;">
        <h1>Penjualan Tidak Berhasil</h1>
        <h3>Silahkan coba kembali !</h3>
    </div>
    <div class="invalid_transfer" style="display:none;">
        <h1>Dijual lagi??</h1>
        <h3>Pemain yang baru dibeli tidak bisa di jual dalam transfer window yang sama. 
            Next time, jangan buru-buru jual pemain elo ya !</h3>
        
    </div>
    <div class="window_closed" style="display:none;">
        <h1>Transfer Window Sedang Tutup</h1>
        <h3>Transfer window SuperSoccer Football Manager sedang tutup, silahkan balik lagi tanggal 11.</h3>
        
    </div>
</script>