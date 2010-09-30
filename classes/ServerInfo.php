<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Alexandr
 * Date: 30.09.10
 */
 
class ServerInfo {
	protected static $apache_modules = null;
	protected static $php_modules = null;

	protected static function getInfo() {
		if (is_null(self::$apache_modules)) {
			self::$apache_modules = function_exists('apache_get_modules') ? apache_get_modules() : array();
		}
		if (is_null(self::$php_modules)) {
			self::$php_modules = get_loaded_extensions();
		}
	}

	public function existsPhpModule($name) {
		self::getInfo();
		return in_array($name, self::$php_modules);
	}

	public function existsApacheModule($name) {
		self::getInfo();
		return in_array($name, self::$apache_modules);
	}
}