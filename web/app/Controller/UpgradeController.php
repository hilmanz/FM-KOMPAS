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
		$this->loadModel('MembershipTransactions');
		$this->loadModel('Team');
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
		$this->redirect('/profile');
	}

	public function member()
	{
		$userData = $this->userData;
		$rs_user = $this->User->findByFb_id($userData['fb_id']);
		$transaction_id = intval($rs_user['User']['id']).'-'.date("YmdHis").'-'.rand(0,999);
		$description = 'Purchase Order #'.$transaction_id;

		$amount = 10000;
		if($this->checkTotalTeam() > 1)
		{
			$amount = 20000;
		}

		$rs = $this->Game->getEcashUrl(array(
			'transaction_id'=>$transaction_id,
			'description'=>$description,
			'amount'=>$amount,
			'clientIpAddress'=>$this->request->clientIp(),
			'source'=>'FMUPGRADE'
		));

		$this->set('rs', $rs);
	}

	public function member_success()
	{
		$id = $this->request->query['id'];

		$rs = $this->Game->EcashValidate($id);

		if(isset($rs['data']) && $rs['data'] != '')
		{
			$data = explode(',', $rs['data']);
			if(isset($data[4]) && trim($data[4]) == "SUCCESS")
			{
				$userData = $this->userData;
				$transaction_name = 'Purchase Order #'.$data[3];
				$detail = json_encode($rs['data']);
				$amount = 10000;
				if($this->checkTotalTeam() > 1)
				{
					$amount = 20000;
				}

				try{
					$dataSource = $this->User->getDataSource();
					$dataSource->begin();
					$save_data = array(
										'fb_id' => $userData['fb_id'],
										'transaction_dt' => date("Y-m-d H:i:s"),
										'transaction_name' => $transaction_name,
										'transaction_type' => 'UPGRADE MEMBER',
										'amount' => $amount,
										'details' => $detail
									);
					$this->MembershipTransactions->save($save_data);
					$this->MembershipTransactions->query("INSERT INTO member_billings
												(fb_id,log_dt,expire)
												VALUES('{$userData['fb_id']}',
														NOW(), NOW() + INTERVAL 1 MONTH)");

					$this->User->query("UPDATE users SET paid_member=1,paid_member_status=1 
										WHERE fb_id='{$userData['fb_id']}'");

					$dataSource->commit();
				}catch(Exception $e){
					$dataSource->rollback();
					Cakelog::write('error', 'Upgrade.member_success 
						id='.$id.' data:'.json_encode($data).' message:'.$e->getMessage());
					$this->render('error');
				}
			}
			else
			{
				Cakelog::write('error', 'Upgrade.member_success id='.$id.' data:'.json_encode($data));
				$this->render('error');
			}
		}
		else
		{
			Cakelog::write('error', 'Upgrade.member_success '.$id.'Not Found');
			$this->render('error');
		}
	}


	public function paymonthly()
	{
		$userData = $this->userData;
		$rs_user = $this->User->findByFb_id($userData['fb_id']);

		if($rs_user['User']['paid_member'] == 1 && $rs_user['User']['paid_member_status'] == 0)
		{
			$userData = $this->userData;
			$rs_user = $this->User->findByFb_id($userData['fb_id']);
			$transaction_id = intval($rs_user['User']['id']).'-'.date("YmdHis").'-'.rand(0,999);
			$description = 'Purchase Order #'.$transaction_id;

			$amount = 10000;
			if($this->checkTotalTeam() > 1)
			{
				$amount = 20000;
			}

			$rs = $this->Game->getEcashUrl(array(
				'transaction_id'=>$transaction_id,
				'description'=>$description,
				'amount'=>$amount,
				'clientIpAddress'=>$this->request->clientIp(),
				'source'=>'FMRENEWAL'
			));

			$this->set('rs', $rs);
		}
		else
		{
			$this->redirect('/profile');
		}

	}

	public function renewal()
	{
		$id = $this->request->query['id'];

		$rs = $this->Game->EcashValidate($id);

		if(isset($rs['data']) && $rs['data'] != '')
		{
			$data = explode(',', $rs['data']);
			if(isset($data[4]) && trim($data[4]) == "SUCCESS")
			{
				$userData = $this->userData;
				$transaction_name = 'Purchase Order #'.$data[3];
				$detail = json_encode($rs['data']);
				$amount = 10000;
				if($this->checkTotalTeam() > 1)
				{
					$amount = 20000;
				}

				try{
					$dataSource = $this->User->getDataSource();
					$dataSource->begin();
					$save_data = array(
										'fb_id' => $userData['fb_id'],
										'transaction_dt' => date("Y-m-d H:i:s"),
										'transaction_name' => $transaction_name,
										'transaction_type' => 'RENEWAL MEMBER',
										'amount' => $amount,
										'details' => $detail
									);
					$this->MembershipTransactions->save($save_data);
					$this->MembershipTransactions->query("UPDATE member_billings
												SET log_dt=NOW(),expire=NOW() + INTERVAL 1 MONTH
												WHERE fb_id='{$userData['fb_id']}'");
					
					$this->User->query("UPDATE users SET paid_member_status=1 
										WHERE fb_id='{$userData['fb_id']}'");

					$dataSource->commit();
				}catch(Exception $e){
					$dataSource->rollback();
					Cakelog::write('error', 'Upgrade.renewal 
						id='.$id.' data:'.json_encode($data).' message:'.$e->getMessage());
					$this->render('error');
				}
			}
			else
			{
				Cakelog::write('error', 'Upgrade.renewal id='.$id.' data:'.json_encode($data));
				$this->render('error');
			}
		}
		else
		{
			Cakelog::write('error', 'Upgrade.renewal '.$id.'Not Found');
			$this->render('error');
		}
	}

	private function checkTotalTeam()
	{
		$userData 	= $this->userData;
		$rs_user 	= $this->User->findByFb_id($userData['fb_id']);
		$total_team = $this->Team->find('count', array(
	        'conditions' => array('Team.user_id' => $rs_user['User']['id'])
	    ));

	    return $total_team;
	}
}