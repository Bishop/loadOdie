<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Alexandr
 * Date: 01.10.10
 */

class Users extends RequestHandler {
	protected $default_action = 'login';

	/**
	 * @request_handler
	 * @return array
	 */
	public function login($params) {
		$result = array('data' => $_SESSION['upload_data']);
		unset($_SESSION['upload_data']);
		return $result;
	}

	/**
	 * @request_handler
	 * @return array
	 */
	public function register($params) {
		$result = array('data' => $_SESSION['upload_data']);
		unset($_SESSION['upload_data']);
		return $result;
	}
}
