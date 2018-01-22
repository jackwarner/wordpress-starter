<?php
	/** Plugin
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
	die( 'These are not the droids you are looking for.' );
}
	class plgSystemMailster {
		var $execEnd;
		var $jPlugin;
		var $mstPlugin;
		var $pluginParams;
		var $minDuration;
		var $maxExecTime;
		var $plgId;
		var $plgCanRun;
		
		private function initMailPlugin(){	
			$time = time();	
			
			if(!class_exists('MstFactory', false)){
				$this->plgCanRun = false; // include of basic classes failed, abort
				return;
			}else{
				$this->plgCanRun = true;
			}

			$this->minDuration = get_option( 'minduration', '2' );
			$this->maxExecTime = get_option( 'maxexectime', '10' );
			$this->execEnd = $time + $this->maxExecTime;
		}
				
		function onAfterInitialise() {
			$this->initMailPlugin();
			if(!$this->plgCanRun){
				return false; // Do not proceed further
			}

			$log = MstFactory::getLogger();
			$senderObj 		= MstFactory::getMailSender();
			$retrieverObj 	= MstFactory::getMailRetriever();
			
			if($this->isPluginRunAllowed()){
				 if($this->isTriggerSourceOk()){
                    global $mailster_trigger_source;
                     $isAdmin = $this->inAdminOrOnWpLogin();
                     $mailster_trigger_source = ($isAdmin ?  MstConsts::TRIGGER_SOURCE_PLUGIN_BACKEND : MstConsts::TRIGGER_SOURCE_PLUGIN_FRONTEND);
                     $log->debug('--- --- --- --- --- Plugin run allowed (config. trigger source: ' . get_option( 'trigger_source', 'all' ) . ') --- --- --- --- ---', MstConsts::LOGENTRY_PLUGIN);

                     if($this->isMailRetrievingRequired()){
                         $log->debug('Time left to run: ' . $this->timeLeft()
									. ' for retrieving mails (execEnd: ' 
									. $this->execEnd . ', now: ' . time() . '), PHP max. exec: ' . ini_get('max_execution_time'), MstConsts::LOGENTRY_PLUGIN);

                         $mstV = MstFactory::getV()->getProductName(true);
                         global $wp_version;
                         $log->debug($mstV . ' running on WordPress ' . $wp_version);
                         $nrRetrievedMessages = $retrieverObj->retrieveMailsOfActiveMailingLists($this->minDuration, $this->execEnd);
                         $log->debug('Retrieved '.$nrRetrievedMessages. ' mails in this plugin run', MstConsts::LOGENTRY_PLUGIN);

                         update_option("last_exec_retrieve", time());

                         if($this->isMailRetrievingRequired()){
                             $lastExecRetrieve 	= 	get_option('last_exec_retrieve', -1);
                             $timeSinceLastRetrieve 	= (time() - $lastExecRetrieve);
                             $log->warning('Mail retrieving still required after reset, last exec: ' . $lastExecRetrieve
										 . ', time since last exec: ' . $timeSinceLastRetrieve, MstConsts::LOGENTRY_PLUGIN);
                         }
                     }

                     if($this->isMailSendingRequired()){
                         $log->debug(($this->isTimeLeft() ? ('Time left to run: ' . $this->timeLeft() . ' for sending mails (execEnd: '
                                                                . $this->execEnd . ', now: ' . time()
																. '), PHP max. exec: ' . ini_get('max_execution_time') )
														: 'No time left for sending mails'), MstConsts::LOGENTRY_PLUGIN);
                         if($this->isTimeLeft()){
                             $nrEmailsSent = $senderObj->sendMails($this->minDuration, $this->execEnd);
                             $log->debug('Sent '.$nrEmailsSent. ' mails in this plugin run', MstConsts::LOGENTRY_PLUGIN);

                             update_option("last_exec_sending", time());

                             if($this->isMailSendingRequired()){
                                 $lastExecSending 	=     get_option( 'last_exec_sending', -1 );
                                 $timeSinceLastSending	= (time() - $lastExecSending);
                                 $log->warning('Mail sending still required after reset, last exec: ' . $lastExecSending
                                     . ', time since last exec: ' . $timeSinceLastSending, MstConsts::LOGENTRY_PLUGIN);
							}
                         }
                     }
                 }else{
                     $log->debug('*** *** *** *** *** *** Plugin run NOT allowed for mail tasks (config. trigger source: ' . get_option( 'trigger_source', 'all' ) . ') *** *** *** *** *** ***', MstConsts::LOGENTRY_PLUGIN);
                 }
                $this->handleMaintenance();	// see if maintenance should be performed in any case
			}else{			
				if($this->isNoExecutionFlagSet()){
					$log->debug('*** *** *** *** *** *** Plugin run NOT allowed (no execution flag set) *** *** *** *** *** ***', MstConsts::LOGENTRY_PLUGIN);
				}elseif($this->isNoExecutionFileExisting()){
					$log->debug('*** *** *** *** *** *** Plugin run NOT allowed (no execution file existing) *** *** *** *** *** ***', MstConsts::LOGENTRY_PLUGIN);
				}elseif($this->isCronjobRunning()){
					$log->debug('*** *** *** *** *** *** Plugin run NOT allowed (cron job running) *** *** *** *** *** ***', MstConsts::LOGENTRY_PLUGIN);
				}elseif($this->isInstallationRunning()){
					$log->debug('*** *** *** *** *** *** Plugin run NOT allowed (installation running) *** *** *** *** *** ***', MstConsts::LOGENTRY_PLUGIN);
				}
			}
			
			return true; // Job done
		}
		
		private function handleMaintenance(){
			$log = MstFactory::getLogger();
			if($this->isMaintenanceRequired()){
				$log->debug(($this->isTimeLeft() ? ('Time left to run: ' . $this->timeLeft() . ' for maintenance (execEnd: ' 
														. $this->execEnd . ', now: ' . time() 
														. '), PHP max. exec: ' . ini_get('max_execution_time') )
												: 'No time left for maintenance'), MstConsts::LOGENTRY_PLUGIN);
				if($this->isTimeLeft()){
					$mstApp = MstFactory::getApplication();
					$mstApp->performMaintenance($this->minDuration, $this->execEnd);
					
					update_option("last_exec_maintenance", time());
				}
			}
		}
		
		private function isTimeLeft(){
			$timeLeft = $this->timeLeft();
			return ($timeLeft > $this->minDuration);
		}
		
		private function timeLeft(){
            $log = MstFactory::getLogger();
			$tNow = time();		
			$t1 =  $this->execEnd-$tNow;
			if($t1 < -30000){ // is time incorrectly considered negative?	
				$log->warning('Timestamp negative (tNow: ' . $tNow . ', t1: ' . $t1 . ', execEnd: ' . $this->execEnd . ')', MstConsts::LOGENTRY_PLUGIN);			
				$t1 = $this->minDuration+3; // add 3 seconds to have more than min duration
			}
			return $t1;
		}
		
		private function isPluginRunAllowed(){
			if($this->isInstallationRunning()) return false; // check for running installation
			if($this->isNoExecutionFlagSet()) return false; // check for manual set no execution flag
			if($this->isNoExecutionFileExisting()) return false; // check for no execution file
			if($this->isCronjobRunning()) return false; // check for running cronjob
			return true;
		}
		
		private function isTriggerSourceOk(){
            $triggeredAtBackend = $this->inAdminOrOnWpLogin();
			$triggerSrc = get_option( 'trigger_source', MstConsts::PLUGIN_TRIGGER_SRC_ALL );
			$triggerSrcOk = false;			
			if($triggerSrc === MstConsts::PLUGIN_TRIGGER_SRC_ALL){
				$triggerSrcOk = true;
			}elseif($triggerSrc === MstConsts::PLUGIN_TRIGGER_SRC_BACKEND){
				$triggerSrcOk = ($triggeredAtBackend ? true : false);
			}elseif($triggerSrc === MstConsts::PLUGIN_TRIGGER_SRC_CRONJOB){
				$triggerSrcOk = false; // do not execute during (Pro) cronjobs
			}			
			return $triggerSrcOk;
		}

        private function inAdminOrOnWpLogin(){
            $isAdmin = is_admin();
            $onWpLogin = ($GLOBALS['pagenow'] == 'wp-login.php');
            return ($isAdmin || $onWpLogin);
        }
						
		private function isNoExecutionFlagSet(){
			if( isset( $_GET[MstConsts::PLUGIN_FLAG_NO_EXECUTION] ) ) {
				$noExecution = sanitize_text_field($_GET[MstConsts::PLUGIN_FLAG_NO_EXECUTION]);
			} else {
				$noExecution = false;
			}
			return $noExecution;
		}

		private function isNoExecutionFileExisting() {
			$upload_dir = wp_upload_dir();
			$basepath = $upload_dir['basedir'];
			return ( file_exists($basepath . "/" . 'no_mst_plg_exec')
					|| file_exists($basepath . "/" . 'no_mst_plg_exec.txt') );
		}
		
		private function isInstallationRunning(){ //CHECK WordPress does not run anything untill installation is complete
			/*$u = JURI::getInstance(); // try to get params from URL
			$option = trim(strtolower($u->getVar('option')));				
			if(strlen($option  < 1)){ // for SEF...
				$option = trim(strtolower(JRequest::getString('option')));
			}			
			if($option === 'com_installer'){
				return true; // we are in com_installer and paranoid - just exit without checking task...
			}*/
			return false;
		}
						
		private function isCronjobRunning(){
			if ( isset( $_REQUEST['controller'] ) ) {
				$controller = sanitize_text_field($_REQUEST['controller']);
				if(strtolower($controller) === 'cron'){
					return true; // cron job active
				}
			}
			return false;
		}
		
		private function isMailRetrievingRequired(){
			$log = MstFactory::getLogger();
			$minCheckTime 		= 	get_option('minchecktime', 240);
			$lastExecRetrieve 	= 	get_option('last_exec_retrieve', -1);
			
			$req = $this->runRequired($minCheckTime, $lastExecRetrieve);
			$log->debug('Retrieving req: ' . ($req ? 'yes' : 'no') 
						. ', last: ' . $lastExecRetrieve . ', now: ' . time()
						. ', min time: ' . $minCheckTime, MstConsts::LOGENTRY_PLUGIN);
			return $req;		
		}
		
		private function isMailSendingRequired(){
			$log = MstFactory::getLogger();
			$minSendTime		= 	get_option('minsendtime', 60);
			$lastExecSending 	= 	get_option('last_exec_sending', -1);
			
			$req = $this->runRequired($minSendTime, $lastExecSending);
			$log->debug('Sending req: ' . ($req ? 'yes' : 'no') 
						. ', last: ' . $lastExecSending . ', now: ' . time()
						. ', min time: ' . $minSendTime, MstConsts::LOGENTRY_PLUGIN);
			return $req;
		}
		
		private function isMaintenanceRequired(){
			$log = MstFactory::getLogger();
			$minMaintenanceTime	= 	get_option('minmaintenance', 3600);
			$lastExecMaintenance= 	get_option('last_exec_maintenance', -1);
			
			$req = $this->runRequired($minMaintenanceTime, $lastExecMaintenance);
			$log->debug('Maintenance req: ' . ($req ? 'yes' : 'no') 
						. ', last: ' . $lastExecMaintenance . ', now: ' . time()
						. ', min time: ' . $minMaintenanceTime, MstConsts::LOGENTRY_PLUGIN);
			return $req;
		}
		
		private function runRequired($minDiff, $lastExec){
			$log = MstFactory::getLogger();
			$timeSinceLastExec	= (time() - $lastExec);
			if($lastExec < 0){
				$log->warning('Last exec timestamp negative: ' . $lastExec, MstConsts::LOGENTRY_PLUGIN);
			}
			if($timeSinceLastExec < 0){
				$log->warning('Time since last exec negative: ' . $timeSinceLastExec, MstConsts::LOGENTRY_PLUGIN);
			}
			return ( ($lastExec < 0) || ($timeSinceLastExec >= $minDiff) );
		}
	
	}
?>
