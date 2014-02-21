<?php
/**
 * Market Controller

 */
App::uses('AppController', 'Controller');
App::uses('Sanitize', 'Utility');

class MerchandisesController extends AppController {

/**
 * Controller name
 *
 * @var string
 */
	public $name = 'Merchandises';

/**
 * This controller does not use a model
 *
 * @var array
 */
	public $uses = array();
	public function beforeFilter(){
		parent::beforeFilter();
		$this->loadModel('Team');
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
	/**
	* the index page will display all available (in-stock) merchandises.
	*/
	public function index(){
		$this->loadModel('MerchandiseItem');
		$this->loadModel('MerchandiseCategory');
		if(isset($this->request->query['cid'])){
			$category_id = intval($this->request->query['cid']);
		}else{
			$category_id = 0;
		}
		
		$merchandise = $this->MerchandiseItem->find('count');
		if($merchandise > 0){
			$this->set('has_merchandise',true);	
		}else{
			$this->set('has_merchandise',false);
		}
		

		//bind the model's association first.
		//i'm too lazy to create a new Model Class :P
		$this->MerchandiseItem->bindModel(array(
			'belongsTo'=>array('MerchandiseCategory')
		));

		//we need to populate the category
		$this->populate_main_categories();

		//if category is set, we filter the query by category_id
		if($category_id != 0){
			$category_ids = array($category_id);
			//check for child ids, and add it into category_ids
			$category_ids = $this->getChildCategories($category_id,$category_ids);
			$this->paginate = array('conditions'=>array('merchandise_category_id'=>$category_ids),
									'limit'=>9
									);
			//maybe the category has children in it.
			//so we try to populate it
			$this->populate_sub_categories($category_id);

			//we need to know the category details
			$category = $this->MerchandiseCategory->findById($category_id);
			$this->set('category_name',h($category['MerchandiseCategory']['name']));

		}else{
			//if doesnt, we query everything.
			$this->paginate = array(
									'limit'=>9
									);
		}


		//get previous orders
		$orders = $this->getPreviousOrders();
		$this->set('orders',$orders);

		//retrieve the paginated results.
		$rs = $this->paginate('MerchandiseItem');
		for($i=0;$i<sizeof($rs);$i++){
			//get the available stock
			// stock_available = stock - total_order
			$total_order = $this->MerchandiseOrder->find('count',
				array('conditions'=>array('merchandise_item_id'=>$rs[$i]['MerchandiseItem']['id'],
										  'n_status <> 4')));
			
			$rs[$i]['MerchandiseItem']['available'] = $rs[$i]['MerchandiseItem']['stock'] - $total_order;
		}
		//assign it.
		$this->set('rs',$rs);


		


	}

	public function view($item_id){
		$this->loadModel('MerchandiseItem');
		$this->loadModel('MerchandiseCategory');
		$this->loadModel('MerchandiseOrder');

		//parno mode.
		$item_id = Sanitize::clean($item_id);

		//get the item detail
		$item = $this->MerchandiseItem->findById($item_id);
		
		$total_order = $this->MerchandiseOrder->find('count',
				array('conditions'=>array('merchandise_item_id'=>$item['MerchandiseItem']['id'],
										  'n_status <> 4')));
			
		$item['MerchandiseItem']['available'] = $item['MerchandiseItem']['stock'] - $total_order;



		$this->set('item',$item);

		$category = $this->MerchandiseCategory->findById($item['MerchandiseItem']['merchandise_category_id']);
		$this->set('category_name',h($category['MerchandiseCategory']['name']));

		


	}

	private function getPreviousOrders(){
		$this->loadModel('MerchandiseOrder');
		$game_team_id = $this->userData['team']['id'];
		
		//we need to link the order with the item
		$this->MerchandiseOrder->bindModel(
			array('belongsTo'=>array('MerchandiseItem'))
		);
		$orders = $this->MerchandiseOrder->find('all',
					array('conditions'=>array(
								'game_team_id'=>$game_team_id
							),
							'order'=>array('MerchandiseOrder.id'=>'DESC'),
						  	'limit'=>1000));
		
		return $orders;
	}
	/**
	*	get the list of child categories, 1 level under only.
	*/
	private function getChildCategories($category_id,$category_ids){
		$categories = $this->MerchandiseCategory->find('all',
														array('conditions'=>array('parent_id'=>$category_id),
															  'limit'=>100)
													);
		for($i=0;$i<sizeof($categories);$i++){
			$category_ids[] = $categories[$i]['MerchandiseCategory']['id'];
		}

		return $category_ids;
	}

	/**
	* populate main categories (all categories that has parent_id = 0)
	*/
	private function populate_main_categories(){
		//retrieve main categories
		$categories = $this->MerchandiseCategory->find('all',
														array('conditions'=>array('parent_id'=>0),
															  'limit'=>100)
													);
		$this->set('categories',$categories);
	}
	private function populate_sub_categories($category_id){
		//retrieve main categories
		$categories = $this->MerchandiseCategory->find('all',
														array('conditions'=>
															array('parent_id'=>$category_id),
															      'limit'=>100)
													);
		$this->set('sub_categories',$categories);
	}
	

	//Buy Merchandise Page.
	public function buy($item_id){
		$this->loadModel('MerchandiseItem');
		$this->loadModel('MerchandiseCategory');

		//parno mode.
		$item_id = Sanitize::clean($item_id);

		//get the item detail
		$item = $this->MerchandiseItem->findById($item_id);
		$this->set('item',$item['MerchandiseItem']);

		//generate CSRF Token
		$csrf_token = md5('purchase_order_merchandise-'.date("YmdHis").rand(0,100));
		$this->Session->write('po_csrf',$csrf_token);
		$this->set('csrf_token',$csrf_token);

		//pre-populate user details on the form
		$name = $this->getDetailedName();
		$this->set('first_name',$name['first_name']);
		$this->set('last_name',$name['last_name']);
		$this->set('phone_number',$this->userDetail['User']['phone_number']);
		$this->set('email',$this->userDetail['User']['email']);
		
		//attach the item_id
		$this->Session->write('po_item_id',$item_id);
		//dont forget to clear po_item_id session when the order is done.


		//if digital item, we display different view
		if($item['MerchandiseItem']['merchandise_type']==1){
			$this->render('redeem_perk');
		}
	}
	public function order(){
		$this->loadModel('MerchandiseItem');
		$this->loadModel('MerchandiseCategory');

		$item_id = $this->Session->read('po_item_id');
		
		//parno mode.
		$item_id = Sanitize::clean($item_id);

		//get the item detail
		$item = $this->MerchandiseItem->findById($item_id);
		if(isset($item['MerchandiseItem'])){
			$this->set('item',$item['MerchandiseItem']);	
		}
		
		//these is our flags
		$is_transaction_ok = true;
		$no_fund = false;

		//make sure the csrf token still valid

		//-> csrf check di disable dulu selama development
		if(
			(strlen($this->request->data['ct']) > 0)
				&& ($this->Session->read('po_csrf') == $this->request->data['ct'])
		  ){

			$result = $this->pay_with_game_cash($item_id,$item);
			$is_transaction_ok = $result['is_transaction_ok'];
			$no_fund = $result['no_fund'];
			if($is_transaction_ok == true){
				//we reduce the stock in front
				//$this->ReduceStock($item_id,$item['MerchandiseItem']);
			}
		}else{
			$is_transaction_ok = false;
		}
		
		$this->set('apply_digital_perk_error',$this->Session->read('apply_digital_perk_error'));
		$this->set('is_transaction_ok',$is_transaction_ok);
		$this->set('no_fund',$no_fund);
		$this->Session->write('apply_digital_perk_error',null);
		//reset the csrf token
		$this->Session->write('po_csrf',null);
		//-->

		//reset the item_id in session (disable these for debug only)
		$this->Session->write('po_item_id',0);
	}
	private function ReduceStock($item_id,$item){
		$item_id = intval($item_id);
		if($item['stock']>0){
			$this->MerchandiseItem->query("UPDATE merchandise_items SET stock = stock - 1 WHERE id = {$item_id}");	
		}
		
	}
	private function pay_with_ingame_funds($item_id,$item){
		//if valid, 
		//save the order to database
		//at these time, we assume that user will pay with in-game funds
		$data = $this->request->data;
		$data['merchandise_item_id'] = $this->Session->read('po_item_id');
		$data['game_team_id'] = $this->userData['team']['id'];
		$data['user_id'] = $this->userDetail['User']['id'];
		$data['order_type'] = 0;
		$data['n_status'] = 0;
		$data['order_date'] = date("Y-m-d H:i:s");
		$data['po_number'] = $item_id.'-'.$data['game_team_id'].'-'.date("ymdhis");

		//oops, before that, we need to know if user has sufficient funds
		

		$finance = $this->Game->financial_statements($this->userData['fb_id']);
		
		if(intval($finance['data']['budget']) > 
				intval($item['MerchandiseItem']['price_currency'])){
			$no_fund = false;
		}else{
			$no_fund = true;
		}
		

		$this->loadModel('MerchandiseOrder');
		
		if(!$no_fund){
			//ok the user has enough fund... purchase it now.
			$this->MerchandiseOrder->create();
			$rs = $this->MerchandiseOrder->save($data);	

			if($rs){
				//get next match's id
				
				$match = $this->nextMatch['match'];
				$game_id = $match['game_id'];
				$matchday = $match['matchday'];
				//time to deduct the money
				$this->Game->query("
				INSERT IGNORE INTO ffgame.game_team_expenditures
				(game_team_id,item_name,item_type,
				 amount,game_id,match_day,item_total,base_price)
				VALUES
				({$data['game_team_id']},'purchase merchandise - {$data['po_number']}',
				  2,-{$item['MerchandiseItem']['price_currency']},
				  '{$game_id}',{$matchday},1,1);");
				
				$is_transaction_ok = true;

			}else{
				$is_transaction_ok = false;
			}
		}else{
			$is_transaction_ok = false;
			$no_fund = true;
		}
		return array('is_transaction_ok'=>$is_transaction_ok,
						'no_fund'=>$no_fund);
	}

	private function pay_with_game_cash($item_id,$item){

		//if valid, 
		//save the order to database
		//at these time, we assume that user will pay with in-game funds
		$data = $this->request->data;
		$data['merchandise_item_id'] = $this->Session->read('po_item_id');
		$data['game_team_id'] = $this->userData['team']['id'];
		$data['user_id'] = $this->userDetail['User']['id'];
		$data['order_type'] = 1;
		$data['n_status'] = 0;
		$data['order_date'] = date("Y-m-d H:i:s");
		$data['po_number'] = $item_id.'-'.$data['game_team_id'].'-'.date("ymdhis");
	
		//oops, before that, we need to know if user has sufficient funds
		if(intval($this->cash) > 
				intval($item['MerchandiseItem']['price_credit'])){
			$no_fund = false;
		}else{
			$no_fund = true;
		}
		

		$this->loadModel('MerchandiseOrder');
		
		if(!$no_fund){
			//ok the user has enough fund... purchase it now.


			//1. check if the item is digital or non-digital
			// if it's digital, we automatically set the order status into closed.
			// and redeem the perk.

			//this is for safety precaution
			//make sure that the digital is successfully applied before processing the order
			$continue = true; 
			if($item['MerchandiseItem']['merchandise_type']==1){
				$data['n_status']=3; //order status : closed
				$continue = $this->apply_digital_perk($data['game_team_id'],
										$item['MerchandiseItem']['perk_id']);
			}
			if($continue){
				$this->MerchandiseOrder->create();
				$rs = $this->MerchandiseOrder->save($data);		
			}else{
				$rs = false;
			}
			

			if($rs){
				//get next match's id
				$match = $this->nextMatch['match'];
				$game_id = $match['game_id'];
				$matchday = $match['matchday'];
				//time to deduct the money
				$this->Game->query("
				INSERT IGNORE INTO ffgame.game_transactions
				(game_team_id,transaction_name,transaction_dt,amount,
				 details)
				VALUES
				({$data['game_team_id']},'purchase_{$data['po_number']}',
					NOW(),
					-{$item['MerchandiseItem']['price_credit']},
					'{$data['po_number']} - {$item['MerchandiseItem']['name']}');");
				
				//update cash summary
				$this->Game->query("INSERT INTO ffgame.game_team_cash
				(game_team_id,cash)
				SELECT game_team_id,SUM(amount) AS cash 
				FROM ffgame.game_transactions
				WHERE game_team_id = {$data['game_team_id']}
				GROUP BY game_team_id
				ON DUPLICATE KEY UPDATE
				cash = VALUES(cash);");

				//flag transaction as ok
				$is_transaction_ok = true;

			}else{
				$is_transaction_ok = false;
			}
		}else{
			$is_transaction_ok = false;
			$no_fund = true;
		}
		return array('is_transaction_ok'=>$is_transaction_ok,
						'no_fund'=>$no_fund);
	}
	//retrieve customer's first name and last name
	private function getDetailedName(){
		$name_arr = explode(" ",$this->userDetail['User']['name']);
		$first_name = $name_arr[0];
		$last_name = '';
		for($i=1;$i<sizeof($name_arr);$i++){
			$last_name = $name_arr[$i].' ';
		}
		$last_name = trim($last_name);
		return array('first_name'=>$first_name,
					 'last_name'=>$last_name);
	}
	private function apply_digital_perk($game_team_id,$perk_id){
		$this->loadModel('MasterPerk');

		$perk = $this->MasterPerk->findById($perk_id);
		$perk['MasterPerk']['data'] = unserialize($perk['MasterPerk']['data']);
		switch($perk['MasterPerk']['data']['type']){
			case "jersey":
				return $this->apply_jersey_perk($game_team_id,$perk['MasterPerk']);
			break;
			default:
				//for everything else, let the game API handle the task
				$rs = $this->Game->apply_digital_perk($game_team_id,$perk_id);

				if($rs['data']['can_add'] && $rs['data']['success']){
					return true;
				}else if(!$rs['data']['can_add']){
					//tells us that the perk cannot be redeemed because these perk is already redeemed before
					$this->Session->write('apply_digital_perk_error','1');
				}else{
					//tells us that the perk cannot be redeemed because we cannot save the perk.
					$this->Session->write('apply_digital_perk_error','2');
				}
			break;
		}
		
	}
	private function apply_jersey_perk($game_team_id,$perk_data){
		//only 1 jersey can be used
		//so we disabled all existing jersey
		$this->loadModel('DigitalPerk');
		$this->DigitalPerk->bindModel(
			array('belongsTo'=>array(
				'MasterPerk'=>array(
					'type'=>'inner',
					'foreignKey'=>false,
					'conditions'=>array(
						"MasterPerk.id = DigitalPerk.master_perk_id",
						"MasterPerk.perk_name = 'ACCESSORIES'"
					)
				)
			))
		);
		$current_perks = $this->DigitalPerk->find('all',array(
			'conditions'=>array('game_team_id'=>$game_team_id),
			'limit'=>40
		));

		//we only take the jersey perks
		$jerseys = array();
		while(sizeof($current_perks)>0){
			$p = array_pop($current_perks);
			$p['MasterPerk']['data'] = unserialize($p['MasterPerk']['data']);
			if($p['MasterPerk']['data']['type']=='jersey'){
				$jerseys[] = $p['DigitalPerk']['id'];
			}
		}
		//disable the current jerseys
		for($i=0;$i<sizeof($jerseys);$i++){
			$this->DigitalPerk->id = intval($jerseys[$i]);
			$this->DigitalPerk->save(array(
				'n_status'=>0
			));
		}
		//add new jersey
		$this->DigitalPerk->create();
		$rs = $this->DigitalPerk->save(
			array('game_team_id'=>$game_team_id,
				  'master_perk_id'=>$perk_data['id'],
				  'n_status'=>1,
				  'redeem_dt'=>date("Y-m-d H:i:s"),
				  'available'=>99999)
		);
		if(isset($rs['DigitalPerk'])){
			return true;
		}
	}
	public function status($order_id){

	}

}
