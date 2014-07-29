<?php
App::uses('AppController', 'Controller');
App::uses('Sanitize', 'Utility');


class PrivateleagueController extends AppController {

	/**
	 * Controller name
	 *
	 * @var string
	 */

	public $name = 'Privateleague';
	public $components = array('Thumbnail');

	public function beforeFilter()
	{
		parent::beforeFilter();
		$this->loadModel('League');
		$this->loadModel('User');
	}

	public function index()
	{

	}

	public function create()
	{
		$this->Session->delete('league_id');
		if($this->request->is("post"))
		{
			$userData = $this->getUserData();
			$upload_dir	= Configure::read('privateleague_web_dir');

			$league_name = Sanitize::clean($this->request->data['name']);
			$league_logo = $_FILES['logo'];
			if(is_array($league_logo))
			{
				$allow_ext = array('jpeg', 'jpg', 'gif');
				$aFile = explode('.', $league_logo['name']);
				$file_ext = array_pop($aFile);
				$filename = date("ymdhis").'-'.rand(0, 99999).'.'.$file_ext;
				if(in_array($file_ext, $allow_ext))
				{
					if(move_uploaded_file($league_logo['tmp_name'], $upload_dir.$filename))
					{
						$this->Thumbnail->create($upload_dir.$filename,
														$upload_dir.'thumb_'.$filename,
														150,150);
						$rs_user = $this->User->findByFb_id($userData['fb_id']);
						$user_id = $rs_user['User']['id'];
						$team_id = $rs_user['Team']['id'];

						$data = array('name' => $league_name,
									'logo' => $filename,
									'type' => 'private_league',
									'user_id' => $user_id,
									'max_player' => 30,
									'date_created' => date("Y-m-d H:i:s"),
									'n_status' => 1
										);

						$this->League->create();
						$this->League->save($data);

						$league_id = $this->League->id;
						$this->League->query("INSERT INTO league_member
													(league_id,team_id,join_date,n_status) 
													VALUES('{$league_id}','{$team_id}',now(),1)");
						$this->Session->write('league_id', $league_id);
						$this->redirect('/privateleague/invite/'.$league_id);
					}
				}
			}
		}
	}

	public function invite()
	{
		$league_id = $this->Session->read('league_id');
		if($league_id == NULL): $this->redirect('/privateleague'); endif;

		if($this->request->is('post'))
		{
			try{
				$this->loadModel('leagueInvitation');
				$email = $this->request->data['email'];
				foreach ($email as $value)
				{
					$data[] = array('league_id' => $league_id,
									'email' => $value,
									'n_status' => 0);
				}

				$this->leagueInvitation->saveMany($data);
				
				$this->Session->delete('league_id');
				$this->Session->setFlash('Berhasil');
				$this->redirect('/privateleague');
			}catch(Exception $e){
				$this->Session->setFlash('Terjadi kesalahan, silahkan coba lagi');
				$this->redirect('/privateleague/error');
			}
		}
	}

	public function error()
	{

	}
}