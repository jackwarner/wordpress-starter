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

	class MstLog
	{
		
		const DEBUG = 	'DEBU';
		const INFO = 	'INFO';
		const WARNING = 'WARN';
		const ERROR = 	'ERRO';
		
	    public static function log($msg, $level='INFO', $typeNr=0)
	    {   
	    	$logFileName = WP_PLUGIN_DIR."/wp-mailster/log/mailster.log.php";

		    // Initialise the file if not already done.
		    $log = new MstLog();

            try{
                $log->initFile();
            }catch(RuntimeException $e){
                return false;
            }

	    	$isInstallEntry = ($typeNr == MstConsts::LOGENTRY_INSTALLER) ? true : false;
	    	
	    	global $mailster_install_running; //TODO find out how this is used
			$isInstallEntry = ($isInstallEntry || ($mailster_install_running == 1));
	    	
	    	if( $isInstallEntry == true ) {
	    		return self::installLog($msg, $level); // during install don't do other tests...
	    	}
	    	
	    	if( self::loggingLevelSufficient( $level ) ) {	

    			$level = strtoupper($level);
		        if(is_array($msg)){
		        	$msg = print_r($msg, true);
		        }
		        if(is_object($msg)){
		        	$msg = serialize($msg);
		        }
	    		
		        if( self::isLog2File() ){
					if( self::loggingPossible() || self::isLoggingForced() ){
                        $logFile = fopen($logFileName, "a");
                        $msg = date("Y-m-d H:i:s") . ' ' . $level . ' ' . $msg;
                        fwrite($logFile, $msg . "\n"); //write to the file
		            }
		        }
	            
		        if( self::isLog2Database() ){
		            $levelNr = self::getLoggingLevel($level);
		            self::log2Database($msg, $levelNr, $typeNr); // Log to database
		        }
	    	}
	    }
	    
	    public static function installLog($msg, $level='INFO'){
            $installLogFile = self::getInstallLogFile();
	    	if( self::loggingPossible($installLogFile, false) ){
                $logFile = fopen($installLogFile, "a");
                $msg = date("Y-m-d H:i:s") . ' ' . $level . ' ' . $msg;
                fwrite($logFile, $msg . "\n"); //write to the file
	    	}
	    }
	    
	    public static function getAllLogEntries(){	    	
	    	global $wpdb;
	    	$query = 'SELECT * FROM ' . $wpdb->prefix . 'mailster_log';
			$res = $wpdb->get_results($query);
			return $res;
	    }
	    
	    public static function log2Database($msg, $levelNr, $typeNr){
	    	global $wpdb;
	    	$query = 'INSERT INTO ' 
							. $wpdb->prefix . 'mailster_log' 
							. '(id,' 
							. ' level,' 
							. ' type,' 
							. ' log_time,' 
							. ' msg' 
							. ' )VALUES'
							. ' (NULL,'
							. '  \'' . $levelNr . '\','
							. '  \'' . $typeNr . '\','
							. '  NOW(),'
							. ' ' . $msg . ''
							. ')';
			$res = $wpdb->get_results($query);
            return $res;
	    }	    
	    
	    public static function warning($msg, $typeNr=0)
	    {
	        self::log($msg, self::WARNING, $typeNr);
	    }
	    public static function error($msg, $typeNr=0)
	    {
	        self::log($msg, self::ERROR, $typeNr);
	    }
	    public static function debug($msg, $typeNr=0)
	    {
	        self::log($msg, self::DEBUG, $typeNr);
	    }
	    public static function info($msg, $typeNr=0)
	    {
	        self::log($msg, self::INFO, $typeNr);
	    }
	    
	    public static function getLoggingLevel($entryType){
	    	switch($entryType){
	    		case self::ERROR:
	    			return MstConsts::LOG_LEVEL_ERROR;
	    		case self::WARNING:
	    			return MstConsts::LOG_LEVEL_WARNING;
	    		case self::INFO:
	    			return MstConsts::LOG_LEVEL_INFO;
	    		case self::DEBUG:
	    			return MstConsts::LOG_LEVEL_DEBUG;
	    	}
	    	return 0;
	    }

	    public static function getLoggingLevelStr($entryTypeNr){
	    	switch($entryTypeNr){
	    		case MstConsts::LOG_LEVEL_ERROR:
	    			return __('Error', 'wpmst-mailster' );
	    		case MstConsts::LOG_LEVEL_WARNING:
	    			return __('Warning', 'wpmst-mailster' );
	    		case MstConsts::LOG_LEVEL_INFO:
	    			return __('Info', 'wpmst-mailster' );
	    		case MstConsts::LOG_LEVEL_DEBUG:
	    			return __('Debug', 'wpmst-mailster' );
	    	}
	    	return 0;
	    }
	    
	    public static function getLoggingTypeStr($typeNr){
	    	switch($typeNr){
	    		case MstConsts::LOGENTRY_INSTALLER:
	    			return __('Installer', 'wpmst-mailster' );
	    		case MstConsts::LOGENTRY_PLUGIN:
	    			return __('Plugin', 'wpmst-mailster' );
	    		case MstConsts::LOGENTRY_MAIL_RETRIEVE:
	    			return __('Mail Retrieving', 'wpmst-mailster' );
	    		case MstConsts::LOGENTRY_MAIL_SEND:
	    			return __('Mail Sending', 'wpmst-mailster' );
	    	}
	    	return "";
	    }
	 
	    public static function loggingLevelSufficient($entryType){
	    	$loggingLevel = self::getCurrentLoggingLevel();
	    	if($loggingLevel > 0){ // when Logging Level is zero then nothing will be logged
		    	$entryLevel = self::getLoggingLevel($entryType); // get Level that this entry needs
	            if($entryLevel <= $loggingLevel){ 
	            	return true; // entry can be logged
	            }
	    	}
            return false; // entry may not be logged
		}
		
		public static function isLoggingActive(){
			return (self::getCurrentLoggingLevel() > 0);
		}
		
		public static function getCurrentLoggingLevel(){
			$mstConf = MstFactory::getConfig();
			return $mstConf->getLoggingLevel(); // get current Logging Level			
		}
		
		public static function getCurrentLoggingLevelString(){
			$loggingLevel = self::getCurrentLoggingLevel();
			return self::getLoggingLevelStr($loggingLevel);
		}
		
		public static function getLogFileFolder(){
			$logPath = WP_PLUGIN_DIR."/wp-mailster/log/";
			return $logPath;
		}
		
		public static function getLogFile($logFileName = 'mailster.log.php'){			
			$logPath = self::getLogFileFolder();
			$logFile = $logPath . $logFileName;
			return $logFile;
		}

        public static function getInstallLogFile($logFileName = 'mailster.install.log.php'){
            $logPath = self::getLogFileFolder();
            $logFile = $logPath . $logFileName;
            return $logFile;
        }
	    
	    public static function loggingPossible($logFileName = 'mailster.log.php', $addPath=true){
	    	$fileUtils = MstFactory::getFileUtils();
            if($addPath){
                $logPath = self::getLogFileFolder();
                $filePath = $logPath . $logFileName;
                $takeFileNameAsPath = false;
            }else{
                $filePath = $logFileName;
                $logPath = null;
                $takeFileNameAsPath = true;
            }
            if(!file_exists($filePath)){
                error_log('File does not exist: '.$filePath);
            }
            
			return $fileUtils->fileWritable($logFileName, $logPath, $takeFileNameAsPath);
		}
	    
	    public static function isLoggingForced(){
			$mstConf = MstFactory::getConfig();
			return $mstConf->isLoggingForced();
	    }
		
	    public static function isLog2File(){
			$mstConf = MstFactory::getConfig();
			return $mstConf->isLog2File();
	    }
		
	    public static function isLog2Database(){
			$mstConf = MstFactory::getConfig();
			return $mstConf->isLog2Database();
	    }
	    
	    public static function getLogDatabaseEntriesCount(){
	    	global $wpdb;
	    	$dbUtils = MstFactory::getDBUtils();
	    	return $dbUtils->getTableRowCount($wpdb->prefix.'mailster_log');
	    }
	    
	    public static function isLogDatabase2Big($rowLimit){
	    	return (self::getLogDatabaseEntriesCount() > $rowLimit);
	    }
	    
	    public static function isLogFile2Big($fileLimitInByte, $logFileName = 'mailster.log.php'){
	    	if(self::loggingPossible($logFileName)){ // log writable?
	    		$logFile = self::getLogFile($logFileName);
	    		if(!is_file($logFile)){ // log file existing?
	    			return false; // not existing, cannot be to big...
	    		}
	    		$lSize = filesize($logFile);
	    		return ($lSize > $fileLimitInByte);
	    	}
	    }

		protected static function generateFileHeader()
		{
			$head = array();
			// Build the log file header.
			// If the no php flag is not set add the php die statement.
			if ( !file_exists(self::getLogFile()) )
			{
				// Blank line to prevent information disclose: https://bugs.php.net/bug.php?id=60677
				$head[] = '#';
				$head[] = '<?php die(\'Forbidden.\'); ?>';
			}

			$head[] = '#Date: ' . date('Y-m-d H:i:s') . ' UTC';
			$head[] = '';

			return implode("\n", $head);
		}

        /**
         * @throws RuntimeException
         */
        public static function initFile()
		{
			$logFile = self::getLogFile();
            $installLogFile = self::getInstallLogFile();
			// We only need to make sure the file exists
			if (file_exists($logFile) && file_exists($installLogFile))
			{
				return;
			}
			// Build the log file header.
			$head = self::generateFileHeader();

            if (!file_exists($logFile)){
                $logFileHandler = fopen($logFile, "w");
                if ( !fwrite($logFileHandler, $head . "\n") ) {
                    throw new RuntimeException('Cannot write to log file '.$logFile);
                }
                fclose($logFileHandler);
            }

            if (!file_exists($installLogFile)){
                $logFileHandler = fopen($installLogFile, "w");
                if ( !fwrite($logFileHandler, $head . "\n") ) {
                    throw new RuntimeException('Cannot write to log file '.$installLogFile);
                }
                fclose($logFileHandler);
            }
		}
	    
	}
?>
