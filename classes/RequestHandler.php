<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Alexandr
 * Date: 28.09.10
 */
 
class RequestHandler {
	protected $default_action = '__non_exist';

	public function processRequest($action, array $params) {
		empty($action) and $action = $this->default_action;
		if (!$this->isMethodCallable($action)) {
			Template::show404Page();
			return;
		}

		$request_result = $this->$action($params);
		$template_name = !empty($request_result['template']) ? $request_result['template'] : strtolower(get_class($this) . '_' . $action);

		Template::showPage($template_name, $request_result['data']);
	}

	private function isMethodCallable($method) {
		$reflection = new ReflectionMethod($this, $method);
		return (bool) preg_match('!\* @request_handler!', $reflection->getDocComment());
	}
}
