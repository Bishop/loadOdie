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

	public static function info($p) {
		return array_key_exists($p, $_SESSION['user']) ? $_SESSION['user'][$p] : '';
	}
}
