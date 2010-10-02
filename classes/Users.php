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
		$q_user = $db->query(SqlBuilder::newQuery()->from('user')->select('*')->where('email', $db->quote($post['email']))->limit(1)->getSql());
		if ($q_user->rowCount() == 0) {
			$message = _('Entered email not registered');
		} else {
			$user = $q_user->fetch(PDO::FETCH_ASSOC);
			if ($user['passwd'] != $post['passwd']) {
				$message = _('Incorrect password');
			} else {
				empty($user['name']) and $user['name'] = $user['email'];
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

	/**
	 * @request_handler
	 * @return array
	 */
	public function profile($params) {
		$files = new Files();
		$params['user_id'] = User::info('id');
		$result = $files->show_all($params);
		$re = '!^(/\w+/\w+/user_id/\d+)(.*)!';
		$result['data']['user_url'] = (empty($_SERVER["HTTPS"]) ? 'http' : 'https') . '://' . $_SERVER["HTTP_HOST"] . preg_replace($re, '$1', $result['data']['base_url']);
		$result['data']['base_url'] = preg_replace($re, '/users/' . __FUNCTION__, $result['data']['base_url']);
		$result['data'] += User::getFormData();
		return $result;
	}

	/**
	 * @request_handler
	 * @return array
	 */
	public function change_my_files($params) {
		$message = '';
		$action = filter_input(INPUT_POST, 'action');
		$ids = filter_input(INPUT_POST, 'ids', FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY);
		$change_success = false;

		if (!(empty($action) || empty($ids))) {
			try {
				if ($action == 'delete') {
					$this->delete_files($ids);
				} else {
					$fields = array('public', 'comments');
					$values = array('+' => 1, '-' => 0);
					if (preg_match('/([+-])(\w+)/', $action, $matches)) {
						list(, $value, $field) = $matches;
						if (in_array($field, $fields) && array_key_exists($value, $values)) {
							$ids = array_map(array(DB::getInstance(), 'quote'), $ids);
							$sql = "UPDATE `file` SET `$field` = {$values[$value]} WHERE `id` IN (" . implode(',', $ids) . ")";
							DB::getInstance()->exec($sql);
							$change_success = true;
						}
					}
				}
			} catch (Exception $e) {
				$message = $e->getMessage();
			}
		}

		return array('redirect' => 'profile', 'data' => compact('message', 'change_success'));
	}

	protected function delete_files($ids) {

	}
}
