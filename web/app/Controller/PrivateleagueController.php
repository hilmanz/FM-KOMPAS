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
		$this->loadModel('leagueInvitation');
		$this->loadModel('User');

		$userData = $this->getUserData();
		$user = $this->userDetail;
		$this->set('user',$user['User']);
		if(!$this->hasTeam()){
			$this->redirect('/login/expired');
		}
	}

	public function hasTeam(){
		$userData = $this->getUserData();
		if(is_array($userData['team'])){
			return true;
		}
	}

	public function index()
	{
		$userData = $this->getUserData();
		$rs_user = $this->User->findByFb_id($userData['fb_id']);
		$team_id 	= $rs_user['Team']['id'];

		$rs = $this->League->getLeague($team_id);
		$this->set('rs', $rs);
	}

	public function create()
	{
		$this->Session->delete('league_id');
		
		$userData 	= $this->getUserData();
		$rs_user 	= $this->User->findByFb_id($userData['fb_id']);
		$user_id 	= $rs_user['User']['id'];
		$email 		= $rs_user['User']['email'];
		$team_id 	= $rs_user['Team']['id'];

		//check if user has create private league or user has joined private league
		$result = $this->League->checkUser($email, $team_id);

		if($result['has_invited'] == 1)
		{
			//page join
			$this->redirect('/privateleague/join_confirmation');
		}
		else if($result['has_joined'] == 1)
		{
			//redirect to /privateleague
			$this->Session->setFlash('Loe gak bisa buat private league lagi');
			$this->redirect('/privateleague');
		}

		if($this->request->is("post"))
		{
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
						
						$data = array('name' => $league_name,
									'logo' => $filename,
									'type' => 'private_league',
									'user_id' => $user_id,
									'max_player' => 30,
									'date_created' => date("Y-m-d H:i:s"),
									'n_status' => 1
										);

						try{
							$dataSource = $this->League->getDataSource();
							$dataSource->begin();

							$this->League->create();
							$this->League->save($data);

							$league_id = $this->League->id;

							$this->League->query("INSERT INTO 
												league_invitations(league_id, email, n_status) 
												VALUES('{$league_id}','{$email}',1)");

							$this->League->query("INSERT INTO league_member
														(league_id,team_id,join_date,n_status) 
														VALUES('{$league_id}','{$team_id}',now(),1)");

							$dataSource->commit();
							$this->Session->write('league_id', $league_id);
							$this->redirect('/privateleague/invite');

						}catch(Exception $e){
							$dataSource->rollback();
							$this->Session->setFlash('Terjadi kesalahan, silahkan coba lagi');
							$this->redirect('/privateleague/error');
						}
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
				$email = $this->request->data['email'];
				
				$i=0;
				foreach ($email as $value)
				{
					$data[] = array('league_id' => $league_id,
									'email' => $value,
									'n_status' => 0);
					if($i == 28){
						break;
					}
					$i++;
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

	public function join_confirmation()
	{
		$userData 	= $this->getUserData();
		$rs_user 	= $this->User->findByFb_id($userData['fb_id']);
		$email 		= $rs_user['User']['email'];

		$rs_league = $this->League->query("SELECT * FROM ".Configure::read('FRONTEND_SCHEMA').".league_invitations a 
											INNER JOIN league b ON a.league_id = b.id 
											INNER JOIN users c ON b.user_id = c.id 
											WHERE a.email = '{$email}' AND a.n_status = 0
											LIMIT 1");
		if(count($rs_league) > 0)
		{
			$this->set('rs_league', $rs_league);
		}
		else
		{
			$this->redirect('/privateleague');
		}

	}

	public function join()
	{
		$userData 	= $this->getUserData();
		$rs_user 	= $this->User->findByFb_id($userData['fb_id']);
		$email 		= $rs_user['User']['email'];
		$team_id 	= $rs_user['Team']['id'];

		try{
			$dataSource = $this->League->getDataSource();
			$dataSource->begin();
			
			$rs_league = $this->leagueInvitation->findByEmail($email);
			$league_id = $rs_league['leagueInvitation']['league_id'];

			$this->leagueInvitation->updateAll(
								array('n_status' => 1),
								array('email' => $email, 'n_status' => 0));
			$this->League->query("INSERT INTO league_member(league_id,team_id,join_date,n_status)
									VALUES('{$league_id}','{$team_id}',now(), 1)");
			$dataSource->commit();
			$this->redirect('/privateleague');
		}catch(Exception $e){
			$dataSource->rollback();
			$this->leagueInvitation->updateAll(
								array('n_status' => 2),
								array('email' => $email, 'n_status' => 0));
			$this->Session->setFlash("Low gak bisa join karena sudah join private league sebelumnya");
			$this->redirect("/privateleague/error");
		}
	}

	public function reject()
	{
		$userData 	= $this->getUserData();
		$rs_user 	= $this->User->findByFb_id($userData['fb_id']);
		$email 		= $rs_user['User']['email'];

		$this->leagueInvitation->updateAll(
								array('n_status' => 2),
								array('email' => $email, 'n_status' => 0));
		$this->redirect('/privateleague');

	}

	public function error()
	{

	}

	public function leaderboard()
	{
		$userData 	= $this->getUserData();
		$rs_user 	= $this->User->findByFb_id($userData['fb_id']);
		$team_id 	= $rs_user['Team']['id'];

		$matchday = 0;

		$rs_leaguetable = $this->League->query("SELECT matchday FROM league_table 
												ORDER BY id DESC LIMIT 1");

		if(count($rs_leaguetable) > 0){
			$matchday = $rs_leaguetable[0]['league_table']['matchday'];
		}

		$rs_league = $this->League->getLeague($team_id);

		$rs_leaderboard = $this->League->query("SELECT a.*,b.*,c.*,d.name FROM league_member a 
												INNER JOIN league_table b ON a.league_id = b.league_id
												INNER JOIN teams c ON b.team_id = c.id
												INNER JOIN users d ON c.user_id = d.id
												WHERE b.league_id='{$rs_league[0]['b']['id']}' 
												AND b.matchday='{$matchday}'
												GROUP BY b.team_id
												ORDER BY b.points DESC
												LIMIT 1000");

		$this->set('rs_leaderboard', $rs_leaderboard);
	}
}