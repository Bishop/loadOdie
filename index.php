<?php

ob_start();
session_start();

include dirname(__FILE__) . DIRECTORY_SEPARATOR . 'configure.php';

define('FULL_REQUEST', (empty($_SERVER["HTTPS"]) ? 'http' : 'https') . '://' . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);

$module = 'files';
$action = '';
$params = array();

if (preg_match('!/(\w+)/?(\w+)?/?(.*)/?!', $_SERVER["PATH_INFO"], $chunks)) {
	list(, $module, $action, $params_str) = $chunks;

	if ($params_str) {
		$keys = array();
		$values = array();
		foreach (explode('/', $params_str) as $i => $str) {
			if ($i & 1) {
				$values[] = $str;
			} else {
				$keys[] = $str;
			}
		}
		if (count($keys) == count($values)) {
			$params = array_combine($keys, $values);
		}
	}
}

if (class_exists($module)) {
	$handler = new $module();
	$handler->processRequest($action, $params);
} else {
	Template::show404Page();
}
