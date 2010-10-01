<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Alexandr
 * Date: 01.10.10
 */

class Users extends RequestHandler {
	protected $default_action = 'login';

	/**
	 * @request_handler
	 * @return array
	 */
	public function login($params) {
		return array('data' => User::getFormData());
	}

	/**
	 * @request_handler
	 * @return array
	 */
	public function register($params) {
		return array('data' => User::getFormData());
	}

	/**
	 * @request_handler
	 * @return array
	 */
	public function create($params) {
		$fields = array_fill_keys(array('email', 'passwd', 'name'), '');
		$post = array_intersect_key(array_merge($fields, $_POST), $fields);

		$message = '';
		$post['email'] = filter_var($post['email'], FILTER_VALIDATE_EMAIL) or $message = _('Incorrect email');
		empty($message) and strlen($post['passwd']) < 8 and $message = _('Too short password');

		if (empty($message)) {
			$db = DB::getInstance();
			try {
				$insert_user = $db->prepare("
					INSERT INTO
						`user`
						(`email`, `passwd`, `name`, `joined`)
					VALUES
						(:email, :passwd, :name, NOW())
				");
				$insert_user->execute($post);
			} catch (PdoException $e) {
				$message = $e->getMessage();
			}
		}
		return array('data' => array('message' => $message, 'register_success' => empty($message), 'form' => $post), 'redirect' => empty($message) ? 'login' : 'register');
	}
}
