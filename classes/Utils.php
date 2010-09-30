<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Alexandr
 * Date: 01.10.10
 */
 
class Utils {
	public static function fileSize($size) {
		$units = array(_('B'), _('KB'), _('MB'), _('GB'), _('TB'));
		for ($i = 0; $size >= 1024 && $i < count($units); $i++) $size /= 1024;
		return round($size, 2) . ' ' . $units[$i];
	}
}
