<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Alexandr
 * Date: 28.09.10
 */
 
class Files extends RequestHandler {
	protected $default_action = 'show_all';

	/**
	 * @request_handler
	 * @return array
	 */
	public function show_all($params) {
		$this->getFiles();

	}

	/**
	 * @request_handler
	 * @return array
	 */
	public function upload($params) {
		$result = array('data' => $_SESSION['upload_data']);
		unset($_SESSION['upload_data']);
		return $result;
	}

	/**
	 * @request_handler
	 * @return array
	 */
	public function put() {
		$dir = rtrim(Config::getConfig('repository'), '\\/') . DIRECTORY_SEPARATOR;

		$processed_files = array();

		$db = DB::getInstance();
		$insert_file = $db->prepare("
			INSERT INTO
				`file`
				(`file_name`, `original_name`, `type`, `size`, `description`)
			VALUES 
				(:file_name, :original_name, :type, :size, :description)
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

			if (move_uploaded_file($_FILES["attach"]['tmp_name'][$f], $dir . $file_name)) {
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
			}
		}

		$error_message = nl2br(trim($error_message));
		return array('redirect' => 'upload', 'data' => compact('processed_files', 'error_message'));
	}

	protected function getFiles() {
		$query = SqlBuilder::newQuery()->from('file')->select('*')->where('public', 1)->order('upload DESC')->limit(25);
		$db = DB::getInstance();
		$db->query($query->getSql())->fetchAll();
	}
}
