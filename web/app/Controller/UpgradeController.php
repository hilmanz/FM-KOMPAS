<?php
App::uses('AppController', 'Controller');
App::uses('Sanitize', 'Utility');


class UpgradeController extends AppController {

	/**
	 * Controller name
	 *
	 * @var string
	 */

	public $name = 'Upgrade';

	public function beforeFilter()
	{
		parent::beforeFilter();
		$this->loadModel('Game');
		$this->loadModel('User');
		if(!$this->hasTeam()){
			$this->redirect('/login/expired');
		}
	}

	public function hasTeam(){
		$userData = $this->userData;
		if(is_array($userData['team'])){
			return true;
		}
	}
	
	public function index()
	{
		
	}

	public function member()
	{
		$userData = $this->userData;
		$rs_user = $this->User->findByFb_id($userData['fb_id']);
		$transaction_id = intval($rs_user['User']['id']).'-'.date("YmdHis").'-'.rand(0,999);
		$description = 'Purchase Order #'.$transaction_id;

		$rs = $this->Game->getEcashUrl(array(
			'transaction_id'=>$transaction_id,
			'description'=>$description,
			'amount'=>10000,
			'clientIpAddress'=>$this->request->clientIp(),
			'source'=>'fm'
		));
	}
}