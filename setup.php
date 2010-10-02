<?php

include dirname(__FILE__) . DIRECTORY_SEPARATOR . 'configure.php';

PHP_SAPI == 'cli' or die(_('Using only in console'));

echo _(sprintf("Welcome to %s setup wizard\n\n", SOFTWARE_NAME));

function prompt($prompt, $default = '') {
	echo "{$prompt} [{$default}]:\n> ";
	$answer = trim(fgets(STDIN)) or $answer = $default;
	return $answer;
}

function confirm($prompt, $ok = 'y') {
	return prompt($prompt, $ok) == $ok;
}

function verify_connection_sting($connection_string) {
	return preg_match(CONNECTION_STRING_FORMAT, $connection_string) || !print(_('Invalid database connection string') . "\n");
}

function verify_directory($directory) {
	if (file_exists($directory)) {
		return confirm(sprintf("Directory %s exists. Use it?", $directory));
	}
	mkdir($directory);
	return true;
}

function create_structure() {
	$db = DB::getInstance();

	$sqls = array(
		"
			CREATE TABLE IF NOT EXISTS `user` (
				`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				`email` VARCHAR(200) NOT NULL,
				`passwd` VARCHAR(200) NOT NULL,
				`name` VARCHAR(200) NOT NULL,
				`joined` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`),
				UNIQUE INDEX `email` (`email`)
			) COLLATE='utf8_general_ci' ENGINE=InnoDB
		",
		"
			CREATE TABLE IF NOT EXISTS `file` (
				`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				`file_name` VARCHAR(250) NOT NULL,
				`user_id` BIGINT(20) UNSIGNED NOT NULL,
				`original_name` VARCHAR(250) NOT NULL,
				`type` VARCHAR(50) NOT NULL,
				`size` BIGINT(20) UNSIGNED NOT NULL,
				`description` TEXT NULL,
				`public` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
				`comments` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
				`upload` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`),
				UNIQUE INDEX `file_name` (`file_name`)
			) COLLATE='utf8_general_ci' ENGINE=InnoDB
		",
		"
			CREATE TABLE IF NOT EXISTS `upload` (
				`file_id` BIGINT(20) UNSIGNED NOT NULL,
				`ip` INT(11) NOT NULL,
				`user_agent` TEXT NOT NULL,
				UNIQUE INDEX `file_id` (`file_id`)
			) COLLATE='utf8_general_ci' ENGINE=InnoDB
		",
		"
			CREATE TABLE IF NOT EXISTS `comment` (
				`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				`file_id` BIGINT(20) UNSIGNED NOT NULL,
				`reply_to` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
				`user_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
				`comment` TEXT NOT NULL,
				`added` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`)
			) COLLATE='utf8_general_ci' ENGINE=InnoDB
		",
	);

	foreach ($sqls as $sql) {
		$db->exec($sql);
	}
}

/* PROCESS */

if (file_exists(CONFIG_FILE) && !confirm(sprintf("Config file '%s' exists. Continue and overwrite it?", CONFIG_FILE))) {
	exit();
}

$questions = array(
	array('name' => 'database', 'text' => _('Database connection string'), 'default' => 'mysql://root:pass@localhost:3306/files', 'callback' => 'verify_connection_sting'),
	array('name' => 'repository', 'text' => _('Files location'), 'default' => dirname(__FILE__) . DIRECTORY_SEPARATOR . 'files', 'callback' => 'verify_directory'),
);

$config = array();

foreach ($questions as $question) {
	do {
		$answer = prompt($question['text'], $question['default']);
	} while (function_exists($question['callback']) && !call_user_func($question['callback'], $answer));

	$config[$question['name']] = $answer;
}

Config::setConfig($config);

try {
	create_structure();
} catch (Exception $e) {
	echo $e->getMessage() . "\n\n";
	die();
}

$config_file = fopen(ROOT_DIR . CONFIG_FILE, 'w');
foreach ($config as $option => $value) {
	fputs($config_file, "{$option} = {$value}\n");
}
fclose($config_file);

echo _("Configuration successful finished\n");