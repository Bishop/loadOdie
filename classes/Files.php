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
		$page = empty($params['page']) ? 1 : $params['page'];

		$db = DB::getInstance();
		$base_url = "/$class/" . __FUNCTION__;

		$query = SqlBuilder::newQuery()->from('file')->select('*')->order($orders[$sort]);
		if (!empty($params['user_id'])) {
			$query->where('user_id', $db->quote($params['user_id']));
			$base_url .= "/user_id/" . $params['user_id'];
		}

		if (empty($params['user_id']) || $params['user_id'] != User::info('id')) {
			$query->where('public', 1);
		}

		$query_count = clone $query;
		$query->limit($this->files_per_page, ($page - 1) * $this->files_per_page);

		return array('data' => array(
			'files' => $db->query($query->getSql())->fetchAll(),
			'base_url' => $base_url,
			'main_url' => $base_url . "/sort/$sort",
			'pages' => ceil($db->query($query_count->getSql(true))->fetchColumn() / $this->files_per_page),
			'page' => $page,
		));
	}

	/**
	 * @request_handler
	 * @return array
	 */
	public function detail($params) {
		empty($params['file_id']) and Template::show404Page();

		$file_id = $params['file_id'];

		$db = DB::getInstance();

		$file = $db->query(SqlBuilder::newQuery()->from('file')->select('*')->where('id', $file_id)->limit(1)->getSql())->fetch() or Template::show404Page();

		$comments = $db->query(SqlBuilder::newQuery()->from('comment')->select('*')->where('file_id', $file_id)->order('id ASC')->join('user_id', 'user', 'id')->from('user')->select('name')->select('email')->getSql())->fetchAll(PDO::FETCH_ASSOC) or $comments = array();

		$map = array();
		foreach ($comments as &$comment) {
			$map[$comment['id']] = array('id' => $comment['id'], 'reply' => $comment['reply_to'], 'reply_to' => $comment['reply_to'], 'level' => 0);
			empty($comment['name']) and $comment['name'] = reset(explode('@', $comment['email']));
			unset($comment['email']);
		}

		$comments = $this->comment_sort($comments, null);

		$data = User::getFormData();
		$message = isset($data['message']) ? $data['message'] : '';

		return array('data' => compact('file', 'comments', 'file_id', 'message'));
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
	public function add_comment($params) {
		$user_id = User::info('id');
		$file_id = filter_input(INPUT_POST, 'file_id');
		$reply_to = filter_input(INPUT_POST, 'reply_to');
		$comment = filter_input(INPUT_POST, 'comment');

		$message = '';
		if (!(empty($user_id) || empty($file_id) || empty($comment))) {
			try {
				$db = DB::getInstance();
				$insert_comment = $db->prepare("
					INSERT INTO
						`comment`
						(`file_id`, `reply_to`, `user_id`, `comment`, `added`)
					VALUES
						(:file_id, :reply_to, :user_id, :comment, NOW())
				");
				$insert_comment->execute(compact('user_id', 'file_id', 'reply_to', 'comment'));
			} catch (PDOException $e) {
				$message = $e->getMessage();
			}
		}

		return array('redirect' => "detail/file_id/$file_id", 'data' => compact('message'));
	}

	/**
	 * @request_handler
	 * @return array
	 */
	public function comment($params) {
		(empty($params['file_id']) || empty($params['reply_to'])) and Template::show404Page();

		$file_id = $params['file_id'];
		$reply_to = $params['reply_to'];

		$db = DB::getInstance();

		$file = $db->query(SqlBuilder::newQuery()->from('file')->select('*')->where('id', $file_id)->limit(1)->getSql())->fetch() or Template::show404Page();

		$comment = $db->query(SqlBuilder::newQuery()->from('comment')->select('*')->where('id', $reply_to)->getSql())->fetch() or Template::show404Page();

		return array('data' => compact('file', 'comment', 'reply_to', 'file_id'));
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

	/**
	 * @request_handler
	 * @return array
	 */
	public function download($params) {
		empty($params['name']) and Template::show404Page();

		$db = DB::getInstance();
		$file = $db->query(SqlBuilder::newQuery()->from('file')->select('*')->where('file_name', $db->quote($params['name']))->limit(1)->getSql())->fetch() or Template::show404Page();

		$dir = rtrim(Config::getConfig('repository'), '\\/') . DIRECTORY_SEPARATOR;

		ob_end_clean();
		header("Content-Type: {$file['type']}");
		header("Accept-Ranges: bytes");
		header("Content-Length: {$file['size']}");
		header("Content-Disposition: inline; filename={$file['original_name']}");
		header('Content-Transfer-Encoding: binary');

		readfile($dir . $file['file_name']);
	}
}
