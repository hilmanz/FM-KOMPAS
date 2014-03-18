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
		$this->loadModel('MerchandiseItem');
		$this->loadModel('MerchandiseCategory');
		$this->loadModel('MerchandiseOrder');
		$this->loadModel('Ongkir');

		$userData = $this->getUserData();
		$user = $this->userDetail;
		$this->set('user',$user['User']);
		if(!$this->hasTeam()){
			$this->redirect('/login/expired');
		}

		//banners
		$sidebar_banner = $this->getBanners('CATALOG_SIDEBAR',3,true);
		$this->set('sidebar_banner',$sidebar_banner);

		$this->loadModel('Ongkir');
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
	public function history(){
		
		$fb_id = $this->userDetail['User']['fb_id'];
		$this->paginate = array('conditions'=>array('fb_id'=>$fb_id),
								  'order'=>array('MerchandiseOrder.id'=>'DESC'),
								 'limit'=>20);
		
		$fb_id = $this->userDetail['User']['fb_id'];

		$rs = $this->Paginate('MerchandiseOrder');
		
		$this->set('rs',$rs);
	}

	public function view_order($order_id){

		//attach order detail
		$rs = $this->MerchandiseOrder->findById($order_id);
		$this->set('rs',$rs);

		//attach chosen delivery city
		$this->set('city_id',$rs['MerchandiseOrder']['ongkir_id']);

		//attach ongkos kirim list.
		$this->getOngkirList();


	}
	public function pay($type,$order_id=0){

		if($type=='ongkir'){
			$this->payOngkirPage($order_id);
		}else{
			//ecash return page
			$this->handlePayment();
		}
		
	}
	private function handlePayment(){
		$body = file_get_contents('php://input');

		if(!empty($body)) {
		  $data = explode(",", $body);
		  $ticket = $data[0];
		  $phone_no = $data[1];
		  $trace_no = $data[2];
		  $order_id = trim($data[3]);
		  $status = trim($data[4]);
		  
		  $returnid = $ticket;
		  $this->Session->write('ecash_return',array(
		  							'id'=>$ticket,
		  							'nohp'=>$phone_no,
		  							'transaction_id'=>$order_id,
		  							'trace_number'=>$trace_no,
		  							'status'=>$status
		  						));

		  die();
		}else{
			$rs  = $this->Game->EcashValidate($this->request->query['id']);
			list($id,$trace_number,$nohp,$transaction_id,$status) = explode(',',$rs['data']);

			$result = array(
				'id'=>trim($id),
				'trace_number'=>trim($trace_number),
				'nohp'=>trim($nohp),
				'transaction_id'=>trim($transaction_id),
				'status'=>trim($status)
			);
			
			$is_valid = true;
			if(Configure::read('debug')==0){
				$ecash_validate = $this->Session->read('ecash_return');
				if($result['id']==$ecash_validate['id'] &&
					$result['trace_number']==$ecash_validate['trace_number'] &&
					$result['nohp']==$ecash_validate['nohp'] &&
					$result['transaction_id']==$ecash_validate['transaction_id'] &&
					$result['status']==$ecash_validate['status']
					){
					$is_valid = true;
				}else{
					$is_valid = false;
				}
			}

			if(strtoupper($result['status'])=='SUCCESS' && $is_valid){
				//update order status
				$data['n_status'] = 1;
				$this->MerchandiseOrder->id = intval($this->Session->read($result['transaction_id']));
				$rs = $this->MerchandiseOrder->save($data);
				if(isset($rs)){
					$this->Session->setFlash('Pembayaran Telah Berhasil ! Terima Kasih !');
				}else{
					$this->Session->setFlash('Transaksi Ecash Berhasil, namun kami gagal menyimpan transaksi anda, silahkan hubungi soccerdesk@supersoccer.co.id untuk bantuan. Mohon maaf atas ketidaknyamanannya.');
				}
				$this->redirect('/merchandises/history');

			}else{
				$this->Session->setFlash("Pembayaran anda gagal diproses, silahkan coba kembali !");
				$this->redirect('/merchandises/history');
				

			}
		}

	}
	private function payOngkirPage($order_id){
		$ongkir = $this->Ongkir->find('all',array('limit'=>10000));
		$rs = $this->MerchandiseOrder->findById($order_id);
		
		$this->set('rs',$rs);
		foreach($ongkir as $ok){
			if($ok['Ongkir']['id'] == $rs['MerchandiseOrder']['ongkir_id']){
				$city = $ok['Ongkir'];
			}
		}
		$this->set('city',$city);

		//add suffix -1 to define that its the payment for shipping for these po number.
		$transaction_id =  $rs['MerchandiseOrder']['po_number'].'-1';
		//ecash url
		$rs = $this->Game->getEcashUrl(array(
			'transaction_id'=>$transaction_id,
			'amount'=>$city['cost'],
			'clientIpAddress'=>$this->request->clientIp(),
			'description'=>'Shipping Fee #'.$transaction_id,
			'source'=>'FMPAY'
		));
		$this->set('transaction_id',$transaction_id);
		$this->set('ecash_url',$rs['data']);

		$this->Session->write($transaction_id,$order_id);
	}
	public function view($item_id){
		


		//we need to populate the category
		$this->populate_main_categories();
		
		
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

		
		if($item['MerchandiseItem']['merchandise_type']==1){
			
			$this->set('can_update_formation',$this->can_update_formation());
		}else{
			$this->set('can_update_formation',true);
		}

	}
	private function get_ongkir(){
		
		$rs = $this->Ongkir->find('all');
		$ongkir = array();
		while(sizeof($rs)>0){
			$p = array_shift($rs);
			$ongkir[] = $p['Ongkir'];
		}	
		return $ongkir;
	}
	private function getPreviousOrders(){
	
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
	*	get the list of child category_ids, 1 level under only.
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
	*	get the list of child categories, 1 level under only.
	*/
	private function getSubCategories($category_id){
		$categories = $this->MerchandiseCategory->find('all',
														array('conditions'=>array('parent_id'=>$category_id),
															  'limit'=>100)
													);
		return $categories;
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
		for($i=0;$i<sizeof($categories);$i++){
			$categories[$i]['Child'] = $this->getSubCategories($categories[$i]['MerchandiseCategory']['id']);
		}
		
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
	//these page will display an order form.
	//user should fill all the fields in order to process the order.
	//here, the user can choose the payment method,
	//at the moment only 2 options available
	//1. coins
	//2. Rupiah via Mandiri Ecash
	//when the user choose coins, we automatically deduct the coins, and
	//then save the purchase order.
	//the tricky part is, the digital item (perks) must be applied directly
	//while the non-digitals doesnt.
	//these conditions should apply : 
	//if all items are digital items, we close the purchase automatically.
	//if all items are non-digital items, or at least has 1 non-digital items,
	//we keep the purchase order open (or pending) so that administrator can check the order
	//and proceed the item delivery manually.
	//=====================================================================================
	//workflows 
	//1. check if all stocks are available 
	//2. if there's stock that isnt available, we redirect back the user to cart and notify the user
	//(already covered in checkout()  )
	//3. if all items area available, display the form.
	//4. upon submitting the form, check the payment method
	//5. create the transaction_id
	//6. if paid with coins, deduct the coins and then save the order
	//7. if paid with ecash, proceed the ecash payment workflow by invoking the ecash webservice
	//8. when the payment completed, we display the success page. 
	//9. send notification email to user.
	//10. send notification email to administrator.
	//11. distribute the digital items

	public function buy(){
	


		//display the cart content
		$shopping_cart = $this->Session->read('shopping_cart');
		for($i=0;$i<sizeof($shopping_cart);$i++){
			$shopping_cart[$i]['data'] = $this->MerchandiseItem->findById($shopping_cart[$i]['item_id']);
		}
		$this->set('shopping_cart',$shopping_cart);

		

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
		
		//attach chosen delivery city
		$this->set('city_id',$this->Session->read('city_id'));

		//attaching ongkir
		$ongkir = $this->Ongkir->findById($this->Session->read('city_id'));

		$this->set('city',$ongkir['Ongkir']);

		//attach ongkos kirim list.
		$this->getOngkirList();

	}

	/*
	* add item to shopping cart
	* if the selected item is a digital item, we check if the user is permitted to buy that perk.
	* for example, you cannot buy point booster while the point booster in the same category is in active.
	* if the cart is already exists in shopping cart, no need to re-add it.
	*/
	public function select($item_id){
		
		$shopping_cart = $this->Session->read('shopping_cart');
		$can_add = false;
		$canAddPerk = true;

		//parno mode.
		$item_id = Sanitize::clean($item_id);

		//get the item detail
		$item = $this->MerchandiseItem->findById($item_id);

		//if its digital item, we need to make sure that these 
		if($item['MerchandiseItem']['merchandise_type'] == 1){
			$canAddPerk = $this->Game->can_apply_perk($this->userData['team']['id'],
										$item['MerchandiseItem']['perk_id']);
			
		}


		if($shopping_cart == null){
			$shopping_cart = array();
			$can_add = true;

		}else{
			$can_add = true;
			//make sure that the item is not in the cart yet
			for($i=0;$i<sizeof($shopping_cart);$i++){
				if($shopping_cart[$i]['item_id'] == $item_id){
					$can_add = false;
					break;
				}
			}
		}


		if($can_add && $canAddPerk){
			$shopping_cart[] = array(
				'item_id'=>$item_id,
				'qty'=>1
			);	
		}
		
		$this->Session->write('shopping_cart',$shopping_cart);
		$this->set('canAddPerk',$canAddPerk);
		$this->set('item',$item['MerchandiseItem']);
		$this->Session->write('out_of_stock',null);
		

		
	}
	//create purchase order and make payment.
	//1. if payment method is ecash, we only generate the ecash payment url for user.
	public function order(){
		
		
		//these is our flags
		$is_transaction_ok = true;
		$no_fund = false;
		

		//recheck the stock of all items.
		$stock_status = $this->recheckStockBeforePayment();
		//if all items are available, we can continute the purchase.
		if($stock_status){
			//make sure the csrf token still valid

			//-> csrf check di disable dulu selama development
			//if(
			//	(strlen($this->request->data['ct']) > 0)
			//		&& ($this->Session->read('po_csrf') == $this->request->data['ct'])
			//  ){

			if($this->request->data['payment_method']=='coins'){

				$this->process_with_coins();
			}else{
				$this->pay_with_ecash();
				$this->render('ecash');
			}
		}else{
			//we will already be redirected back to shopping cart.
		}
		
	}
	private function process_with_coins(){
		$result = $this->pay_with_coins();
		$is_transaction_ok = $result['is_transaction_ok'];
		$no_fund = $result['no_fund'];
		$order_id = $result['order_id'];
		
		if($is_transaction_ok == true){
			//check accross the items, we apply the perk for all digital items
			$this->process_items($result['items']);
		}

	
	
		$this->set('apply_digital_perk_error',$this->Session->read('apply_digital_perk_error'));
		$this->set('is_transaction_ok',$is_transaction_ok);
		$this->set('no_fund',$no_fund);
		$this->Session->write('apply_digital_perk_error',null);
		//reset the csrf token
		$this->Session->write('po_csrf',null);
		//-->
		//attach chosen delivery city
		$this->set('city_id',$this->Session->read('city_id'));

		//attach ongkos kirim list.
		$this->getOngkirList();
		//reset the shopping_cart in session (disable these for debug only)
		$this->Session->write('shopping_cart',null);

	}
	private function pay_with_ecash(){

		
		
		//attach chosen delivery city
		$this->set('city_id',$this->Session->read('city_id'));

		//attach ongkos kirim list.
		$this->getOngkirList();



		$result = array('is_transaction_ok'=>false,
						'no_fund'=>false);

		//display the cart content
		$shopping_cart = $this->Session->read('shopping_cart');

		

		//get total coins to be spent.
		$total_price = 0;
		$all_digital = true;
		
		for($i=0;$i<sizeof($shopping_cart);$i++){

			$shopping_cart[$i]['data'] = $this->MerchandiseItem->findById($shopping_cart[$i]['item_id']);
			$item = $shopping_cart[$i]['data']['MerchandiseItem'];

			$total_price += (intval($shopping_cart[$i]['qty']) * intval($item['price_money']));
			//is there any non-digital item ?
			if($item['merchandise_type']==0){
				$all_digital = false;
			}
		}
		$admin_fee = 50000;
		
		if($all_digital){
			$admin_fee = 0;
		}


		$total_price += $admin_fee;
		
		$total_ongkir = 0;
		foreach($this->ongkirList as $ongkir){
			if($ongkir['Ongkir']['id'] == intval($this->Session->read('city_id'))){
				$total_ongkir = intval($ongkir['Ongkir']['cost']);
				break;
			}
		}

		//tambahkan harga ongkir kedalam total price
		$total_price+=$total_ongkir;


		$this->set('shopping_cart',$shopping_cart);
		//add shipping and handling cost
		$this->set('admin_fee',$admin_fee);
		

		//1. create transaction ID
		$data = $this->request->data;

		$transaction_id = $this->userData['team']['id'].'-'.date("ymdhis");
		$this->Session->write($transaction_id,
								serialize(array('data'=>$data,'shopping_cart'=>$shopping_cart))
							 );

		
		

		$rs = $this->Game->getEcashUrl(array(
			'transaction_id'=>$transaction_id,
			'amount'=>$total_price,
			'clientIpAddress'=>$this->request->clientIp(),
			'description'=>'Purchase Order #'.$transaction_id,
			'source'=>'FM'
		));
		$this->set('transaction_id',$transaction_id);
		$this->set('ecash_url',$rs['data']);
	}
	public function payment(){
		

		$body = file_get_contents('php://input');

		if(!empty($body)) {
		  $data = explode(",", $body);
		  $ticket = $data[0];
		  $phone_no = $data[1];
		  $trace_no = $data[2];
		  $order_id = trim($data[3]);
		  $status = trim($data[4]);
		  
		  $returnid = $ticket;
		  $this->Session->write('ecash_return',array(
		  							'id'=>$ticket,
		  							'nohp'=>$phone_no,
		  							'transaction_id'=>$order_id,
		  							'trace_number'=>$trace_no,
		  							'status'=>$status
		  						));

		  die();
		}else{
			$rs  = $this->Game->EcashValidate($this->request->query['id']);
			list($id,$trace_number,$nohp,$transaction_id,$status) = explode(',',$rs['data']);

			$result = array(
				'id'=>trim($id),
				'trace_number'=>trim($trace_number),
				'nohp'=>trim($nohp),
				'transaction_id'=>trim($transaction_id),
				'status'=>trim($status)
			);
			
			$is_valid = true;
			if(Configure::read('debug')==0){
				$ecash_validate = $this->Session->read('ecash_return');
				if($result['id']==$ecash_validate['id'] &&
					$result['trace_number']==$ecash_validate['trace_number'] &&
					$result['nohp']==$ecash_validate['nohp'] &&
					$result['transaction_id']==$ecash_validate['transaction_id'] &&
					$result['status']==$ecash_validate['status']
					){
					$is_valid = true;
				}else{
					$is_valid = false;
				}
			}

			if(strtoupper($result['status'])=='SUCCESS' && $is_valid){
				$this->pay_with_ecash_completed($result);

			}else{
				$this->Session->setFlash("Pembayaran anda gagal diproses, silahkan coba kembali !");
				$this->redirect('/merchandises/buy');
				

			}
		}

		
	}
	private function pay_with_ecash_completed($ecash_data){
		
		
		//attach ongkos kirim list.
		$this->getOngkirList();


		//get transaction data from session
		$sess = unserialize($this->Session->read($ecash_data['transaction_id']));
		if($ecash_data['status']=="SUCCESS"){
			$is_transaction_ok = true;
		}else{
			$is_transaction_ok = false;
		}

		$data = $sess['data'];
		$shopping_cart = $sess['shopping_cart'];
		
		if(sizeof($shopping_cart) > 0){
			$total_price = 0;
		
			$all_digital = true;

			for($i=0;$i<sizeof($shopping_cart);$i++){

				$item = $shopping_cart[$i]['data']['MerchandiseItem'];
				$total_price += (intval($shopping_cart[$i]['qty']) * intval($item['price_money']));
				//is there any non-digital item ?
				if($item['merchandise_type']==0){
					$all_digital = false;
				}
			}
			
			$admin_fee = 50000;
			if($all_digital){
				$admin_fee = 0;
			}
			$total_price += $admin_fee;

			$total_ongkir = 0;
			foreach($this->ongkirList as $ongkir){
				if($ongkir['Ongkir']['id'] == intval($this->Session->read('city_id'))){
					$total_ongkir = intval($ongkir['Ongkir']['cost']);
					break;
				}
			}
			
			//tambahkan harga ongkir kedalam total price
			$total_price+=$total_ongkir;


			$data['fb_id'] = $this->userDetail['User']['fb_id'];
			$data['merchandise_item_id'] = 0;
			$data['game_team_id'] = $this->userData['team']['id'];
			$data['user_id'] = $this->userDetail['User']['id'];
			$data['order_type'] = 1;
			$data['ongkir_id'] = intval($this->Session->read('city_id')); //the related ongkir_id

			//we need ongkir value
			$ok = $this->Ongkir->findById($this->Session->read('city_id'));
			$data['ongkir_value'] = $ok['Ongkir']['cost'];
			
			if($all_digital){
				$data['n_status'] = 3;	
			}else{
				$data['n_status'] = 1;
			}

			$data['order_date'] = date("Y-m-d H:i:s");
			$data['data'] = serialize($shopping_cart);
			$data['po_number'] = $ecash_data['transaction_id'];
			$data['total_sale'] = intval($total_price);
			$data['payment_method'] = 'ecash';
			$data['trace_code'] = $ecash_data['trace_number'];

			$this->MerchandiseOrder->create();
			$rs = $this->MerchandiseOrder->save($data);	
			

			$this->process_items($shopping_cart);
				
		
			$this->set('apply_digital_perk_error',$this->Session->read('apply_digital_perk_error'));

			$this->Session->write('apply_digital_perk_error',null);
			//reset the csrf token
			$this->Session->write('po_csrf',null);
			//-->

			//reset the shopping_cart in session (disable these for debug only)
			$this->Session->write('shopping_cart',null);
			$this->Session->write($ecash_data['transaction_id'],null);

		}else{
			$is_transaction_ok = false;
		}
		$this->set('is_transaction_ok',$is_transaction_ok);
		
		
	}
	private function pay_with_coins(){
		
		

		$result = array('is_transaction_ok'=>false);

		//display the cart content
		$shopping_cart = $this->Session->read('shopping_cart');
		if(sizeof($shopping_cart) > 0){
			//get total coins to be spent.
			$total_coins = 0;
			$all_digital = true;
			for($i=0;$i<sizeof($shopping_cart);$i++){

				$shopping_cart[$i]['data'] = $this->MerchandiseItem->findById($shopping_cart[$i]['item_id']);
				$item = $shopping_cart[$i]['data']['MerchandiseItem'];
				$total_coins += (intval($shopping_cart[$i]['qty']) * intval($item['price_credit']));
				//is there any non-digital item ?
				if($item['merchandise_type']==0){
					$all_digital = false;
				}
			}
			
			//1. check if the coins are sufficient
			if(intval($this->cash) >= $total_coins){
				$no_fund = false;
			}else{
				$no_fund = true;
			}
			
			//2. if fund is available, we create transaction id and order detail.
			if(!$no_fund){

				$data = $this->request->data;
				$data['merchandise_item_id'] = 0;
				$data['game_team_id'] = $this->userData['team']['id'];
				$data['user_id'] = $this->userDetail['User']['id'];
				$data['order_type'] = 1;
				$data['fb_id'] = $this->userDetail['User']['fb_id'];
				$data['ongkir_id'] = intval($this->Session->read('city_id'));
				//we need ongkir value
				$ok = $this->Ongkir->findById($this->Session->read('city_id'));
				$data['ongkir_value'] = $ok['Ongkir']['cost'];
			
				if($all_digital){
					$data['n_status'] = 3;	
				}else{
					$data['n_status'] = 0;
				}
				$data['order_date'] = date("Y-m-d H:i:s");
				$data['data'] = serialize($shopping_cart);
				$data['po_number'] = $data['game_team_id'].'-'.date("ymdhis");
				$data['total_sale'] = intval($total_coins);
				$data['payment_method'] = 'coins';

				$this->MerchandiseOrder->create();
				$rs = $this->MerchandiseOrder->save($data);	
				if($rs){
					$result['order_id'] = $this->MerchandiseOrder->id;
					//time to deduct the money
					$this->Game->query("
					INSERT IGNORE INTO ffgame.game_transactions
					(game_team_id,transaction_name,transaction_dt,amount,
					 details)
					VALUES
					({$data['game_team_id']},'purchase_{$data['po_number']}',
						NOW(),
						-{$total_coins},
						'{$data['po_number']} - {$result['order_id']}');");
					
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
					$result['is_transaction_ok'] = $is_transaction_ok;
					$result['items'] = $shopping_cart;
				}
			}

			$result['no_fund'] = $no_fund;
		}else{
			$result['no_fund'] = false;
			$result['order_id'] = 0;
		}
		
		return $result;
	}

	/*
	* process digital items
	* when the digital items redeemed, we reduce its stock.
	*/
	private function process_items($items){	
		for($i=0; $i<sizeof($items); $i++){
			$item = $items[$i]['data']['MerchandiseItem'];
			if($item['merchandise_type']==1){
				$this->apply_digital_perk($this->userData['team']['id'],
											$item['perk_id']);
			}
			$this->reduceStock($item['id']);
		}
	}
	/*
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
	*/
	public function cart(){
		$shopping_cart = array();
		if($this->request->is('post')){

			$item_id = $this->request->data['item_id'];
			$qty = $this->request->data['qty'];
			for($i=0;$i<sizeof($item_id);$i++){
				$shopping_cart[] = array('item_id'=>intval($item_id[$i]),
										  'qty'=>intval($qty[$i]));
			}
			
			$this->Session->write('shopping_cart',$shopping_cart);	
			$this->Session->write('city_id',intval($this->request->data['city_id']));	
			
			
		}
		
		$shopping_cart = $this->Session->read('shopping_cart');
			
		
		$this->request->data['update_type'] = intval(@$this->request->data['update_type']);


		if($this->request->data['update_type']==1){

			$this->checkout($shopping_cart);

		}else{

			$this->displayShoppingCartContent($shopping_cart);
		}
		
	}
	private function displayShoppingCartContent($shopping_cart){
		for($i=0;$i<sizeof($shopping_cart);$i++){
			$shopping_cart[$i]['data'] = $this->MerchandiseItem->findById($shopping_cart[$i]['item_id']);
			$out_of_stock = $this->Session->read('out_of_stock');

			for($j=0;$j<sizeof($out_of_stock);$j++){
				$shopping_cart[$i]['out_of_stock'] = false;
				if($out_of_stock[$j]['MerchandiseItem']['id'] == $shopping_cart[$i]['item_id']){
					$shopping_cart[$i]['qty'] = $out_of_stock[$j]['MerchandiseItem']['stock'];
					$shopping_cart[$i]['out_of_stock'] = true;
					break;
				}
			}
		}
		$this->set('shopping_cart',$shopping_cart);
		$this->set('city_id',intval($this->Session->read('city_id')));
		$this->getOngkirList();
	}
	private function getOngkirList(){
		
		$ongkir = $this->Ongkir->find('all',array('limit'=>10000));
		$this->ongkirList = $ongkir;
		$this->set('ongkir',$ongkir);
	}
	/*
	* checkout procedure
	* 1. make sure that all items stock are available
	* 2. if stock available, redirect to order
	*/
	private function checkout($shopping_cart){
		$stocks = $this->getItemsStock();
		$stock_available = true;
		$out_of_stock = array();

		//capture the city_id (of ongkir)
		$this->Session->write('city_id',intval($this->request->data['city_id']));
		
		if(sizeof($stocks)>0){
			foreach($stocks as $item_id=>$stock){
				if($stock==0){
					$stock_available = false;
					$out_of_stock[] = $this->MerchandiseItem->findById($item_id);
				}
			}
		}
		if($stock_available){
			$this->redirect('/merchandises/buy');
		}else{
			$str = "Mohon maaf, stok barang - barang berikut tidak mencukupi :<br/>";
			for($i=0;$i<sizeof($out_of_stock);$i++){
				$str .= $out_of_stock[$i]['MerchandiseItem']['name']." - Sisa : ".
						$out_of_stock[$i]['MerchandiseItem']['stock']."<br/>";
			}
			$this->Session->write('out_of_stock',$out_of_stock);
			$this->Session->setFlash($str);
			

			$this->displayShoppingCartContent($shopping_cart);
		}
	}
	/*
	* recheck the items stock just before we close the order.
	*/
	private function recheckStockBeforePayment(){
		$stocks = $this->getItemsStock();
		$stock_available = true;
		$out_of_stock = array();

		
		
		if(sizeof($stocks)>0){
			foreach($stocks as $item_id=>$stock){
				if($stock==0){
					$stock_available = false;
					$out_of_stock[] = $this->MerchandiseItem->findById($item_id);
				}
			}
		}
		if($stock_available){
			return true;
		}else{
			$str = "Mohon maaf, stok barang - barang berikut tidak mencukupi :<br/>";
			for($i=0;$i<sizeof($out_of_stock);$i++){
				$str .= $out_of_stock[$i]['MerchandiseItem']['name']." - Sisa : ".
						$out_of_stock[$i]['MerchandiseItem']['stock']."<br/>";
			}
			$this->Session->write('out_of_stock',$out_of_stock);
			$this->Session->setFlash($str);
			$this->redirect('/merchandises/cart');
		}
	}
	/*
	* check items stocks
	*/
	private function getItemsStock(){
		
		$items = $this->Session->read('shopping_cart');

		$rs = array();
		for($i=0;$i<sizeof($items);$i++){
			$item = $this->MerchandiseItem->findById(intval($items[$i]['item_id']));
		
			if(($item['MerchandiseItem']['stock'] - $items[$i]['qty']) >= 0){
				$rs[intval($item['MerchandiseItem']['id'])] = intval($item['MerchandiseItem']['stock']);
			}else{
				$rs[intval($item['MerchandiseItem']['id'])] = 0;
			}
		}
		return $rs;
	}
	private function ReduceStock($item_id){
		$item_id = intval($item_id);
		$sql = "UPDATE merchandise_items SET stock = stock - 1 WHERE id = {$item_id}";
		$this->MerchandiseItem->query($sql);
		
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
			//make sure that the match isnt in progress.
			//people cant buy the perk while the match is in progress.
			if($this->can_update_formation()){
				if($item['MerchandiseItem']['merchandise_type']==1){
					$data['n_status']=3; //order status : closed
					$continue = $this->apply_digital_perk($data['game_team_id'],
											$item['MerchandiseItem']['perk_id']);
				}
			}else{
				$continue = false;
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
		
		$this->DigitalPerk->cache = false;


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
		$has_bought = false;
		$bought_id = 0;
		//we only take the jersey perks
		$jerseys = array();
		while(sizeof($current_perks)>0){
			$p = array_pop($current_perks);
			$p['MasterPerk']['data'] = unserialize($p['MasterPerk']['data']);
			if($p['MasterPerk']['data']['type']=='jersey'){
				$jerseys[] = $p['DigitalPerk']['id'];
			}
			if($p['DigitalPerk']['master_perk_id'] == $perk_data['id']){
				$has_bought = true;
				$bought_id = $p['DigitalPerk']['id'];
			}
		}
		//check if these jersy has been bought before.
		
		//disable the current jerseys
		for($i=0;$i<sizeof($jerseys);$i++){

			$this->DigitalPerk->id = intval($jerseys[$i]);
			$this->DigitalPerk->save(array(
				'n_status'=>0
			));
		}


		//add new jersey
		if(!$has_bought){
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
		}else{
			//update the status only
			$this->DigitalPerk->id = intval($bought_id);
			$rs = $this->DigitalPerk->save(array(
				'n_status'=>1
			));
			if($rs){
				return true;
			}
		}
		
	}
	public function status($order_id){

	}

}
