<?php

ob_start();
session_start();

include dirname(__FILE__) . '/configure.php';

$module = @$_GET['module'] or $module = 'files';
$action = @$_GET['action'] or $action = 'show_all';

if (class_exists($module)) {
	$handler = new $module();
	$handler->processRequest($action);
} else {
	Template::showPage("404", array('url' => (empty($_SERVER["HTTPS"]) ? 'http' : 'https') . '://' . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]));
}
