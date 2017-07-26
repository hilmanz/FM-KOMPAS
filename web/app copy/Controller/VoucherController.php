<?php
/**
 * Market Controller

 */
App::uses('AppController', 'Controller');
App::uses('Sanitize', 'Utility');

class VoucherController extends AppController {

/**
 * Controller name
 *
 * @var string
 */
	public $name = 'Vouchers';

/**
 * This controller does not use a model
 *
 * @var array
 */
	public $uses = array();
	
	public function beforeFilter(){
		parent::beforeFilter();
		
	}
	public function get($no){
		
	}

}
