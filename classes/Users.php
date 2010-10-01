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
		return User::isLogged() ? array('redirect' => 'profile') : array('data' => User::getFormData());
	}

	/**
	 * @request_handler
	 * @return array
	 */
	public function enter($params) {
		$fields = array_fill_keys(array('email', 'passwd'), '');
		$post = array_intersect_key(array_merge($fields, $_POST), $fields);

		$message = '';
		$db = DB::getInstance();
		$q_user = $db->query("SELECT `id`, `email`, `passwd`, IF(`name` = '', `email`, `name`) `name` FROM `user` WHERE `email` = " . $db->quote($post['email']) . "LIMIT 1");
		if ($q_user->rowCount() == 0) {
			$message = _('Entered email not registered');
		} else {
			$user = $q_user->fetch(PDO::FETCH_ASSOC);
			if ($user['passwd'] != $post['passwd']) {
				$message = _('Incorrect password');
			} else {
				User::setAuth($user);
			}
		}

		return array('data' => array('message' => $message, 'form' => $post), 'redirect' => empty($message) ? 'profile' : 'login');
	}

	/**
	 * @request_handler
	 * @return array
	 */
	public function logout($params) {
		User::setAuth(null);
		return array('redirect' => 'login');
	}

	/**
	 * @request_handler
	 * @return array
	 */
	public function register($params) {
		return User::isLogged() ? array('redirect' => 'profile') : array('data' => User::getFormData());
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
