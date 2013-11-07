<?php
/**
 * API controller.
 *
 * This file will serves as API endpoint
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Controller
 * @since         CakePHP(tm) v 0.2.9
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
App::uses('AppController', 'Controller');
App::uses('Sanitize', 'Utility');
require_once APP . 'Vendor' . DS. 'Thumbnail.php';
class ApiController extends AppController {

/**
 * Controller name
 *
 * @var string
 */
	public $name = 'Api';
	public $uses = array();
	private $weekly_balances = null;
	private $expenditures = null;
	private $starting_budget = 0;
	private $finance_total_items_raw = null;
	private $tickets_sold = null;
	public function auth(){
		$fb_id = $this->request->query('fb_id');
		$user = $this->User->findByFb_id($fb_id);

		if(isset($user['User'])){
			$rs = $this->Apikey->findByApi_key($this->request->query['api_key']);
			if(isset($rs['Apikey']) && $rs['Apikey']['api_key']!=null){
				$access_token = encrypt_param(serialize(array('api_key'=>$rs['Apikey']['api_key'],
														  'valid_until'=>time()+24*60*60)));

				$this->redisClient->set($access_token,serialize(array('api_key'=>$rs['Apikey']['api_key'],
																	  'fb_id'=>$fb_id)));
				$this->redisClient->expire($access_token,24*60*60);//expires in 1 day
				$this->set('response',array('status'=>1,'access_token'=>$access_token));
			}else{
				$this->set('response',array('status'=>403,'error'=>'invalid api_key'));
			}
		}else{
			$this->set('response',array('status'=>400,'error'=>'user not found'));
		}
		
		$this->render('default');
	}
	public function index(){
		$this->set('response',array('status'=>1));
		$this->render('default');
	}
	
	public function team(){
		$api_session = $this->readAccessToken();
		$fb_id = $api_session['fb_id'];

		$user = $this->User->findByFb_id($fb_id);
		if(strlen($user['User']['avatar_img'])<2){
			$user['User']['avatar_img'] = "http://graph.facebook.com/".$fb_id."/picture";
		}else{
			$user['User']['avatar_img'] = Configure::read('avatar_web_url').'120x120_'.$user['User']['avatar_img'];
		}
		$game_team = $this->Game->getTeam($fb_id);
		$this->loadModel('Point');

		$point = $this->Point->findByTeam_id($user['Team']['id']);

		$response['user'] = array('id'=>$user['User']['id'],
									'fb_id'=>$user['User']['fb_id'],
									'name'=>$user['User']['name'],
									'avatar_img'=>$user['User']['avatar_img']);

		$response['stats']['points'] = intval(@$point['Point']['points']);
		$response['stats']['rank'] = intval(@$point['Point']['rank']);



		//list of players
		$players = $this->Game->get_team_players($fb_id);
		$response['players'] = $players;

		//lineup starters
		$lineup = $this->Game->getLineup($game_team['id']);
		$response['lineup_settings'] = $lineup;
		
		//budget
		$budget = $this->Game->getBudget($game_team['id']);
		
		$response['budget'] = $budget;
		$response['stats']['club_value'] = intval($budget) + $response['stats']['points'];
		//club
		$club = $this->Team->findByUser_id($user['User']['id']);
		
		$response['club'] = array('id'=>$club['Team']['id'],
									'team_name'=>$club['Team']['team_name'],
									'team_id'=>$club['Team']['team_id'],
								  );

		//get original club
		$original_club = $this->Game->getClub($game_team['team_id']);
		
		$response['original_club'] = $original_club;

		$next_match = $this->Game->getNextMatch($game_team['team_id']);
		$next_match['match']['home_original_name'] = $next_match['match']['home_name'];
		$next_match['match']['away_original_name'] = $next_match['match']['away_name'];
		if($next_match['match']['home_id']==$game_team['team_id']){
			$next_match['match']['home_name'] = $club['Team']['team_name'];
		}else{
			$next_match['match']['away_name'] = $club['Team']['team_name'];
		}
		$next_match['match']['match_date_ts'] = strtotime($next_match['match']['match_date']);
		$this->getCloseTime($next_match);

		$response['next_match'] = array('game_id'=>$next_match['match']['game_id'],
										'home_name'=>$next_match['match']['home_name'],
										'away_name'=>$next_match['match']['away_name'],
										'home_original_name'=>$next_match['match']['home_original_name'],
										'away_original_name'=>$next_match['match']['away_original_name'],
										'match_date'=>date("Y-m-d H:i:s",strtotime($next_match['match']['match_date'])),
										'match_date_ts'=>strtotime($next_match['match']['match_date'])
										);

		//match venue
		$match_venue = $this->Game->getVenue($next_match['match']['home_id']);
		$response['match_venue'] = $match_venue;

		//best match
		$best_match = $this->Game->getBestMatch($game_team['id']);
		$team_id = $game_team['team_id'];
		
		if($best_match['status']==0){
			$this->set('best_match','N/A');
			$response['stats']['best_match'] = 'N/A';
		}else{
			$best_match['data']['points'] = number_format($best_match['data']['points']);
			if($best_match['data']['match']['home_id']==$team_id){
				$against = $best_match['data']['match']['away_name'];
			}else if($best_match['data']['match']['away_id']==$team_id){
				$against = $best_match['data']['match']['home_name'];
			}
			
			$response['stats']['best_match'] = "VS. {$against} (+{$best_match['data']['points']})";
		}

		//last earnings
		$rs = $this->Game->getLastEarnings($game_team['id']);
		if($rs['status']==1){
			$this->set('last_earning',$rs['data']['total_earnings']);
			$response['stats']['last_earning'] = $rs['data']['total_earnings'];
		}else{
			$response['stats']['last_earning'] = 0;
		}

		//best player
		$rs = $this->Game->getBestPlayer($game_team['id']);
		
		if($rs['status']==1){
			$this->set('best_player',$rs['data']);
			$response['stats']['best_player'] = $rs['data'];
		}

		//close time
		$response['close_time'] = $this->closeTime;
		//can updte formation
		if($this->closeTime > time() && $this->openTime < time()){
			$response['can_update_formation'] = 1;	
		}else{
			$response['can_update_formation'] = 0;
		}
		
		$this->set('response',array('status'=>1,'data'=>$response));
		$this->render('default');
	}
	public function save_formation(){
		$this->loadModel('Team');
		$this->loadModel('User');
		$this->loadModel('Info');
		if($this->request->is('post')){
			$api_session = $this->readAccessToken();
			$fb_id = $api_session['fb_id'];
			$user = $this->User->findByFb_id($fb_id);
			$game_team = $this->Game->getTeam($fb_id);

			$formation = $this->request->data['formation'];

			$players = array();
			foreach($this->request->data as $n=>$v){
				if(eregi('player-',$n)&&$v!=0){
					$players[] = array('player_id'=>str_replace('player-','',$n),'no'=>intval($v));
				}
			}
			$lineup = $this->Game->setLineup($game_team['id'],$formation,$players);
			
			if($lineup['status']==1){
				$msg = "@p1_".$user['User']['id']." telah menentukan formasinya.";
				$this->Info->write('set formation',$msg);
				$this->set('response',array('status'=>1,'message'=>'Formation is been saved successfully !'));
			}else{
				$this->set('response',array('status'=>0,'error'=>'There is an error in formation setup !'));
			}
			
		}else{
			$this->set('response',array('status'=>404,'error'=>'method not found'));
		}

		$this->render('default');
	}
	public function club(){

		$this->loadModel('Point');

		$api_session = $this->readAccessToken();
		$fb_id = $api_session['fb_id'];
		
		$user = $this->User->findByFb_id($fb_id);
		
		if(strlen($user['User']['avatar_img'])<2){
			$user['User']['avatar_img'] = "http://graph.facebook.com/".$fb_id."/picture";
		}else{
			$user['User']['avatar_img'] = Configure::read('avatar_web_url').'120x120_'.$user['User']['avatar_img'];
		}
		$game_team = $this->Game->getTeam($fb_id);
		
		$response = array();

		$point = $this->Point->findByTeam_id($user['Team']['id']);

		$response['user'] = array('id'=>$user['User']['id'],
									'fb_id'=>$user['User']['fb_id'],
									'name'=>$user['User']['name'],
									'avatar_img'=>$user['User']['avatar_img']);

		$response['stats']['points'] = intval(@$point['Point']['points']) + intval(@$point['Point']['extra_points']);
		$response['stats']['rank'] = intval(@$point['Point']['rank']);

		//budget
		$budget = $this->Game->getBudget($game_team['id']);
		$response['budget'] = $budget;

		$response['stats']['club_value'] = intval($budget) + $response['stats']['points'];

		//club
		$club = $this->Team->findByUser_id($user['User']['id']);
		$response['club'] = array('id'=>$club['Team']['id'],
									'team_name'=>$club['Team']['team_name'],
									'team_id'=>$club['Team']['team_id'],
								  );

		//get original club
		$original_club = $this->Game->getClub($club['Team']['team_id']);
		$this->set('original',$original_club);
		$response['original_club'] = $original_club;

		//list of players
		$players = $this->Game->get_team_players($fb_id);

		foreach($players as $n=>$p){
			$last_performance = floatval($p['last_performance']);
			$performance_bonus = getTransferValueBonus($last_performance,intval($p['transfer_value']));
			$player[$n]['base_transfer_value'] = $p['transfer_value'];
			$players[$n]['transfer_value'] = intval($p['transfer_value']) + $performance_bonus;

		}

		$response['players'] = $players;
		

		//players weekly salaries
		$weekly_salaries = 0;
		foreach($players as $p){
			$weekly_salaries += intval(@$p['salary']);
		}

		//lineup starters
		$lineup = $this->Game->getLineup($game_team['id']);
		$response['lineup_settings'] = $lineup;



		//list of staffs
		//get officials

		$officials = $this->Game->getAvailableOfficials($game_team['id']);
		
		$staffs = array();
		foreach($officials as $official){
			if(isset($official['hired'])){
				$staffs[] = $official;
			}
		}
		
		//staff's weekly salaries
		foreach($staffs as $p){
			$weekly_salaries += intval(@$p['salary']);
		}
		$response['weekly_salaries'] = $weekly_salaries;

		$response['staffs'] = $staffs;

		//financial statements
		$finance = $this->getFinancialStatements($fb_id);
		$financial_statement['finance'] = $finance;
		$financial_statement['weekly_balances'] = $this->weekly_balances;
		$financial_statement['total_items'] = $this->finance_total_items_raw;
		$financial_statement['tickets_sold'] = $this->tickets_sold;
		//last earnings
		$rs = $this->Game->getLastEarnings($game_team['id']);
		if($rs['status']==1){
			$financial_statement['last_earning'] = $rs['data']['total_earnings'];
		}else{
			$financial_statement['last_earning'] = 0;
		}

		//last expenses
		$rs = $this->Game->getLastExpenses($game_team['id']);
		if($rs['status']==1){
			$financial_statement['last_expenses'] = $rs['data']['total_expenses'];
		}else{
			$financial_statement['last_expenses'] = 0;
		}
		$financial_statement['expenditures'] = $this->expenditures;
		$financial_statement['starting_budget'] = $this->starting_budget;



		


		$response['finance'] = $finance;
		$response['finance_details'] = $financial_statement;

		$next_match = $this->Game->getNextMatch($game_team['team_id']);
		$next_match['match']['home_original_name'] = $next_match['match']['home_name'];
		$next_match['match']['away_original_name'] = $next_match['match']['away_name'];

		if($next_match['match']['home_id']==$game_team['team_id']){
			$next_match['match']['home_name'] = $club['Team']['team_name'];
		}else{
			$next_match['match']['away_name'] = $club['Team']['team_name'];
		}

		$next_match['match']['match_date_ts'] = strtotime($next_match['match']['match_date']);
		$next_match['match']['last_match_ts'] = strtotime($next_match['match']['last_match']);

		$this->getCloseTime($next_match);

		$response['next_match'] = array('game_id'=>$next_match['match']['game_id'],
										'home_name'=>$next_match['match']['home_name'],
										'away_name'=>$next_match['match']['away_name'],
										'home_original_name'=>$next_match['match']['home_original_name'],
										'away_original_name'=>$next_match['match']['away_original_name'],
										'match_date'=>date("Y-m-d H:i:s",strtotime($next_match['match']['match_date'])),
										'match_date_ts'=>strtotime($next_match['match']['match_date'])
										);
		//match venue
		$match_venue = $this->Game->getVenue($next_match['match']['home_id']);
		$response['match_venue'] = $match_venue;

		//best match
		$best_match = $this->Game->getBestMatch($game_team['id']);
		$team_id = $game_team['team_id'];
		
		if($best_match['status']==0){
			$this->set('best_match','N/A');
			$response['stats']['best_match'] = 'N/A';
		}else{
			$best_match['data']['points'] = number_format($best_match['data']['points']);
			if($best_match['data']['match']['home_id']==$team_id){
				$against = $best_match['data']['match']['away_name'];
			}else if($best_match['data']['match']['away_id']==$team_id){
				$against = $best_match['data']['match']['home_name'];
			}
			
			$response['stats']['best_match'] = "VS. {$against} (+{$best_match['data']['points']})";
		}

		//last earnings
		$rs = $this->Game->getLastEarnings($game_team['id']);
		if($rs['status']==1){
			$this->set('last_earning',$rs['data']['total_earnings']);
			$response['stats']['last_earning'] = $rs['data']['total_earnings'];
		}else{
			$response['stats']['last_earning'] = 0;
		}

		//best player
		$rs = $this->Game->getBestPlayer($game_team['id']);
		
		if($rs['status']==1){
			$this->set('best_player',$rs['data']);
			$response['stats']['best_player'] = $rs['data'];
		}

		//close time
		$response['close_time'] = $this->closeTime;


		//weekly points and weekly balances

		//for weekly points, make sure the points from other player are included
		$this->loadModel('Weekly_point');
		$this->Weekly_point->virtualFields['TotalPoints'] = 'SUM(Weekly_point.points)';
		$options = array('fields'=>array('Weekly_point.id', 'Weekly_point.team_id', 
							'Weekly_point.game_id', 'Weekly_point.matchday', 'Weekly_point.matchdate', 
							'SUM(Weekly_point.points) AS TotalPoints', 'Team.id', 'Team.user_id', 
							'Team.team_id','Team.team_name'),
			'conditions'=>array('Weekly_point.team_id'=>$club['Team']['id']),
	        'limit' => 100,
	        'group' => 'Weekly_point.matchday',
	        'order' => array(
	            'matchday' => 'asc'
	        ));
		$weekly_points = $this->Weekly_point->find('all',$options);
		$weekly_team_points = array();
		while(sizeof($weekly_points) > 0){
			$p = array_shift($weekly_points);
			$weekly_team_points[] = array(
					'game_id'=>$p['Weekly_point']['game_id'],
					'matchday'=>$p['Weekly_point']['matchday'],
					'matchdate'=>$p['Weekly_point']['matchdate'],
					'points'=>$p[0]['TotalPoints']
				);
		}
		unset($weekly_points);


		$response['weekly_stats']['balances'] = $financial_statement['weekly_balances'];
		$response['weekly_stats']['points'] = $weekly_team_points;

		//matches
		$matches = $this->getMatches($game_team['id'],$game_team['team_id'],
										$weekly_team_points,
										$financial_statement['expenditures'],
										$financial_statement['tickets_sold']);

		$response['previous_matches'] = $matches;


		$this->set('response',array('status'=>1,'data'=>$response));
		$this->render('default');
	}

	public function player($player_id){
		$this->loadModel('Point');

		$api_session = $this->readAccessToken();
		$fb_id = $api_session['fb_id'];
		
		$user = $this->User->findByFb_id($fb_id);
		
		if(strlen($user['User']['avatar_img'])<2){
			$user['User']['avatar_img'] = "http://graph.facebook.com/".$fb_id."/picture";
		}else{
			$user['User']['avatar_img'] = Configure::read('avatar_web_url').'120x120_'.$user['User']['avatar_img'];
		}
		$game_team = $this->Game->getTeam($fb_id);
		
		$response = array();

		$point = $this->Point->findByTeam_id($user['Team']['id']);

		$response['user'] = array('id'=>$user['User']['id'],
									'fb_id'=>$user['User']['fb_id'],
									'name'=>$user['User']['name'],
									'avatar_img'=>$user['User']['avatar_img']);

		$response['stats']['points'] = intval(@$point['Point']['points']) + intval(@$point['Point']['extra_points']);
		$response['stats']['rank'] = intval(@$point['Point']['rank']);

		//budget
		$budget = $this->Game->getBudget($game_team['id']);
		$response['budget'] = $budget;

		$response['stats']['club_value'] = intval($budget) + $response['stats']['points'];

		//club
		$club = $this->Team->findByUser_id($user['User']['id']);
		$response['club'] = array('id'=>$club['Team']['id'],
									'team_name'=>$club['Team']['team_name'],
									'team_id'=>$club['Team']['team_id'],
								  );

		//get original club
		$original_club = $this->Game->getClub($club['Team']['team_id']);
		$this->set('original',$original_club);
		$response['original_club'] = $original_club;


		//player detail : 
		$rs = $this->Game->get_team_player_info($fb_id,$player_id);

		//stats modifier
		$modifiers = $this->Game->query("SELECT * FROM ffgame.game_matchstats_modifier as Modifier");
		
		if($rs['status']==1){

			if(isset($rs['data']['daily_stats'])&&sizeof($rs['data']['daily_stats'])>0){
				
				foreach($rs['data']['daily_stats'] as $n=>$v){
					$fixture = $this->Team->query("SELECT matchday,match_date,
										UNIX_TIMESTAMP(match_date) as ts
										FROM ffgame.game_fixtures 
										WHERE game_id='{$n}' 
										LIMIT 1");

					$rs['data']['daily_stats'][$n]['fixture'] = $fixture[0]['game_fixtures'];
					$rs['data']['daily_stats'][$n]['fixture']['ts'] = $fixture[0][0]['ts'];
				}
			}

			//generate stats from overall data.

			

		}
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

		$data = $rs['data'];
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
		        foreach($map as $mainstats=>$substats){
		            foreach($substats as $n=>$v){
		                
		                if($v==$stats['stats_name']){
		                    if(!isset($main_stats_vals[$mainstats])){
		                        $main_stats_vals[$mainstats] = 0;
		                        $main_stats_ori[$mainstats] = 0;
		                    }
		                    $main_stats_vals[$mainstats] += ($stats['total'] *
		                                                    $this->getModifierValue($modifiers,
		                                                                            $v,
		                                                                            $pos));

		                   
		                }
		            }
		        }
		    }
		    foreach($main_stats_vals as $n){
		        $total_points += $n;
		    }

		

			$stats = array(
				'games'=>$this->getStats('games',$pos,$modifiers,$map,$data['overall_stats']),
				'passing_and_attacking'=>$this->getStats('passing_and_attacking',$pos,$modifiers,$map,$data['overall_stats']),
				'defending'=>$this->getStats('defending',$pos,$modifiers,$map,$data['overall_stats']),
				'goalkeeping'=>$this->getStats('goalkeeper',$pos,$modifiers,$map,$data['overall_stats']),
				'mistakes_and_errors'=>$this->getStats('mistakes_and_errors',$pos,$modifiers,$map,$data['overall_stats']),

			);
		}else{
			$stats = array();
			$main_stats_vals = array();
		}
		$response['player'] = array('info'=>$data['player'],
									 'summary'=>$main_stats_vals,
										'stats'=>$stats);

		
		$this->set('response',array('status'=>1,'data'=>$response));
		$this->render('default');
	}

	private function getModifierValue($modifiers,$statsName,$pos){
	    foreach($modifiers as $m){
	        if($m['Modifier']['name']==$statsName){
	            return ($m['Modifier'][$pos]);
	        }
	    }
	    return 0;
	}
	private function getStats($category,$pos,$modifiers,$map,$stats){
	    
	    
	    $statTypes = $map[$category];
	    //pr($statTypes);
	    $collection = array();
	    foreach($stats as $s){
	        foreach($statTypes as $n=>$v){
	            if(!isset($collection[$n])){
	                $collection[$n] = array('total'=>0,'points'=>0);
	            }
	            if($s['stats_name'] == $v){
	                $collection[$n] = array('total'=>$s['total'],
	                                    'points'=>$s['total'] * $this->getModifierValue($modifiers,$v,$pos));
	            }
	        }
	    }
	    
	    return $collection;
	}
	/*
	private function getFinancialStatements($fb_id){
		$finance = $this->Game->financial_statements($fb_id);
		if($finance['status']==1){

			$report = array('total_matches' => $finance['data']['total_matches'],
							'budget' => $finance['data']['budget']);
			foreach($finance['data']['report'] as $n=>$v){
				$report[$v['item_name']] = $v['total'];
			}
			$report['total_earnings'] = intval(@$report['tickets_sold'])+
										intval(@$report['commercial_director_bonus'])+
										intval(@$report['marketing_manager_bonus'])+
										intval(@$report['public_relation_officer_bonus'])+
										intval(@$report['win_bonus']);
			return $report;
		}
	}*/
	private function getWeeklyFinancialStatement($weekly_finance){
		$weekly_statement = array();
		$total_items = array();
		while(sizeof($weekly_finance['transactions'])>0){
			$p = array_shift($weekly_finance['transactions']);
			$weekly_statement[$p['item_name']] = $p['amount'];
			$total_items[$p['item_name']] = $p['item_total'];
		}

		$weekly_statement['total_earnings'] = intval(@$weekly_statement['tickets_sold'])+
									intval(@$weekly_statement['commercial_director_bonus'])+
									intval(@$weekly_statement['marketing_manager_bonus'])+
									intval(@$weekly_statement['public_relation_officer_bonus'])+
									intval(@$weekly_statement['win_bonus'])+
									intval(@$weekly_statement['player_sold'])
									;
		return array('transaction'=>$weekly_statement,'total_items'=>$total_items);
	}
	private function getMatches($game_team_id,$team_id,$arr,$expenditures,$tickets_sold){
		
		$matches = array();
		if(sizeof($arr)>0){
			$game_ids = array();

			foreach($arr as $a){
				$game_ids[] = "'".$a['game_id']."'";
			}

			$a_game_ids = implode(',',$game_ids);
			$sql = "SELECT game_id,home_id,away_id,b.name AS home_name,c.name AS away_name,
					a.matchday,a.match_date,a.home_score,a.away_score
					FROM ffgame.game_fixtures a
					INNER JOIN ffgame.master_team b
					ON a.home_id = b.uid
					INNER JOIN ffgame.master_team c
					ON a.away_id = c.uid
					WHERE (a.home_id = '{$team_id}' 
							OR a.away_id = '{$team_id}')
					AND EXISTS (SELECT 1 FROM ffgame_stats.game_match_player_points d
								WHERE d.game_id = a.game_id 
								AND d.game_team_id = {$game_team_id} LIMIT 1)
					ORDER BY a.game_id";
			$rs = $this->Game->query($sql);
			

			foreach($rs as $n=>$r){
				$points = 0;
				$balance = 0;
				foreach($arr as $a){
					if($r['a']['matchday']==$a['matchday']){
						$points = $a['points'];
						break;
					}
				}
				foreach($tickets_sold as $b){
					if($r['a']['game_id']==$b['game_id']){
						$income = $b['total_income'];
						break;
					}
				}
				$match = $r['a'];
				if($r['b']['home_id']==$team_id){
					$match['against'] = $r['c']['away_name'];
				}else{
					$match['against'] = $r['b']['home_name'];
				}
				$match['home_name'] = $r['b']['home_name'];
				$match['away_name'] = $r['c']['away_name'];
				$match['points'] = intval(@$points);
				$match['income'] = intval(@$income);
				$matches[] = $match;
			}

			//clean memory
			$rs = null;
			unset($rs);
		}
		return $matches;
	}
	private function getFinancialStatements($fb_id){
		$finance = $this->Game->financial_statements($fb_id);
		
		$this->weekly_balances = @$finance['data']['weekly_balances'];
		$this->expenditures = @$finance['data']['expenditures'];
		$this->starting_budget = @intval($finance['data']['starting_budget']);
		$this->tickets_sold = @$finance['data']['tickets_sold'];

		if($finance['status']==1){

			$report = array('total_matches' => $finance['data']['total_matches'],
							'budget' => $finance['data']['budget']);
			$total_items = array();
			foreach($finance['data']['report'] as $n=>$v){
				$report[$v['item_name']] = $v['total'];
				$total_items[$v['item_name']] = $v['item_total'];
			}
			$report['total_earnings'] = intval(@$report['tickets_sold'])+
										intval(@$report['commercial_director_bonus'])+
										intval(@$report['marketing_manager_bonus'])+
										intval(@$report['public_relation_officer_bonus'])+
										intval(@$report['win_bonus'])+
										intval(@$report['player_sold'])
										;
			$this->finance_total_items_raw = $total_items;
			return $report;
		}
	}
	public function profile($act=null){
		$this->loadModel('User');
		$api_session = $this->readAccessToken();
		$fb_id = $api_session['fb_id'];
		$user = $this->User->findByFb_id($fb_id);
		if(strlen($user['User']['avatar_img'])<2){
			$user['User']['avatar_img'] = "http://graph.facebook.com/".$fb_id."/picture";
		}else{
			$user['User']['avatar_img'] = Configure::read('avatar_web_url').'120x120_'.$user['User']['avatar_img'];
		}
		
		$game_team = $this->Game->getTeam($fb_id);
		//club
		$club = $this->Team->findByUser_id($user['User']['id']);

		$next_match = $this->Game->getNextMatch($game_team['team_id']);
		$next_match['match']['home_original_name'] = $next_match['match']['home_name'];
		$next_match['match']['away_original_name'] = $next_match['match']['away_name'];

		if($next_match['match']['home_id']==$game_team['team_id']){
			$next_match['match']['home_name'] = $club['Team']['team_name'];
		}else{
			$next_match['match']['away_name'] = $club['Team']['team_name'];
		}
		$next_match['match']['match_date_ts'] = strtotime($next_match['match']['match_date']);
		$this->getCloseTime($next_match);
		
		if($act=='save'){
			if($this->request->is('post')){
				$data = array(
					'name'=>@$this->request->data['name'],
					'email'=>@$this->request->data['email'],
					'location'=>@$this->request->data['location']
				);
				$this->User->id = $user['User']['id'];
				$rs = $this->User->save($data);
				$rs['User']['next_match'] = array('game_id'=>$next_match['match']['game_id'],
										'home_name'=>$next_match['match']['home_name'],
										'away_name'=>$next_match['match']['away_name'],
										'home_original_name'=>$next_match['match']['home_original_name'],
										'away_original_name'=>$next_match['match']['away_original_name'],
										'match_date'=>date("Y-m-d H:i:s",strtotime($next_match['match']['match_date'])),
										'match_date_ts'=>strtotime($next_match['match']['match_date'])
										);
				$user['User']['close_time'] = $this->closeTime;
				$this->set('response',array('status'=>1,'data'=>$rs['User']));
			}else{
				$this->set('response',array('status'=>0,'error'=>'Cannot save profile'));
			}
			
		}else{
			$user['User']['next_match'] = array('game_id'=>$next_match['match']['game_id'],
										'home_name'=>$next_match['match']['home_name'],
										'away_name'=>$next_match['match']['away_name'],
										'home_original_name'=>$next_match['match']['home_original_name'],
										'away_original_name'=>$next_match['match']['away_original_name'],
										'match_date'=>date("Y-m-d H:i:s",strtotime($next_match['match']['match_date'])),
										'match_date_ts'=>strtotime($next_match['match']['match_date'])
										);
			$user['User']['close_time'] = $this->closeTime;
			$this->set('response',array('status'=>1,'data'=>$user['User']));
		}
		$this->render('default');
	}
	public function save_avatar(){
		$this->loadModel('User');
		$api_session = $this->readAccessToken();
		$fb_id = $api_session['fb_id'];
		$user = $this->User->findByFb_id($fb_id);
		if(isset($_FILES['name'])&&strlen($_FILES['name'])>0){
			$_FILES['file']['name'] = str_replace(array(' ','\''),"_",$_FILES['file']['name']);
			if(move_uploaded_file($_FILES['file']['tmp_name'],
					Configure::read('avatar_img_dir').$_FILES['file']['name'])){
				//resize to 120x120 pixels
				$thumb = new Thumbnail();
				$thumb->resizeImage('resizeCrop', $_FILES['file']['name'], 
								Configure::read('avatar_img_dir'), 
								'120x120_'.$_FILES['file']['name'], 
								120, 
								120, 
								100);
				//save to db
				$data = array(
					'avatar_img'=>$_FILES['file']['name']
				);
				if(intval($user['User']['id']) > 0){
					$this->User->id = $user['User']['id'];
					$rs = $this->User->save($data);
					$this->set('response',array('status'=>1,'files'=>$_FILES['file']['name']));	
				}else{
					$this->set('response',array('status'=>400,'error'=>'User not found'));
				}
				
			}else{
				$this->set('response',array('status'=>0,'error'=>'cannot save the uploaded file.'));
			}
		}else if(isset($_POST['file'])){
			$buffer = base64_decode($_POST['file']);
			$new_filename = 'f'.time().rand(0,99999).".jpg";
			$fp = fopen(Configure::read('avatar_img_dir').$new_filename, "wb");
			$w = fwrite($fp, $buffer);
			fclose($fp);
			
			//resize to 120x120 pixels
			$thumb = new Thumbnail();
			$thumb->resizeImage('resizeCrop', $new_filename, 
							Configure::read('avatar_img_dir'), 
							'120x120_'.$new_filename, 
							120, 
							120, 
							100);
			
			if($w){
				//save to db
				$data = array(
					'avatar_img'=>$new_filename
				);
				if(intval($user['User']['id']) > 0){
					$this->User->id = $user['User']['id'];
					$rs = $this->User->save($data);
					$this->set('response',array('status'=>1,'files'=>$new_filename));	
				}else{
					$this->set('response',array('status'=>400,'error'=>'User not found'));
				}
			}else{
				$this->set('response',array('status'=>501,'error'=>'no file uploaded'));
			}
			
			//$this->set('response',array('status'=>2,'error'=>'masih testing','file'=>Configure::read('avatar_img_dir').$new_filename));
			
		}else{
			$this->set('response',array('status'=>500,'error'=>'no file uploaded'));
		}
		$this->render('default');
	}
	private function getCloseTime($nextMatch){
		
		$this->nextMatch = $nextMatch;

		$previous_close_dt = date("Y-m-d", strtotime("previous Saturday"))." 17:00:00";
		

		$close_dt = date("Y-m-d", strtotime("next Saturday"))." 17:00:00";
		
		$next_match_ts = $this->nextMatch['match']['match_date_ts'];
		if(date_default_timezone_get()=='Asia/Jakarta'){
		    $next_match_ts += 6*60*60;
		}
		
		if($next_match_ts > strtotime($close_dt)){
			$close_time = array("datetime"=>$close_dt,
							"ts"=>strtotime($close_dt));
		}else{
			$close_time = array("datetime"=>$previous_close_dt,
							"ts"=>strtotime($previous_close_dt));
		}
		$this->openTime = $this->nextMatch['match']['last_match_ts'];
		$this->closeTime = $close_time;
	}

	public function test(){
		$this->set('response',array('status'=>1,'data'=>array()));
		$this->render('default');
	}
	private function getRingkasanClub(){

	}

	//transfer players stuffs//////
	public function team_list(){
		$teams = $this->Game->getMatchResultStats();

		foreach($teams['data'] as $n=>$v){
			$teams['data'][$n]['stats']['points_earned'] = ($v['stats']['wins'] * 3) + 
															($v['stats']['draws']);
		}
		$rs = $this->sortTeamByPoints($teams['data']);
		$this->set('response',array('status'=>1,'data'=>$rs));
		$this->render('default');
	}
	private function sortTeamByPoints($teams){
		
		$changes = false;
		$n = sizeof($teams);
		for($i=1;$i<sizeof($teams);$i++){
			$swap = false;
			$p = $teams[$i-1];
			$q = $teams[$i];
			$p['stats']['goals'] = intval(@$p['stats']['goals']);
			$p['stats']['conceded'] = intval(@$p['stats']['conceded']);

			$q['stats']['goals'] = intval(@$q['stats']['goals']);
			$q['stats']['conceded'] = intval(@$q['stats']['conceded']);

			if($q['stats']['points_earned'] > $p['stats']['points_earned']){
				$swap = true;
			}else if($q['stats']['points_earned'] == $p['stats']['points_earned']){
				//the most goals wins
				if(($q['stats']['goals'] - $q['stats']['conceded']) > ($p['stats']['goals'] - $p['stats']['conceded'])){
					$swap = true;
				}else if(($q['stats']['goals'] - $q['stats']['conceded']) == ($p['stats']['goals'] - $p['stats']['conceded'])){
					if($q['stats']['goals'] > $p['stats']['goals']){
						$swap = true;
					}
				}
			}
			
			if($swap){
				$changes = true;
				$teams[$i] = $p;
				$teams[$i-1] = $q;
			}

		}
		if($changes){
			return $this->sortTeamByPoints($teams);
		}
		return $teams;

	}

	public function view_team($team_id){
		$this->loadModel('User');
		$api_session = $this->readAccessToken();
		$fb_id = $api_session['fb_id'];
		$userData = $this->User->findByFb_id($fb_id);

		
		$club = $this->Game->getClub($team_id);
		
		$players = $this->Game->getMasterTeam($team_id);

		//list of players
		$my_players = $this->Game->get_team_players($fb_id);
		
		$player_list = array();
		while(sizeof($players)>0){
			$p = array_shift($players);
			$p['stats']['points'] = floatval($p['stats']['points']);
			if(!$this->isMyPlayer($p['uid'],$my_players)){
				$player_list[] = $p;
			}
		}
		
		$rs = array('club'=>$club,
					'players'=>$player_list);

		$this->set('response',array('status'=>1,'data'=>$rs));
		$this->render('default');
	}

	private function isMyPlayer($player_id,$my_players){
		foreach($my_players as $m){
			if($m['uid']==$player_id){
				return true;
			}
		}
	}
	public function view_player($player_id){
		$this->loadModel('User');
		$this->loadModel('Point');
		$api_session = $this->readAccessToken();
		$fb_id = $api_session['fb_id'];
		$user = $this->User->findByFb_id($fb_id);
	
		
		if(strlen($user['User']['avatar_img'])<2){
			$user['User']['avatar_img'] = "http://graph.facebook.com/".$fb_id."/picture";
		}else{
			$user['User']['avatar_img'] = Configure::read('avatar_web_url').'120x120_'.$user['User']['avatar_img'];
		}

		$game_team = $this->Game->getTeam($fb_id);
		
		$response = array();
		
		

		$point = $this->Point->findByTeam_id($user['Team']['id']);
		$response['user'] = array('id'=>$user['User']['id'],
									'fb_id'=>$user['User']['fb_id'],
									'name'=>$user['User']['name'],
									'avatar_img'=>$user['User']['avatar_img']);

		$response['stats']['points'] = intval(@$point['Point']['points']) + intval(@$point['Point']['extra_points']);
		$response['stats']['rank'] = intval(@$point['Point']['rank']);

		//budget
		$budget = $this->Game->getBudget($game_team['id']);
		$response['budget'] = $budget;

		//club
		$club = $this->Team->findByUser_id($user['User']['id']);
		$response['club'] = array('id'=>$club['Team']['id'],
									'team_name'=>$club['Team']['team_name'],
									'team_id'=>$club['Team']['team_id'],
								  );

		//get original club
		$original_club = $this->Game->getClub($club['Team']['team_id']);
		$this->set('original',$original_club);
		$response['original_club'] = $original_club;


		//player detail : 
		$rs = $this->Game->get_player_info($player_id);
		
		
		
		
		//stats modifier
		$modifiers = $this->Game->query("SELECT * FROM ffgame.game_matchstats_modifier as Modifier");

		if($rs['status']==1){

			if(isset($rs['data']['daily_stats'])&&sizeof($rs['data']['daily_stats'])>0){
				foreach($rs['data']['daily_stats'] as $n=>$v){
					$fixture = $this->Team->query("SELECT matchday,match_date,
										UNIX_TIMESTAMP(match_date) as ts
										FROM ffgame.game_fixtures 
										WHERE game_id='{$n}' 
										LIMIT 1");
					
					$rs['data']['daily_stats'][$n]['fixture'] = $fixture[0]['game_fixtures'];
					$rs['data']['daily_stats'][$n]['fixture']['ts'] = $fixture[0][0]['ts'];
				}
			}
			
			
		}
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
		
		$data = $rs['data'];

		
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
		        foreach($map as $mainstats=>$substats){
		            foreach($substats as $n=>$v){
		                
		                if($v==$stats['stats_name']){
		                    if(!isset($main_stats_vals[$mainstats])){
		                        $main_stats_vals[$mainstats] = 0;
		                        $main_stats_ori[$mainstats] = 0;
		                    }
		                    $main_stats_vals[$mainstats] += ($stats['total'] *
		                                                    $this->getModifierValue($modifiers,
		                                                                            $v,
		                                                                            $pos));

		                   
		                }
		            }
		        }
		    }
		    foreach($main_stats_vals as $n){
		        $total_points += $n;
		    }

			

			$stats = array(
				'games'=>$this->getStats('games',$pos,$modifiers,$map,$data['overall_stats']),
				'passing_and_attacking'=>$this->getStats('passing_and_attacking',$pos,$modifiers,$map,$data['overall_stats']),
				'defending'=>$this->getStats('defending',$pos,$modifiers,$map,$data['overall_stats']),
				'goalkeeping'=>$this->getStats('goalkeeper',$pos,$modifiers,$map,$data['overall_stats']),
				'mistakes_and_errors'=>$this->getStats('mistakes_and_errors',$pos,$modifiers,$map,$data['overall_stats']),

			);
			
		}
		$response['player'] = array('info'=>$data['player'],
									 'summary'=>$main_stats_vals,
										'stats'=>$stats);
		
		
		$this->set('response',array('status'=>1,'data'=>$response));
		$this->render('default');
	}
	/**
	* buy a player
	*/
	public function buy(){
		$this->loadModel('Team');
		$this->loadModel('User');
		$api_session = $this->readAccessToken();
		$fb_id = $api_session['fb_id'];
		$user = $this->User->findByFb_id($fb_id);
		
		
		if(strlen($user['User']['avatar_img'])<2){
			$user['User']['avatar_img'] = "http://graph.facebook.com/".$fb_id."/picture";
		}else{
			$user['User']['avatar_img'] = Configure::read('avatar_web_url').'120x120_'.$user['User']['avatar_img'];
		}

		$game_team = $this->Game->getTeam($fb_id);

		$player_id = Sanitize::clean($this->request->data['player_id']);

		$window = $this->Game->transfer_window();
		$window_id = intval(@$window['id']);
		
		//check if the transfer window is opened, or the player is just registered within 24 hours
		$is_new_user = false;
		$can_transfer = false;

		if(time()<strtotime($user['User']['register_date'])+(24*60*60)){
			$is_new_user = true;
		}

		if(!$is_new_user){
			if(strtotime(@$window['tw_open']) <= time() && strtotime(@$window['tw_close'])>=time()){
				$can_transfer = true;
				
			}
		}else{
			$can_transfer = true;
		}

		if(strlen($player_id)<2){
			
			$rs = array('status'=>'0','error'=>'no data available');

		}else{
			if($can_transfer){
				$rs = $this->Game->buy_player($window_id,$game_team['id'],$player_id);
			
				//reset financial statement
				$this->Session->write('FinancialStatement',null);
				

				if(@$rs['status']==1){
					$msg = "@p1_".$user['User']['id']." telah membeli {$rs['data']['name']} seharga SS$".number_format($rs['data']['transfer_value']);
					
				}else{
					$rs = array('status'=>'0','error'=>'Transaction Failed');
				}
			}else{
				$rs = array('status'=>3,'message'=>'Transfer window is closed');
			}
		}

		

		$this->set('response',$rs);
		$this->render('default');
	}
}
