<?php

class Template {
	private function __construct() {}

	const T_PATH = 'templates';

	protected static $block = array();
	protected static $process = array();

	protected static function templateExists($name) {
		return file_exists($name = ROOT_DIR . DIRECTORY_SEPARATOR . self::T_PATH . DIRECTORY_SEPARATOR . $name . '.html') ? $name : '';
	}

	public static function showPage($template_name, $args = array()) {
		extract(Config::getAll());
		extract($args);

		if (!($template_full_name = self::templateExists($template_name))) {
			$message = _(sprintf('Template "%s" not found', $template_name));
			$template_full_name = self::templateExists('internal_error');
		}

		include $template_full_name;

		$action = array_pop(self::$process);
		is_array($action) and array_key_exists('template', $action) and self::showPage($action['template'], self::$block);
	}

	public static function showErrorPage($message) {
		ob_end_clean();
		self::showPage("internal_error", array('message' => $message));
		die();
	}

	public static function show404Page() {
		ob_end_clean();
		self::showPage("404", array('url' => FULL_REQUEST));
		die();
	}

	protected static function block($name) {
		ob_clean();
		array_push(self::$process, array('block' => $name));
	}

	protected static function endBlock() {
		self::$block[reset(array_pop(self::$process))] = ob_get_contents();
		ob_clean();
	}

	protected static function inherit($name) {
		array_push(self::$process, array('template' => $name));
	}

	protected static function incl($name, $args = array()) {
		array_push(self::$process, array('include' => $name));
		self::showPage($name, $args);
	}
}