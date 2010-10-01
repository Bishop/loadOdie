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
		$class = strtolower(get_class($this));

		if (!empty($request_result['redirect'])) {
			header("location: /$class/{$request_result['redirect']}");
			empty($request_result['data']) or User::storeFormData($request_result['data']);
			die();
		}

		$template_name = !empty($request_result['template']) ? $request_result['template'] : $class . '_' . $action;

		Template::showPage($template_name, $request_result['data']);
	}

	private function isMethodCallable($method) {
		try {
			$reflection = new ReflectionMethod($this, $method);
			return (bool) preg_match('!\* @request_handler!', $reflection->getDocComment());
		} catch (ReflectionException $re) {
			return false;
		}
	}
}
