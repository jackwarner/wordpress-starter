<?php

	/**
	 * @copyright (C) 2016 - 2017 Holger Brandt IT Solutions
	 * @license GNU/GPL, see license.txt
	 * WP Mailster is free software; you can redistribute it and/or
	 * modify it under the terms of the GNU General Public License 2
	 * as published by the Free Software Foundation.
	 * 
	 * WP Mailster is distributed in the hope that it will be useful,
	 * but WITHOUT ANY WARRANTY; without even the implied warranty of
	 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	 * GNU General Public License for more details.
	 * 
	 * You should have received a copy of the GNU General Public License
	 * along with WP Mailster; if not, write to the Free Software
	 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA
	 * or see http://www.gnu.org/licenses/.
	 */

	if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
	die('These are not the droids you are looking for.');
}
	
	class MstConfIO
	{
		
		public static function loadConf($file){		
			$props = array();	
			if (isset($file) && is_file($file)) {
				$props = parse_ini_file($file);
			}			
			return $props;
		}		
		
		public static function saveConf($file, $array){
				return self::write_php_ini($file, $array);
		}	
		
		public static function getProperty($file, $property, $default=NULL, $saveWhenNotExistent=false){
			$log = MstFactory::getLogger();
			$props = self::loadConf($file);
			foreach ($props as $key => $val){
				if(is_array($val)){ // contains section?
		            foreach($val as $skey => $sval){		            
						if($skey == $property){
							return $sval;
						}
		            }
		        }else{
					if($key == $property){
						return $val;
					}
		        }
			}
			if($saveWhenNotExistent){
				self::saveProperty($file, $property, $default);
			}
			return $default;
		}
		
		public static function saveProperty($file, $key, $value, $section=NULL){
			$log = MstFactory::getLogger();
			$props = self::loadConf($file);
			if(!is_null($section)){
				if(!is_array($props[$section])){
					$props[$section] = array();
				}
				$props[$section][$key] = $value;				
			}else{
				$props[$key] = $value;
			}
			return self::saveConf($file, $props);
		}
		
		protected static function write_php_ini($file, $array){
			$log = MstFactory::getLogger();
		    $res = array();
		    foreach($array as $key => $val){
		        if(is_array($val)){ // contains section?
		            $res[] = "[$key]"; 
		            foreach($val as $skey => $sval) $res[] = "$skey = ".(is_numeric($sval) ? $sval : '"'.$sval.'"');
		        }
		        else $res[] = "$key = ".(is_numeric($val) ? $val : '"'.$val.'"');
		    }
		    return self::safefilerewrite($file, implode("\r\n", $res));
		}
		
		
		protected static function safefilerewrite($fileName, $dataToSave)
		{    
			if ($fp = fopen($fileName, 'w')){
		        $startTime = microtime(true);
		        do{    
		        	$isLocked = flock($fp, LOCK_EX);
		           // Wait/Sleep 0 - 100 ms for lock, to avoid collision
		           if(!$isLocked) usleep(round(rand(0, 100)*1000));
		        } while ((!$isLocked)and((microtime(true)-$startTime) < 1000));
		
		        //file was locked so now we can store information
		        if ($isLocked){          
		        	fwrite($fp, $dataToSave);
		            flock($fp, LOCK_UN);
		        }
		        
		        fclose($fp);
		        return true;
		    }
		    return false;		
		}
		
	}

?>
