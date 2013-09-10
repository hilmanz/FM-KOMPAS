<div id="fullcontent">
   <div>
      <h3><?=$report['results']['home_name']?> VS <?=$report['results']['away_name']?></h3>
    </div>
<?php
$data = $report['home'];
?>
    <div>
      <a href="<?=$this->Html->url('/pages/team/?team_id='.$team_id)?>" class="button">Back</a>
    </div>
    <div class="section">
        <div class="col12">
            <div class="widget">
                <div class="widget-title">
                    <h3><a href="<?=$this->Html->url('/pages/matchstats/?game_id='.$report['results']['game_id'].'&team_id='.$report['results']['home_team'])?>"><?=$report['results']['home_name']?></a></h3>
                </div><!-- end .widget-title -->
                <div class="widget-content">
                  <div style="text-align:center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
                    <thead>
                          <tr>
                            <th><h4>Overview</h4></th>
                            <th><h5></h5></th>
                          </tr>
                      </thead>
                      <tbody>
                        <tr>
                            <td>League Position</td>
                            <td class="tright">
                              N/A
                            </td>
                          </tr>
                          <tr>
                            <td>Most Influencal Player</td>
                            <td class="tright">
                              <?=@$data['most_influential_player'][0]['name']?>
                            </td>
                          </tr>
                          <tr>
                            <td>Top Assist</td>
                            <td class="tright">
                              <?=@$data['top_assist'][0]['name']?>
                            </td>
                          </tr>
                          <tr>
                            <td>Top Scorer</td>
                            <td class="tright">
                              <?=@$data['top_scorer'][0]['name']?>
                            </td>
                          </tr>
                          <tr>
                            <td>Dangerous Passer</td>
                            <td class="tright">
                              <?=@$data['dangerous_passer'][0]['name']?>
                            </td>
                          </tr>
                          <tr>
                            <td>Greatest Liability</td>
                            <td class="tright">
                              <?=@$data['greatest_liability'][0]['name']?>
                            </td>
                          </tr>
                      </tbody>
                    </table>
                  </div>
                </div><!-- end .widget-content -->
                
        </div><!-- end .col4 -->
    </div><!-- end .section -->
    
    <div class="section">
        <div class="col12">
            <div class="widget">
                <div class="widget-title">
                    <h3>Attacking Play</h3>
                </div><!-- end .widget-title -->
                <div class="widget-content">
                  <div style="text-align:center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
                    <thead>
                          <tr>
                            <th><h4>Stats</h4></th>
                            <th><h5>Frequency</h5></th>
                            <th><h5>Effeciency</h5></th>
                            <th><h5>Chances</h5></th>
                            <th><h5>Goals</h5></th>
                            <th><h5>Conversion Rate</h5></th>
                            <th><h5>Average/Game</h5></th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php
                        foreach($data['attacking_play'] as $stats=>$st):
                          
                        ?>
                        <tr>
                          <td><?=ucfirst(str_replace('_',' ',$stats))?></td>
                          <td class="">
                             <?=round($st['frequency']*100)?> %
                          </td>
                          <td class="">
                             <?=round($st['efficiency']*100)?> %
                          </td>
                          <td class="">
                             <?=$st['chances']?>
                          </td>
                          <td class="">
                             <?=$st['goals']?>
                          </td>
                          <td class="">
                             <?=round($st['conversion_rate']*100)?> %
                          </td>
                          <td class="">
                             <?=round($st['average']*100)?> %
                          </td>
                        </tr>
                      <?php endforeach;?>
                      </tbody>
                    </table>
                  </div>
                </div><!-- end .widget-content -->
            </div>
        </div><!-- end .col4 -->
    </div><!-- end .section -->

    <div class="section">
        <div class="col4">
            <div class="widget">
                <div class="widget-title">
                    <h3>Attacking Style</h3>
                </div><!-- end .widget-title -->
                <div class="widget-content">
                  <div style="text-align:center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
                    <thead>
                          <tr>
                            <th><h4>Stats</h4></th>
                            <th><h5>#</h5></th>
                            <th><h5>%</h5></th>
                            <th><h5>Avg/Game</h5></th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php
                        foreach($data['attacking_style'] as $stats=>$st):
                        ?>
                        <tr>
                          <td><?=ucfirst(str_replace('_',' ',$stats))?></td>
                          <td class="">
                             <?=$st['total']?>
                          </td>
                          <td class="">
                             <?=round($st['average']*100)?> %
                          </td>
                          <td class="">
                             <?=$st['total']/$data['total_games']?>
                          </td>
                         
                        </tr>
                      <?php endforeach;?>
                      </tbody>
                    </table>
                  </div>
                </div><!-- end .widget-content -->
              </div>  
        </div><!-- end .col4 -->
        <div class="col4">
            <div class="widget">
                <div class="widget-title">
                    <h3>Dribbling</h3>
                </div><!-- end .widget-title -->
                <div class="widget-content">
                  <div style="text-align:center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
                    <thead>
                          <tr>
                            <th><h4>Stats</h4></th>
                            <th><h5>#</h5></th>
                            <th><h5>Avg/Game</h5></th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php
                        foreach($data['dribbling'] as $stats=>$st):
                        ?>
                        <tr>
                          <td><?=ucfirst(str_replace('_',' ',$stats))?></td>
                          <td class="">
                             <?=$st?>
                          </td>
                         
                          <td class="">
                             <?=$st/$data['total_games']?>
                          </td>
                         
                        </tr>
                      <?php endforeach;?>
                      </tbody>
                    </table>
                  </div>
                </div><!-- end .widget-content -->
            </div>
        </div><!-- end .col4 -->
      <div class="col4 ">
            <div class="widget">
                <div class="widget-title">
                    <h3>Passing Style</h3>
                </div><!-- end .widget-title -->
                <div class="widget-content">
                  <div style="text-align:center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
                    <thead>
                          <tr>
                            <th><h4>Stats</h4></th>
                            <th><h5>#</h5></th>
                            <th><h5>%</h5></th>
                            <th><h5>Accuracy</h5></th>
                            <th><h5>Avg/Game</h5></th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php
                        foreach($data['passing_style'] as $stats=>$st):
                        ?>
                        <tr>
                          <td><?=ucfirst(str_replace('_',' ',$stats))?></td>
                          <td class="">
                             <?=$st['total']?>
                          </td>
                          <td class="">
                             <?=round($st['average']*100)?> %
                          </td>
                          <td class="">
                             <?=round($st['accuracy']*100)?> %
                          </td>
                          <td class="">
                             <?=$st['total']/$data['total_games']?>
                          </td>
                         
                        </tr>
                      <?php endforeach;?>
                      </tbody>
                    </table>
                  </div>
                </div><!-- end .widget-content -->
            </div>    
        </div><!-- end .col4 -->
      <div class="col4 col4last">
            <div class="widget">
                <div class="widget-title">
                    <h3>defending_style</h3>
                </div><!-- end .widget-title -->
                <div class="widget-content">
                  <div style="text-align:center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
                    <thead>
                          <tr>
                            <th><h4>Stats</h4></th>
                            <th><h5>#</h5></th>
                            <th><h5>%</h5></th>
                            <th><h5>Avg/Game</h5></th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php
                        foreach($data['defending_style'] as $stats=>$st):
                        ?>
                        <tr>
                          <td><?=ucfirst(str_replace('_',' ',$stats))?></td>
                          <td class="">
                             <?=$st['total']?>
                          </td>
                         <td class="">
                             <?=round($st['average']*100)?> %
                          </td>
                          <td class="">
                             <?=($st['total']/$data['total_games'])?>
                          </td>
                         
                        </tr>
                      <?php endforeach;?>
                      </tbody>
                    </table>
                  </div>
                </div><!-- end .widget-content -->
          </div>
        </div><!-- end .col4 -->
        
        
    </div><!-- end .section -->

    <div class="section">
      <div class="col4 ">
            <div class="widget">
                <div class="widget-title">
                    <h3>Goalkeeping</h3>
                </div><!-- end .widget-title -->
                <div class="widget-content">
                  <div style="text-align:center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
                    <thead>
                          <tr>
                            <th><h4>Stats</h4></th>
                            <th><h5>#</h5></th>
                            <th><h5>%</h5></th>
                            <th><h5>Avg/Game</h5></th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php
                        foreach($data['goalkeeping'] as $stats=>$st):
                        ?>
                        <tr>
                          <td><?=ucfirst(str_replace('_',' ',$stats))?></td>
                          <td class="">
                             <?=$st['total']?>
                          </td>
                          <td class="">
                             <?=round($st['average']*100)?> %
                          </td>
                          <td class="">
                             <?=$st['total']/$data['total_games']?>
                          </td>
                         
                        </tr>
                      <?php endforeach;?>
                      </tbody>
                    </table>
                  </div>
                </div><!-- end .widget-content -->
            </div>    
        </div><!-- end .col4 -->
      <div class="col4">
            <div class="widget">
                <div class="widget-title">
                    <h3>DEF strength &amp; weakness</h3>
                </div><!-- end .widget-title -->
                <div class="widget-content">
                  <div style="text-align:center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
                    <thead>
                          <tr>
                            <th><h4>Stats</h4></th>
                            <th><h5>#</h5></th>
                            <th><h5>%</h5></th>
                            <th><h5>Avg/Game</h5></th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php
                        foreach($data['defending_strength_and_weakness'] as $stats=>$st):
                        ?>
                        <tr>
                          <td><?=ucfirst(str_replace('_',' ',$stats))?></td>
                          <td class="">
                             <?=$st['total']?>
                          </td>
                         <td class="">
                             <?=round($st['average']*100)?> %
                          </td>
                          <td class="">
                             <?=($st['total']/$data['total_games'])?>
                          </td>
                         
                        </tr>
                      <?php endforeach;?>
                      </tbody>
                    </table>
                  </div>
                </div><!-- end .widget-content -->
          </div>
        </div><!-- end .col4 -->

        <div class="col4 ">
            <div class="widget">
                <div class="widget-title">
                    <h3>Aerial Strength</h3>
                </div><!-- end .widget-title -->
                <div class="widget-content">
                  <div style="text-align:center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
                    <thead>
                          <tr>
                            <th><h4>Stats</h4></th>
                            <th><h5>#</h5></th>
                            <th><h5>%</h5></th>
                            <th><h5>Avg/Game</h5></th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php
                        foreach($data['aerial_strength'] as $stats=>$st):
                        ?>
                        <tr>
                          <td><?=ucfirst(str_replace('_',' ',$stats))?></td>
                          <td class="">
                             <?=$st['total']?>
                          </td>
                          <td class="">
                             <?=round($st['average']*100)?> %
                          </td>
                          <td class="">
                             <?=$st['total']/$data['total_games']?>
                          </td>
                         
                        </tr>
                      <?php endforeach;?>
                      </tbody>
                    </table>
                  </div>
                </div><!-- end .widget-content -->
            </div>    
        </div><!-- end .col4 -->
      <div class="col4 col4last">
            <div class="widget">
                <div class="widget-title">
                    <h3>Set Plays</h3>
                </div><!-- end .widget-title -->
                <div class="widget-content">
                  <div style="text-align:center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
                    <thead>
                          <tr>
                            <th><h4>Stats</h4></th>
                            <th><h5>#</h5></th>
                            <th><h5>Accuracy</h5></th>
                            <th><h5>Chance</h5></th>
                            <th><h5>Avg/Game</h5></th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php
                        foreach($data['setplays'] as $stats=>$st):
                        ?>
                        <tr>
                          <td><?=ucfirst(str_replace('_',' ',$stats))?></td>
                          <td class="">
                             <?=$st['total']?>
                          </td>
                         <td class="">
                             <?=round($st['accuracy']*100)?> %
                          </td>
                          <td class="">
                             <?=round($st['chance_ratio']*100)?> %
                          </td>
                          <td class="">
                             <?=($st['total']/$data['total_games'])?>
                          </td>
                         
                        </tr>
                      <?php endforeach;?>
                      </tbody>
                    </table>
                  </div>
                </div><!-- end .widget-content -->
          </div>
        </div><!-- end .col4 -->
        
        
    </div><!-- end .section -->



