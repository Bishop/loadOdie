<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Alexandr
 * Date: 28.09.10
 */
 
class Files extends RequestHandler {
	protected $default_action = 'show_all';

	public $files_per_page = 10;

	/**
	 * @request_handler
	 * @return array
	 */
	public function show_all($params) {
		$class = strtolower(get_class($this));

		$orders = array(
			'name' => 'original_name',
			'upload' => 'upload DESC'
		);
		$sort = empty($params['sort']) ? 'upload' : $params['sort'];

		$query = SqlBuilder::newQuery()->from('file')->select('*')->where('public', 1)->order($orders[$sort])->limit($this->files_per_page);

		$db = DB::getInstance();

		return array('data' => array(
			'files' => $db->query($query->getSql())->fetchAll(),
			'base_url' => "/$class/" . __FUNCTION__ . "/sort/$sort",
		));
	}

	/**
	 * @request_handler
	 * @return array
	 */
	public function detail($params) {
		empty($params['id']) and Template::show404Page();

		$db = DB::getInstance();

		$file = $db->query(SqlBuilder::newQuery()->from('file')->select('*')->where('id', $params['id'])->limit(1)->getSql())->fetch() or Template::show404Page();

		$sorted_comments = array();

		$comments = $db->query(SqlBuilder::newQuery()->from('comment')->select('*')->where('file_id', $params['id'])->order('id ASC')->join('user_id', 'user', 'id')->from('user')->select('name')->select('email')->getSql())->fetchAll(PDO::FETCH_ASSOC) or $comments = array();

		$map = array();
		foreach ($comments as &$comment) {
			$map[$comment['id']] = array('id' => $comment['id'], 'reply' => $comment['reply_to'], 'reply_to' => $comment['reply_to'], 'level' => 0);
			empty($comment['name']) and $comment['name'] = reset(explode('@', $comment['email']));
			unset($comment['email']);
		}

		$comments = $this->comment_sort($comments, null);

		return array('data' => compact('file', 'comments'));
	}

	protected function comment_sort($comments, $item, $level = 0) {
		$result = array();
		foreach ($comments as $comment) {
			if ($comment['reply_to'] == $item) {
				$comment['level'] = $level;
				$result[] = $comment;
				$result = array_merge($result, $this->comment_sort($comments, $comment['id'], $level + 1));
			}
		}
		return $result;
	}

	/**
	 * @request_handler
	 * @return array
	 */
	public function upload($params) {
		return array('data' => User::getFormData());
	}

	/**
	 * @request_handler
	 * @return array
	 */
	public function put($params) {
		$dir = rtrim(Config::getConfig('repository'), '\\/') . DIRECTORY_SEPARATOR;

		$processed_files = array();

		$db = DB::getInstance();

		$user_id = User::info('id') or $user_id = 0;

		$insert_file = $db->prepare("
			INSERT INTO
				`file`
				(`file_name`, `original_name`, `type`, `size`, `description`, `user_id`)
			VALUES 
				(:file_name, :original_name, :type, :size, :description, $user_id)
		");

		$ip = $db->quote(ip2long($_SERVER['REMOTE_ADDR']));
		$user_agent = $db->quote($_SERVER['HTTP_USER_AGENT']);
		$insert_upload = $db->prepare("
			INSERT INTO
				`upload`
				(`file_id`, `ip`, `user_agent`)
			VALUES
				(:file_id, $ip, $user_agent)
		");

		$error_message = '';

		foreach ($_FILES['attach']['error'] as $f => $error) {
			if ($error != UPLOAD_ERR_OK) {
				continue;
			}

			$file_name = uniqid();

			if (!file_exists($dir . $file_name) && move_uploaded_file($_FILES["attach"]['tmp_name'][$f], $dir . $file_name)) {
				try {
					$db->beginTransaction();

					$insert_file->execute(array(
						'file_name' => $file_name,
						'original_name' => $_FILES["attach"]['name'][$f],
						'type' => $_FILES["attach"]['type'][$f],
						'size' => $_FILES["attach"]['size'][$f],
						'description' => $_POST['description']
					));
					$insert_upload->execute(array('file_id' => $db->lastInsertId()));

					$db->commit();
					$processed_files[] = $_FILES["attach"]['name'][$f];
				} catch (PDOException $e) {
					$error_message .= $e->getMessage() . "\n";
					$db->rollBack();
					unlink($dir . $file_name);
				}
			} else {
				$error_message .= _('Error occurred while file uploading. Please, try again') . "\n";
			}
		}

		$error_message = nl2br(trim($error_message));
		return array('redirect' => 'upload', 'data' => compact('processed_files', 'error_message'));
	}
}
