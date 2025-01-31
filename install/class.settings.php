<?php
require_once(dirname(__FILE__).'/../require/settings.php');
require_once(dirname(__FILE__).'/../require/class.Common.php');

class settings {
	public static function modify_settings($settings) {
		$Common = new Common();
		$settings_filename = '../require/settings.php';
		$content = file_get_contents($settings_filename);
		$fh = fopen($settings_filename,'w');
		foreach ($settings as $settingname => $value) {
			if ($value == 'TRUE' || $value == 'FALSE') {
			    $pattern = '/\R\$'.$settingname." = ".'(TRUE|FALSE)'."/";
			    $replace = "\n".'\$'.$settingname." = ".$value."";
			} elseif (is_array($value)) {
			    $pattern = '/\R\$'.$settingname." = array\(".'(.*)'."\)/";
			    if ($Common->isAssoc($value)) {
				foreach ($value as $key => $data) {
				    if (!isset($array_value)) {
					$array_value = "'".$key."' => '".$data."'";
				    } else {
					$array_value .= ",'".$key."' => '".$data."'";
				    }
				}
			    } else {
				foreach ($value as $data) {
				    if (!isset($array_value)) {
					$array_value = "'".$data."'";
				    } else {
					$array_value .= ",'".$data."'";
				    }
				}
			    }
			    if (!isset($array_value)) $array_value = '';
			    $replace = "\n".'\$'.$settingname." = array(".$array_value.")";
			    unset($array_value);
			} else {
			    $pattern = '/\R\$'.$settingname." = '".'(.*)'."'/";
			    $replace = "\n".'\$'.$settingname." = '".$value."'";
			}
			$rep_cnt = 0;
			$content = preg_replace($pattern,$replace,$content,1,$rep_cnt);
			
			/// If setting was a string and is now an array
			if ($rep_cnt == 0 && is_array($value)) {
			    $pattern = '/\R\$'.$settingname." = '".'(.*)'."'/";
			    $content = preg_replace($pattern,$replace,$content,1,$rep_cnt);
			}
			
			// If setting is not in settings.php (for update)
			if ($rep_cnt == 0) {
			    $content = preg_replace('/\?>/',$replace.";\n?>",$content,1,$rep_cnt);
			}

		}
		fwrite($fh,$content);
		fclose($fh);
	}
}

//settings::modify_setting('globalName','titi');
?>