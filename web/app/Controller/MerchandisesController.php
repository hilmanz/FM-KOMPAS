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
									'limit'=>3
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
									'limit'=>3
									);
		}

		//retrieve the paginated results.
		$rs = $this->paginate('MerchandiseItem');

		//assign it.
		$this->set('rs',$rs);


		


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
		
		//attach the item_id
		$this->Session->write('po_item_id',$item_id);
		//dont forget to clear po_item_id session when the order is done.
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
		

		$is_transaction_ok = true;
		//make sure the csrf token still valid

		if(
			(strlen($this->request->data['ct']) > 0)
				&& ($this->Session->read('po_csrf') == $this->request->data['ct'])
		  ){
		
			//if valid, save the order to database
			//at these time, we assume that user will pay with in-game funds
			$data = $this->request->data;
			$data['merchandise_item_id'] = $this->Session->read('po_item_id');
			$data['game_team_id'] = $this->userDetail['Team']['id'];
			$data['user_id'] = $this->userDetail['User']['id'];
			$data['order_type'] = 0;
			$data['n_status'] = 0;
			$data['order_date'] = date("Y-m-d H:i:s");
			$data['po_number'] = $item_id.'-'.$data['game_team_id'].'-'.date("ymdhis");
			$this->loadModel('MerchandiseOrder');
			$this->MerchandiseOrder->create();
			$rs = $this->MerchandiseOrder->save($data);
			if($rs){
				$is_transaction_ok = true;
			}else{
				$is_transaction_ok = false;
			}
		}else{
			$is_transaction_ok = false;
		}

		$this->set('is_transaction_ok',$is_transaction_ok);

		//reset the csrf token
		$this->Session->write('po_csrf',null);
		//-->

		//reset the item_id in session
		$this->Session->write('po_item_id',0);
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

}