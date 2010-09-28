<?php

/**
 * Created by JetBrains PhpStorm.
 * User: Alexandr
 * Date: 28.09.10
 * Time: 21:54
 */
 
if (!defined('STDIN')) {
	define('STDIN', fopen('php://stdin', 'r'));
}

if (!is_callable('gettext')) {
	function _($msgid) { return $msgid; }
	function gettext($msgid) { return $msgid; }
	function ngettext($msgid1, $msgid2, $n) { return $n == 1 ? $msgid1 : $msgid2; }
}

define('CONFIG_FILE', 'app.cfg');