<?php

class Template {
	private function __construct() {}

	const T_PATH = 'templates';

	protected static function templateExists($name) {
		return file_exists($name = ROOT_DIR . DIRECTORY_SEPARATOR . self::T_PATH . DIRECTORY_SEPARATOR . $name . '.html') ? $name : '';
	}

	public static function showPage($template_name, $args = array()) {
		extract($args);

		if (!($template_full_name = self::templateExists($template_name))) {
			$message = _(sprintf('Template "%s" not found', $template_name));
			$template_full_name = self::templateExists('internal_error');
		}

		include $template_full_name;
	}
}