<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Alexandr
 * Date: 01.10.10
 */
 
class User {
	public static function isLogged() {
		return !empty($_SESSION['user']['id']);
	}

	public static function info($field) {
		return array_key_exists($field, $_SESSION['user']) ? $_SESSION['user'][$field] : '';
	}

	public static function getFormData() {
		return isset($_SESSION['post_data']) ? $_SESSION['post_data'] : array();
	}

	public static function storeFormData($data) {
		$_SESSION['post_data'] = $data;
	}
}
