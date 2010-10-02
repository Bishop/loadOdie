<?php

/**
 * Created by JetBrains PhpStorm.
 * User: Alexandr
 * Date: 29.09.10
 */
 
class DB {
	/**
	 * @var PDO
	 */
	protected static $db = null;

	private function __construct() {}

	public static function connect($connection_string) {
		$message = '';
		if (preg_match(CONNECTION_STRING_FORMAT, $connection_string, $matches)) {
			list(, $user, $pass, $host, $port, $db) = $matches;
			empty($port) and $port = "3306";
			try {
				self::$db = new PDO("mysql:dbname=$db;host=$host;port=$port", $user, $pass,
					array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
				self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			} catch (PDOException $e) {
				$message = _('Connection failed: ') . $e->getMessage();
			}
		} else {
			$message = _('Invalid database connection string');
		}

		if ($message) {
			if (PHP_SAPI == 'cli') {
				throw new Exception($message);
			} else {
				Template::showErrorPage($message);
			}
		}
	}

	/**
	 * @static
	 * @return PDO
	 */
	public static function getInstance() {
		return self::$db;
	}
}

DB::connect(Config::getConfig('database'));