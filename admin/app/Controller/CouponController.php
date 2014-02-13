<?php

App::uses('AppController', 'Controller');
/*
* Manage Digital Coupon
* Pad
*/

class CouponController extends AppController {

/**
 * Controller name
 *
 * @var string
 */
	public $name = 'Coupon';
	private $error_codes = array(); //an array of invalid coupon codes.
	private $success_codes = array();
	//display the available coupons, 20 items each
	public function index(){
		$this->paginate = array('limit'=>20,
								'order'=>array('Coupon.id'=>'desc')
								);
		$this->set('data',$this->Paginate('Coupon'));
	}
	public function view($coupon_id){
		$this->loadModel('CouponCode');
		$coupon = $this->Coupon->findById($coupon_id);
		$coupon_count = $this->CouponCode->find('count',
												array('conditions'=>array('coupon_id'=>$coupon_id)));
		$this->set('coupon',$coupon);
		$this->set('coupon_count',$coupon_count);
	}
	public function create(){
		if($this->request->is('post')){
			if(is_array($_FILES['img'])){
				$filename = 'voucher_'.Inflector::slug($_FILES['img']['name']);
				if(move_uploaded_file($_FILES['img']['tmp_name'],
									Configure::read('avatar_img_dir').$filename)){
					$this->request->data['Coupon']['img'] = $filename;
					$this->request->data['Coupon']['created_dt'] = date("Y-m-d H:i:s");
					$this->Coupon->create();
					$rs = $this->Coupon->save($this->request->data['Coupon']);
					if(isset($rs['Coupon']['id']) && $rs['Coupon']['id'] > 0){
						$this->Session->setFlash("New Coupon/Voucher has been created successfully !");
					}else{
						$this->Session->setFlash("Please upload the Coupon/Voucher image !");
					}
				}else{
					$this->Session->setFlash("Please upload the Coupon/Voucher image !");
				}
			}
		}

	}
	public function download($coupon_id){
		$info = $this->Coupon->findById($coupon_id);

		// The user will receive a PDF to download
		header('Content-type: plain/text');
		// File will be called downloaded.pdf
		header('Content-Disposition: attachment; filename="'.
					Inflector::slug($info['Coupon']['vendor_name']).'-'.
					$info['Coupon']['id'].
					'-'.
					date("Ymdhis").
					'.csv"');


		$this->loadModel('CouponCode');
		$start = 0;

		while(1){
			$coupon = $this->CouponCode->find('all',array(
				'conditions'=>array(
					'coupon_id'=>$coupon_id,
					'n_status'=>0,
					'paid'=>0
				),
				'start'=>$start,
				'limit'=>100
			));
			$start+=100;
			if(sizeof($coupon)==0){
				break;
			}
			for($i=0;$i<sizeof($coupon);$i++){
				print $coupon[$i]['CouponCode']['coupon_code'].PHP_EOL;
			}
		}
		die();
	}
	/*
	* page for generating unique voucher codes
	*/
	public function generate($id){
		$coupon = $this->Coupon->findById($id);
		$this->set('coupon',$coupon);

	}
	public function ajax_generate($id){
		$n_total = intval($this->request->query['total']);
		$n_length = strlen($id);
		$sql = "";
		for($i=0;$i<$n_total;$i++){
			if($sql==""){
				$sql.="INSERT INTO coupon_codes
									(coupon_id,coupon_code,created_dt,redeem_dt,game_team_id,n_status)
									VALUES";
			}
			set_time_limit(30);
			while(1){
				$code = $id.rand(0,9).rand(0,9).rand(0,9).rand(0,9).rand(0,9).rand(0,9).rand(0,9).
					rand(0,9).rand(0,9).rand(0,9).rand(0,9);
				$rs = $this->Coupon->query("SELECT id FROM coupon_codes 
										WHERE coupon_code = '{$code}' LIMIT 1");
				if(sizeof($rs)==0){
					if($i > 0){
						$sql.=",";
					}
					$sql.="({$id},{$code},NOW(),NULL,0,0)";
					break;
				}
			}
		}

		$this->layout = "ajax";
		

		if(strlen($sql)>0){
			$this->Coupon->query($sql,false);
			$this->set('response',array('status'=>1,'sql'=>$sql));
		}else{
			$this->set('response',array('status'=>0));
		}
		
		
		$this->render('response');
	}

	/*
	* user will upload the csv file, and we parse the csv, and update the code usage.
	* CSV Format : 
	* code,purchase_date
	*/
	public function update_data($coupon_id){
		$coupon = $this->Coupon->findById($coupon_id);
		$this->set('coupon',$coupon);
	}
	public function import_csv($coupon_id){
		
		if(move_uploaded_file($_FILES['csv']['tmp_name'], 
							Configure::read('CSV_DIR').Inflector::slug($_FILES['csv']['name']))){
			$this->Session->setFlash("The file is uploaded successfully !");
			$this->import_data($coupon_id,
								Configure::read('CSV_DIR').Inflector::slug($_FILES['csv']['name'])
								);
		}else{
			$this->Session->setFlash("Cannot import the file, please try again later !");
		}
		$coupon = $this->Coupon->findById($coupon_id);
		$this->set('coupon',$coupon);
	}

	private function import_data($coupon_id,$filename){
		$fp = fopen($filename,"r");
		while(!feof($fp)){
			$str = fgets($fp,4096);
			$this->update_from_csv($coupon_id,$str);
		}
		fclose($fp);
		$this->set('success_codes',$this->success_codes);
		$this->set('error_codes',$this->error_codes);

	}
	private function update_from_csv($coupon_id,$str){
		$str = str_replace("\"","",$str);
		$str = str_replace(";",",",$str);
		$arr = explode(",",$str);

		$this->loadModel('CouponCode');

		//1. make sure that the coupon code is valid.
		$code = $this->CouponCode->find('first',array('conditions'=>array(
			'coupon_id'=>$coupon_id,
			'coupon_code'=>$arr[0],
			'paid'=>0
		)));

		if($code['CouponCode']['coupon_code']==$arr[0] && strlen($code['CouponCode']['coupon_code']) > 10){
			$this->CouponCode->id = $code['CouponCode']['id'];
			$rs = $this->CouponCode->save(array(
				'paid'=>1,
				'paid_dt'=>date("Y-m-d H:i:s",strtotime($arr[1]))
			));
			if(!$rs){
				$this->error_codes[] = array('code'=>$arr[0],
											'purchase_date'=>$arr[1],
											'reason'=>'unable to update the code status');	
			}else{
				$this->success_codes[] = array('code'=>$arr[0],
											'purchase_date'=>$arr[1]);	
				return true;
			}
		}else{
			$this->error_codes[] = array('code'=>$arr[0],
										'purchase_date'=>$arr[1],
										'reason'=>'the code is not found or already been paid');
		}

	}

}