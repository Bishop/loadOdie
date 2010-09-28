<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Alexandr
 * Date: 28.09.10
 */
 
class RequestHandler {
	public function processRequest($action, array $params) {
		if (!$this->isMethodCallable($action)) {
			Template::show404Page();
			return;
		}

		$request_result = $this->$action();
	}

	private function isMethodCallable($method) {
		$reflection = new ReflectionMethod($this, $method);
		return (bool) preg_match("/\\* @request_handler/", $reflection->getDocComment());
	}
}
