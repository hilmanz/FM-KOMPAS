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
		$this->loadModel('GameTransaction');
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

				try{
					$dataSource = $this->User->getDataSource();
					$dataSource->begin();
					$rs_user = $this->User->findByFb_id($userData['fb_id']);
					$user_id = $rs_user['User']['id'];
					$save_data = array(
										'user_id' => $user_id,
										'transaction_dt' => date("Y-m-d H:i:s"),
										'transaction_name' => $transaction_name,
										'transaction_type' => 'UPGRADE MEMBER',
										'amount' => 10000,
										'details' => $detail
									);
					$this->GameTransaction->save($save_data);
					$this->GameTransaction->query("INSERT INTO member_billings
												(user_id,log_dt,expire)
												VALUES('{$user_id}',
														NOW(), NOW() + INTERVAL 1 MONTH)");

					$this->User->query("UPDATE users SET paid_member=1,paid_member_status=1 
										WHERE id='{$user_id}'");

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

			$rs = $this->Game->getEcashUrl(array(
				'transaction_id'=>$transaction_id,
				'description'=>$description,
				'amount'=>10000,
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

				try{
					$dataSource = $this->User->getDataSource();
					$dataSource->begin();
					$rs_user = $this->User->findByFb_id($userData['fb_id']);
					$user_id = $rs_user['User']['id'];
					$save_data = array(
										'user_id' => $user_id,
										'transaction_dt' => date("Y-m-d H:i:s"),
										'transaction_name' => $transaction_name,
										'transaction_type' => 'RENEWAL MEMBER',
										'amount' => 10000,
										'details' => $detail
									);
					$this->GameTransaction->save($save_data);
					$this->GameTransaction->query("UPDATE member_billings
												SET log_dt=NOW(),expire=NOW() + INTERVAL 1 MONTH
												WHERE user_id='{$user_id}'");
					
					$this->User->query("UPDATE users SET paid_member_status=1 WHERE id='{$user_id}'");

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
}