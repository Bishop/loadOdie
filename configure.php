<?php

/**
 * Created by JetBrains PhpStorm.
 * User: Alexandr
 * Date: 28.09.10
 * Time: 21:54
 */

define('ROOT_DIR', dirname(__FILE__) . DIRECTORY_SEPARATOR);
define('CONNECTION_STRING_FORMAT', '!^mysql://([^:@]+)(?::([^@]+))?@([^:/]+)(?::(\d+))?/(.*)$!');

if (!defined('STDIN')) {
	define('STDIN', fopen('php://stdin', 'r'));
}

if (!is_callable('gettext')) {
	function _($msgid) { return $msgid; }
	function gettext($msgid) { return $msgid; }
	function ngettext($msgid1, $msgid2, $n) { return $n == 1 ? $msgid1 : $msgid2; }
}

define('CONFIG_FILE', 'app.cfg');
define('SOFTWARE_NAME', 'loadOdie');

class Autoloader {
	static public function register() {
		spl_autoload_register(array(new self, 'autoload'));
	}

	static public function autoload($class) {
		$file = ROOT_DIR . "classes/$class.php";
		if (file_exists($file)) {
			require_once $file;
			return true;
		} else
			return false;
	}
}

Autoloader::register();

class Config {
	protected static $config;

	public static function setConfig($config) {
		self::$config = $config;
	}

	public static function getConfig($param) {
		return array_key_exists($param, self::$config) ? self::$config[$param] : '';
	}

	public static function getAll() {
		return self::$config;
	}
}

Config::setConfig(parse_ini_file(ROOT_DIR . CONFIG_FILE));
