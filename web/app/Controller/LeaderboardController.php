<?php
/**
 * Leaderboard Controller

 */
App::uses('AppController', 'Controller');
App::uses('Sanitize', 'Utility');

class LeaderboardController extends AppController {

/**
 * Controller name
 *
 * @var string
 */
	public $name = 'Leaderboard';

/**
 * This controller does not use a model
 *
 * @var array
 */
	public $uses = array();
	public function beforeFilter(){
		parent::beforeFilter();
		$userData = $this->getUserData();
		
	}
	public function hasTeam(){
		$userData = $this->getUserData();
		if(is_array($userData['team'])){
			return true;
		}
	}

	public function index(){
		$this->paginate = array(
	        'limit' => 100,
	        'order' => array(
	            'Point.points' => 'desc'
	        )
	    );
	    $this->loadModel("Point");
	    $this->loadModel('User');
	    $rs = $this->paginate('Point');
	    foreach($rs as $n=>$r){
	    	//get manager's name
	    	$manager = $this->User->findById($r['Team']['user_id']);
	    	$rs[$n]['Manager'] = $manager['User'];
	    }
	    $this->set('team',$rs);
	}

	public function error(){
		$this->render('error');
	}
	public function team_error(){
		$this->set('error_type','team');
		$this->render('error');
	}
	public function success(){
		$this->render('success');
	}
}
