<?php
App::uses('AppModel', 'Model');
/**
 * Game Model
 *
 * @property status_types $status_types
 * @property document_types $document_types
 * @property topics $topics
 */
class Game extends AppModel {
	public $useTable = false; //kita gak pake table database, karena nembak API langsung.

	public function getTeam($fb_id){
		$response = $this->api_call('/team/get/'.$fb_id,array());
		if(!isset($response['error'])){
			return $response;	
		}
		return null;
	}
	public function getBudget($team_id){
		$response = $this->api_call('/team/budget/'.$team_id,array());
		return $response['budget'];
	}

	public function setProfile($data){ /*save profile*/
		$response = $this->api_post('/user/register',$data);
		return $response;
	}

	public function getTeams(){ /*load team list*/
		$response = $this->api_call('/teams');
		return $response;
	}
	
	public function getMasterTeam($team_id){
		$response = $this->api_call('/players/'.$team_id);
		return $response;
	}
	public function create_team($data){
		$response = $this->api_post('/create_team',$data);
		return $response;
	}
	public function get_team_players($fb_id){
		$response = $this->api_call('/team/get/'.$fb_id);
		if(intval($response['id'])>0){
			$team_id = intval($response['id']);
			$team = $this->api_call('/team/list/'.$team_id);
			if(sizeof($team)>0){
				return $team;
			}
		}
	}
	public function getAvailableOfficials($team_id){
		$response = $this->api_call('/official/list/'.$team_id);
		if($response['status']==1){
			return $response['officials'];
		}
	}
	public function hire_staff($team_id,$official_id){
		$response = $this->api_post('/official/hire',array(
			'team_id'=>$team_id,
			'official_id'=>$official_id
		));
		return $response;
	}
	public function dismiss_staff($team_id,$official_id){
		$response = $this->api_post('/official/fire',array(
			'team_id'=>$team_id,
			'official_id'=>$official_id
		));
		return $response;
	}
	/*
	* get current lineup settings
	* @team_id game_team_id 
	*/
	public function getLineup($team_id){
		$response = $this->api_call('/team/lineup/'.$team_id);
		return $response;
	}
	public function setLineup($team_id,$formation,$players){
		$s_players = json_encode($players);
		$response = $this->api_post('/team/lineup/save',array(
						'team_id'=>$team_id,
						'players'=>$s_players,
						'formation'=>$formation
					));
		$response['lineup'] = $players;
		return $response;
	}


	/**
	* get match list
	*
	*/
	public function getMatches(){
		$response = $this->api_call('/match/list');
		return $response;
	}
}