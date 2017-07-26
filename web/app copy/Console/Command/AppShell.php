<?php
/**
 * AppShell file
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Shell', 'Console');

/**
 * Application Shell
 *
 * Add your application-wide methods in the class below, your shells
 * will inherit them.
 *
 * @package       app.Console.Command
 */
class AppShell extends Shell {
	var $uses = array('Game');
	var $access_token;
	public function beforeFilter(){
		$this->initAccessToken();
		
		$this->Game->setAccessToken($this->getAccessToken());
	}
	
	public function getAccessToken(){
		return $this->access_token;
	}
	public function initAccessToken(){
		$ckfile = tempnam ("/tmp", "CURLCOOKIE");
		$response = $this->api_post('/auth',array(),
									$ckfile);
		if(!isset($response['error'])){
			$challenge_code = $response['challenge_code'];
			$request_code = sha1($this->getAPIKey().'|'.$challenge_code.'|'.$this->salt());
			$response = $this->api_post('/auth',
										array('request_code'=>$request_code),
										$ckfile);

			unlink($ckfile);
			$this->access_token = $response['access_token'];
			
			return $this->access_token;
			
		}
		return 0;
	}
	public function getAPIUrl(){
		return Configure::read('API_URL');
	}
	public function getAPIKey(){
		return Configure::read('API_KEY');
	}


	public function api_post($uri,$params,$cookie_file='',$timeout=15){
		App::import("Vendor","common");
		if($this->getAccessToken()!=null){
			$params['access_token'] = $this->getAccessToken();
		}
		$params['api_key'] = $this->getAPIKey();
		$response = json_decode(curlPost($this->getAPIUrl().$uri,$params,$cookie_file,$timeout),true);
		return $response;
	}
	public function api_call($uri,$params,$cookie_file='',$timeout=15){
		App::import("Vendor","common");
		if($this->getAccessToken()!=null){
			$params['access_token'] = $this->getAccessToken();
		}
		$params['api_key'] = $this->getAPIKey();
		$response = json_decode(curlGet($this->getAPIUrl().$uri,$params,$cookie_file,$timeout),true);
		return $response;
	}
	public function salt(){
		return Configure::read('API_SALT');
	}
}
