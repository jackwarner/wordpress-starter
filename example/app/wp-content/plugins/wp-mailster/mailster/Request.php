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
//TODO delete?
	class MstRequest 
	{
		
		public static function getArray($name, $default=array()){
			return $_REQUEST[$name];
		}
		
		public static function getInt($name, $default=0){
			return intval($_REQUEST[$name]);
		}
				
		public static function getRawStr($name, $default=''){
			return $_REQUEST[$name];
		}
				
		public static function getStr($name, $default=''){
			return $_REQUEST[$name];
		}
		
		public static function getPost(){
			return $_POST;
		}
	
	}
?>
