<?php
/**
* Game Fixtures Monitoring.
*
*/
App::uses('AppController', 'Controller');


class ScheduleController extends AppController {

/**
 * Controller name
 *
 * @var string
 */
	public $name = 'Schedule';

	public function index(){
		$rs = $this->Schedule->query("SELECT Fixture.id,game_id,home_id,away_id,
									period,matchday,home_score,away_score,
									is_processed,attendance,match_date,
									Home.name,Away.name,
									Home.stadium_name 
									FROM ffgame.game_fixtures Fixture
									INNER JOIN ffgame.master_team Home
									ON Fixture.home_id = Home.uid
									INNER JOIN ffgame.master_team Away
									ON Fixture.away_id = Away.uid
									ORDER BY Fixture.matchday ASC,
									Fixture.match_date ASC;");
		$this->set('match',$rs);
	}
	public function edit($game_id){
		if($this->request->is('post')){
			$dt = $this->request->data['dt'];
			$tm = $this->request->data['tm'];
			$match_date = $dt." ".$tm;
			$this->Schedule->query("UPDATE ffgame.game_fixtures SET match_date='{$match_date}'
									WHERE game_id='{$game_id}'");
		}
		//edit schedule page
		$rs = $this->Schedule->query("SELECT Fixture.id,game_id,home_id,away_id,
									period,matchday,home_score,away_score,
									is_processed,attendance,match_date,
									Home.name,Away.name,
									Home.stadium_name 
									FROM ffgame.game_fixtures Fixture
									INNER JOIN ffgame.master_team Home
									ON Fixture.home_id = Home.uid
									INNER JOIN ffgame.master_team Away
									ON Fixture.away_id = Away.uid
									WHERE Fixture.game_id='{$game_id}'
									LIMIT 1");

		$this->set('rs',$rs[0]);

		$rs = $this->Game->getPostponedMatch($game_id);
		$this->set('is_postponed',$rs['status']);
	}

	public function matchday(){
		$this->loadModel('MasterMatchday');
		$rs = $this->MasterMatchday->find('all',array('order'=>array('matchday'=>'ASC')));
		$this->set('match',$rs);
	}
	public function edit_matchday($id){
		
		$this->loadModel('MasterMatchday');
		$this->MasterMatchday->id = $id;
		if($this->request->is('post')){
			$this->MasterMatchday->save($this->request->data);
		}

		$rs = $this->MasterMatchday->findById($id);
		

		$this->set('rs',$rs);
	}

	public function postponed($game_id,$toggle){
		$rs = $this->Game->setPostponedMatch($game_id,$toggle);
		if($rs['status']==1){
			$this->Session->setFlash('The match is successfully postponed !');
		}else{
			$this->Session->setFlash('Cannot postponed the match, please try again later !');
		}
		$this->redirect('/schedule/edit/'.$game_id);
	}
}
