<?php

include 'configure.php';

PHP_SAPI == 'cli' or die(_('Using only in console'));

echo _("Welcome to loadOdie setup wizard\n\n");

function prompt($prompt, $default = '') {
	echo "{$prompt} [{$default}]:\n> ";
	$answer = trim(fgets(STDIN)) or $answer = $default;
	return $answer;
}

function confirm($prompt, $ok = 'y') {
	return prompt($prompt, $ok) == $ok;
}

function verify_connection_sting($connection_string) {
	return true;
}

function verify_directory($directory) {
	if (file_exists($directory)) {
		return confirm(sprintf("Directory %s exists. Use it?", $directory));
	}
	mkdir($directory);
	return true;
}

if (file_exists(CONFIG_FILE) && !confirm(sprintf("Config file '%s' exists. Continue and overwrite it?", CONFIG_FILE))) {
	exit();
}

$questions = array(
	array('name' => 'database', 'text' => _('Database connection string'), 'default' => 'mysql://root@localhost:3306/files', 'callback' => 'verify_connection_sting'),
	array('name' => 'repository', 'text' => _('Files location'), 'default' => dirname(__FILE__) . DIRECTORY_SEPARATOR . 'files', 'callback' => 'verify_directory'),
);

$config = array();

foreach ($questions as $question) {
	do {
		$answer = prompt($question['text'], $question['default']);
	} while (function_exists($question['callback']) && !call_user_func($question['callback'], $answer));

	$config[$question['name']] = $answer;
}

$config_file = fopen(CONFIG_FILE, 'w');
foreach ($config as $option => $value) {
	fputs($config_file, "{$option} = {$value}\n");
}
fclose($config_file);

echo _("Configuration successful finished\n");