<!-- AWAY TEAM STATS -->


<?php
$data = $report['away'];
?>

    <div>
      <a href="<?=$this->Html->url('/pages/team/?team_id='.$team_id)?>" class="button">Back</a>
    </div>
    <div class="section">
        <div class="col12">
            <div class="widget">
                <div class="widget-title">
                    <h3><a href="<?=$this->Html->url('/pages/matchstats/?game_id='.$report['results']['game_id'].'&team_id='.$report['results']['away_team'])?>">
                      <?=$report['results']['away_name']?>
                    </a></h3>
                </div><!-- end .widget-title -->
                <div class="widget-content">
                  <div style="text-align:center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
                    <thead>
                          <tr>
                            <th><h4>Overview</h4></th>
                            <th><h5></h5></th>
                          </tr>
                      </thead>
                      <tbody>
                        <tr>
                            <td>League Position</td>
                            <td class="tright">
                              N/A
                            </td>
                          </tr>
                          <tr>
                            <td>Most Influencal Player</td>
                            <td class="tright">
                              <?=@$data['most_influential_player'][0]['name']?>
                            </td>
                          </tr>
                          <tr>
                            <td>Top Assist</td>
                            <td class="tright">
                              <?=@$data['top_assist'][0]['name']?>
                            </td>
                          </tr>
                          <tr>
                            <td>Top Scorer</td>
                            <td class="tright">
                              <?=@$data['top_scorer'][0]['name']?>
                            </td>
                          </tr>
                          <tr>
                            <td>Dangerous Passer</td>
                            <td class="tright">
                              <?=@$data['dangerous_passer'][0]['name']?>
                            </td>
                          </tr>
                          <tr>
                            <td>Greatest Liability</td>
                            <td class="tright">
                              <?=@$data['greatest_liability'][0]['name']?>
                            </td>
                          </tr>
                      </tbody>
                    </table>
                  </div>
                </div><!-- end .widget-content -->
                
        </div><!-- end .col4 -->
    </div><!-- end .section -->
    
    <div class="section">
        <div class="col12">
            <div class="widget">
                <div class="widget-title">
                    <h3>Attacking Play</h3>
                </div><!-- end .widget-title -->
                <div class="widget-content">
                  <div style="text-align:center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
                    <thead>
                          <tr>
                            <th><h4>Stats</h4></th>
                            <th><h5>Frequency</h5></th>
                            <th><h5>Effeciency</h5></th>
                            <th><h5>Chances</h5></th>
                            <th><h5>Goals</h5></th>
                            <th><h5>Conversion Rate</h5></th>
                            <th><h5>Average/Game</h5></th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php
                        foreach($data['attacking_play'] as $stats=>$st):
                          
                        ?>
                        <tr>
                          <td><?=ucfirst(str_replace('_',' ',$stats))?></td>
                          <td class="">
                             <?=round($st['frequency']*100)?> %
                          </td>
                          <td class="">
                             <?=round($st['efficiency']*100)?> %
                          </td>
                          <td class="">
                             <?=$st['chances']?>
                          </td>
                          <td class="">
                             <?=$st['goals']?>
                          </td>
                          <td class="">
                             <?=round($st['conversion_rate']*100)?> %
                          </td>
                          <td class="">
                             <?=round($st['average']*100)?> %
                          </td>
                        </tr>
                      <?php endforeach;?>
                      </tbody>
                    </table>
                  </div>
                </div><!-- end .widget-content -->
            </div>
        </div><!-- end .col4 -->
    </div><!-- end .section -->

    <div class="section">
        <div class="col4">
            <div class="widget">
                <div class="widget-title">
                    <h3>Attacking Style</h3>
                </div><!-- end .widget-title -->
                <div class="widget-content">
                  <div style="text-align:center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
                    <thead>
                          <tr>
                            <th><h4>Stats</h4></th>
                            <th><h5>#</h5></th>
                            <th><h5>%</h5></th>
                            <th><h5>Avg/Game</h5></th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php
                        foreach($data['attacking_style'] as $stats=>$st):
                        ?>
                        <tr>
                          <td><?=ucfirst(str_replace('_',' ',$stats))?></td>
                          <td class="">
                             <?=$st['total']?>
                          </td>
                          <td class="">
                             <?=round($st['average']*100)?> %
                          </td>
                          <td class="">
                             <?=$st['total']/$data['total_games']?>
                          </td>
                         
                        </tr>
                      <?php endforeach;?>
                      </tbody>
                    </table>
                  </div>
                </div><!-- end .widget-content -->
              </div>  
        </div><!-- end .col4 -->
        <div class="col4">
            <div class="widget">
                <div class="widget-title">
                    <h3>Dribbling</h3>
                </div><!-- end .widget-title -->
                <div class="widget-content">
                  <div style="text-align:center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
                    <thead>
                          <tr>
                            <th><h4>Stats</h4></th>
                            <th><h5>#</h5></th>
                            <th><h5>Avg/Game</h5></th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php
                        foreach($data['dribbling'] as $stats=>$st):
                        ?>
                        <tr>
                          <td><?=ucfirst(str_replace('_',' ',$stats))?></td>
                          <td class="">
                             <?=$st?>
                          </td>
                         
                          <td class="">
                             <?=$st/$data['total_games']?>
                          </td>
                         
                        </tr>
                      <?php endforeach;?>
                      </tbody>
                    </table>
                  </div>
                </div><!-- end .widget-content -->
            </div>
        </div><!-- end .col4 -->
      <div class="col4 ">
            <div class="widget">
                <div class="widget-title">
                    <h3>Passing Style</h3>
                </div><!-- end .widget-title -->
                <div class="widget-content">
                  <div style="text-align:center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
                    <thead>
                          <tr>
                            <th><h4>Stats</h4></th>
                            <th><h5>#</h5></th>
                            <th><h5>%</h5></th>
                            <th><h5>Accuracy</h5></th>
                            <th><h5>Avg/Game</h5></th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php
                        foreach($data['passing_style'] as $stats=>$st):
                        ?>
                        <tr>
                          <td><?=ucfirst(str_replace('_',' ',$stats))?></td>
                          <td class="">
                             <?=$st['total']?>
                          </td>
                          <td class="">
                             <?=round($st['average']*100)?> %
                          </td>
                          <td class="">
                             <?=round($st['accuracy']*100)?> %
                          </td>
                          <td class="">
                             <?=$st['total']/$data['total_games']?>
                          </td>
                         
                        </tr>
                      <?php endforeach;?>
                      </tbody>
                    </table>
                  </div>
                </div><!-- end .widget-content -->
            </div>    
        </div><!-- end .col4 -->
      <div class="col4 col4last">
            <div class="widget">
                <div class="widget-title">
                    <h3>defending_style</h3>
                </div><!-- end .widget-title -->
                <div class="widget-content">
                  <div style="text-align:center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
                    <thead>
                          <tr>
                            <th><h4>Stats</h4></th>
                            <th><h5>#</h5></th>
                            <th><h5>%</h5></th>
                            <th><h5>Avg/Game</h5></th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php
                        foreach($data['defending_style'] as $stats=>$st):
                        ?>
                        <tr>
                          <td><?=ucfirst(str_replace('_',' ',$stats))?></td>
                          <td class="">
                             <?=$st['total']?>
                          </td>
                         <td class="">
                             <?=round($st['average']*100)?> %
                          </td>
                          <td class="">
                             <?=($st['total']/$data['total_games'])?>
                          </td>
                         
                        </tr>
                      <?php endforeach;?>
                      </tbody>
                    </table>
                  </div>
                </div><!-- end .widget-content -->
          </div>
        </div><!-- end .col4 -->
        
        
    </div><!-- end .section -->

    <div class="section">
      <div class="col4 ">
            <div class="widget">
                <div class="widget-title">
                    <h3>Goalkeeping</h3>
                </div><!-- end .widget-title -->
                <div class="widget-content">
                  <div style="text-align:center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
                    <thead>
                          <tr>
                            <th><h4>Stats</h4></th>
                            <th><h5>#</h5></th>
                            <th><h5>%</h5></th>
                            <th><h5>Avg/Game</h5></th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php
                        foreach($data['goalkeeping'] as $stats=>$st):
                        ?>
                        <tr>
                          <td><?=ucfirst(str_replace('_',' ',$stats))?></td>
                          <td class="">
                             <?=$st['total']?>
                          </td>
                          <td class="">
                             <?=round($st['average']*100)?> %
                          </td>
                          <td class="">
                             <?=$st['total']/$data['total_games']?>
                          </td>
                         
                        </tr>
                      <?php endforeach;?>
                      </tbody>
                    </table>
                  </div>
                </div><!-- end .widget-content -->
            </div>    
        </div><!-- end .col4 -->
      <div class="col4">
            <div class="widget">
                <div class="widget-title">
                    <h3>DEF strength &amp; weakness</h3>
                </div><!-- end .widget-title -->
                <div class="widget-content">
                  <div style="text-align:center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
                    <thead>
                          <tr>
                            <th><h4>Stats</h4></th>
                            <th><h5>#</h5></th>
                            <th><h5>%</h5></th>
                            <th><h5>Avg/Game</h5></th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php
                        foreach($data['defending_strength_and_weakness'] as $stats=>$st):
                        ?>
                        <tr>
                          <td><?=ucfirst(str_replace('_',' ',$stats))?></td>
                          <td class="">
                             <?=$st['total']?>
                          </td>
                         <td class="">
                             <?=round($st['average']*100)?> %
                          </td>
                          <td class="">
                             <?=($st['total']/$data['total_games'])?>
                          </td>
                         
                        </tr>
                      <?php endforeach;?>
                      </tbody>
                    </table>
                  </div>
                </div><!-- end .widget-content -->
          </div>
        </div><!-- end .col4 -->

        <div class="col4 ">
            <div class="widget">
                <div class="widget-title">
                    <h3>Aerial Strength</h3>
                </div><!-- end .widget-title -->
                <div class="widget-content">
                  <div style="text-align:center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
                    <thead>
                          <tr>
                            <th><h4>Stats</h4></th>
                            <th><h5>#</h5></th>
                            <th><h5>%</h5></th>
                            <th><h5>Avg/Game</h5></th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php
                        foreach($data['aerial_strength'] as $stats=>$st):
                        ?>
                        <tr>
                          <td><?=ucfirst(str_replace('_',' ',$stats))?></td>
                          <td class="">
                             <?=$st['total']?>
                          </td>
                          <td class="">
                             <?=round($st['average']*100)?> %
                          </td>
                          <td class="">
                             <?=$st['total']/$data['total_games']?>
                          </td>
                         
                        </tr>
                      <?php endforeach;?>
                      </tbody>
                    </table>
                  </div>
                </div><!-- end .widget-content -->
            </div>    
        </div><!-- end .col4 -->
      <div class="col4 col4last">
            <div class="widget">
                <div class="widget-title">
                    <h3>Set Plays</h3>
                </div><!-- end .widget-title -->
                <div class="widget-content">
                  <div style="text-align:center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
                    <thead>
                          <tr>
                            <th><h4>Stats</h4></th>
                            <th><h5>#</h5></th>
                            <th><h5>Accuracy</h5></th>
                            <th><h5>Chance</h5></th>
                            <th><h5>Avg/Game</h5></th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php
                        foreach($data['setplays'] as $stats=>$st):
                        ?>
                        <tr>
                          <td><?=ucfirst(str_replace('_',' ',$stats))?></td>
                          <td class="">
                             <?=$st['total']?>
                          </td>
                         <td class="">
                             <?=round($st['accuracy']*100)?> %
                          </td>
                          <td class="">
                             <?=round($st['chance_ratio']*100)?> %
                          </td>
                          <td class="">
                             <?=($st['total']/$data['total_games'])?>
                          </td>
                         
                        </tr>
                      <?php endforeach;?>
                      </tbody>
                    </table>
                  </div>
                </div><!-- end .widget-content -->
          </div>
        </div><!-- end .col4 -->
        
        
    </div><!-- end .section -->


</div><!-- end #fullcontent -->

