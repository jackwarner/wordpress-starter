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
    die('These droids are not the droids you are looking for.');
}

class MstPluginUtils
{
    public static function resetMailPluginTimes(){

        $minSendTime 		= 	get_option('minsendtime', 60);
        $minCheckTime 		= get_option('minchecktime', 240);
        $minMaintenanceTime = get_option('minmaintenance', 3600);
        $tNow = time();
        if($tNow > 0){
            $lastExecRetrieve = $tNow - $minCheckTime;
            $lastExecSending = $tNow - $minSendTime;
            $lastExecMaintenance = $tNow - $minMaintenanceTime;
            update_option('last_exec_retrieve', $lastExecRetrieve);
            update_option('last_exec_sending', $lastExecSending);
            update_option('last_exec_maintenance', $lastExecMaintenance);
            return true;
        }else{
            return false;
        }
    }

    public static function getNextMailCheckTime(){
        $minCheckTime = 	get_option('minchecktime', 240);
        $lastCheckTime =	get_option('last_exec_retrieve', 0);
        return $lastCheckTime + $minCheckTime;
    }

    public static function getNextMailSendTime(){
        $minSendTime = 	get_option('minsendtime', 60);
        $lastSendTime =	get_option('last_exec_sending', 0);
        return $lastSendTime + $minSendTime;
    }

    public static function getNextMaintenanceTime(){
        $minSendTime = 	get_option('minmaintenance', 3600);
        $lastSendTime =	get_option('last_exec_maintenance', 0);
        return $lastSendTime + $minSendTime;
    }

}
