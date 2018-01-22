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
	
class MstConfiguration
{

	public static function getProperty($property, $default){
		$propPath = self::_getConfigFilePath();
		$confIO = MstFactory::getConfIO();
		return $confIO->getProperty($propPath, $property, $default);
	}	

	public static function getKeepBlockedEmailsForDays() {
		return get_option( 'keep_blocked_mails_for_days', 30 );
	}
	public static function getKeepBouncedEmailsForDays() {
		return get_option( 'keep_bounced_mails_for_days', 30 );
	}
	
	public static function getLoggingLevel() {
		return get_option( 'logging_level', MstConsts::LOG_LEVEL_INFO );
	}
	
	public static function getLogFileSizeWarningLevel() {
		return get_option( 'log_file_warning_size_mb', 25 ); 
	}
	
	public static function getLogDatabaseWarningEntries() {
		return get_option( 'log_db_warning_entries_nr', 1000 ); 
	}
	
	public static function isLog2File() {
		$logDest = get_option( 'log_entry_destination' );
		if( $logDest == MstConsts::LOG_DEST_FILE || $logDest == MstConsts::LOG_DEST_DB_AND_FILE ) {
			return true;
		} else {
			return false;
		}		
	}
	
	public static function isLog2Database() {
		$logDest = get_option( 'log_entry_destination' );
		if ( $logDest == MstConsts::LOG_DEST_DB || $logDest == MstConsts::LOG_DEST_DB_AND_FILE ) {
			return true;
		} else {
			return false;
		}
	}
	
	public static function isUseAlternativeTextVars() {
		return ( get_option('use_alt_txt_vars') > 0 ); 
	}
	
	public static function isLoggingForced() {
		return ( get_option( 'force_logging' ) > 0 );
	}
	
	public static function useMailingListAddressAsFromField() {			
		return ( get_option( 'mail_from_field' ) > 0 ); 
	}
	
	public static function useMailingListNameAsFromField() {
		return ( get_option( 'name_from_field' ) > 0 ); 
	}
	
	public static function insertSenderAddressForEmptySenderName() {
		return ( get_option( 'mail_from_email_for_from_name_field' ) > 0 );
	}

	public static function getBlockedEmailAddresses() {
		$words = array();
		$wordsStr = get_option( 'blocked_email_addresses' );
		$words = explode( ',', $wordsStr );
		if ( $words ) {
			$nrWords = count( $words );
			for ( $i=0; $i < $nrWords; $i++ ) {
				$word = $words[ $i ];
				$word = trim( $word );
				if ( ( $word === '' ) || ( $word === MstConsts::NO_PARAMETER_SUPPLIED_FLAG ) ) {
					unset( $words[$i] ); // remove empty element
				} else {
					$words[ $i ] = $word; // take trimmed version
				}
			}
			$words = array_values( $words ); // re-index
		} else {
			$words = array();
		}
		return $words;
	}
	
	public static function getWordsToFilter() {
		$words = array();
		$wordsStr = get_option( 'words_to_filter' );
		$words = explode( ',', $wordsStr );
		if ( $words ) {
			$nrWords = count( $words );
			for( $i=0; $i < $nrWords; $i++ ) {
				$word = $words[ $i ];
				$word = trim( $word );
				if ( ( $word === '' ) || ( $word === MstConsts::NO_PARAMETER_SUPPLIED_FLAG ) ) {
					unset( $words[ $i ] ); // remove empty element
				}
			}
			$words = array_values( $words ); // re-index
		} else {
			$words = array();
		}
		return $words;
	}
	
	public static function getDigestMailFormat() {
		$dateFormat = trim( get_option( 'digest_format_html_or_plain' ) );
		if( strlen( $dateFormat ) < 1 ) {
			$dateFormat = 'plain';
		}
		return $dateFormat;
	}
	
	public static function getDateFormat() {		
		$dateFormat = trim( get_option( 'mail_date_format' ) );
		if( is_null($dateFormat) || strlen( $dateFormat ) < 1 ) {
			$dateFormat = get_option( 'date_format', 'd/m/Y' ) . " " . get_option( 'time_format', 'H:i:s' );
		}
		return $dateFormat;
	}
	
	public static function getDateFormatWithoutTime() {
		$dateFormat = trim( get_option( 'mail_date_format_without_time' ) );
		if ( strlen( $dateFormat ) < 1 ) {
			$dateFormat = get_option( 'date_format', 'd/m/Y');
		}
		return $dateFormat;
	}
	
	public static function addMailsterMailHeaderTag() {	
		if ( get_option( 'tag_mailster_mails' ) > 0 ) {
			return true;
		}
		return false;
	}
	
	public static function includeBodyInBouncedBlockedNotifications() {	
		if ( get_option( 'include_body_in_blocked_bounced_notifies', 1 ) > 0 ) {
			return true;
		}
		return false;
	}
	
	public static function isUndoLineWrapping(){	
		if ( get_option( 'undo_line_wrapping' ) > 0 ) {
			return true;
		}
		return false;
	}

	public static function getMaxMailsPerMinute() {
		$default = 0;
		if ( MstFactory::getV()->getFtSetting( MstVersionMgmt::MST_FT_ID_THROTTLE ) ) {
			return get_option('max_mails_per_minute', $default);
		} else {
			return $default;
		}
	}

	public static function getMaxMailsPerHour() {
		$default = 0;
		if ( MstFactory::getV()->getFtSetting( MstVersionMgmt::MST_FT_ID_THROTTLE ) ) {
			return get_option( 'max_mails_per_hour', $default );
		} else {
			return $default;
		}
	}

	public static function getWaitBetweenTwoMails() {			
		$default = 0;	
		if ( MstFactory::getV()->getFtSetting( MstVersionMgmt::MST_FT_ID_THROTTLE ) ) {
			return get_option( 'wait_between_two_mails', $default );
		} else {
			return $default;
		}
	}
	
	public static function getLastMailSentAt() {
		$default = -1;	
		return get_option( 'last_mail_sent_at', $default );		
	}
	
	public static function getLastHourMailSentIn() {
		$default = -1;	
		return get_option( 'last_hour_mail_sent_in', $default );
	}
	
	public static function getNrOfMailsSentInLastHour() {
		$default = -1;	
		return get_option( 'nr_of_mails_sent_in_last_hour', $default );
	}
	
	public static function getLastDayMailSentIn() {
		$default = -1;	
		return get_option( 'last_day_mail_sent_in', $default );
	}
	
	public static function getNrOfMailsSentInLastDay() {
		$default = -1;
		return get_option( 'nr_of_mails_sent_in_last_day', $default );
	}
	
	public static function setLastMailSentAt($lastMailSentAtTime){
		/*$params = self::getComponentParams();
		if($params){
			$params->set('last_mail_sent_at', $lastMailSentAtTime);
			return self::updateComponentParams($params);
		}*/
		update_option('last_mail_sent_at', $lastMailSentAtTime);
	}
	
	public static function setLastHourMailSentIn($lastHourMailSentIn){
		/*$params = self::getComponentParams();
		if($params){
			$params->set('last_hour_mail_sent_in', $lastHourMailSentIn);
			return self::updateComponentParams($params);
		}*/
		update_option('last_hour_mail_sent_in', $lastHourMailSentIn);
	}
	
	public static function setNrOfMailsSentInLastHour($nrOfMailsSentInLastHour){
		/*$params = self::getComponentParams();
		if($params){
			$params->set('nr_of_mails_sent_in_last_hour', $nrOfMailsSentInLastHour);
			return self::updateComponentParams($params);
		}*/
		update_option( 'nr_of_mails_sent_in_last_hour', $nrOfMailsSentInLastHour );
	}
	
	public static function setLastDayMailSentIn($lastDayMailSentIn){
		/*$params = self::getComponentParams();
		if($params){
			$params->set('last_day_mail_sent_in', $lastDayMailSentIn);
			return self::updateComponentParams($params);
		}*/
		update_option( 'last_day_mail_sent_in', $lastDayMailSentIn );
	}
	
	public static function setNrOfMailsSentInLastDay($nrOfMailsSentInLastDay){
		/*$params = self::getComponentParams();
		if($params){
			$params->set('nr_of_mails_sent_in_last_day', $nrOfMailsSentInLastDay);
			return self::updateComponentParams($params);
		}*/
		update_option( 'nr_of_mails_sent_in_last_day', $nrOfMailsSentInLastDay );
	}
	
	public static function addSubjectPrefixToReplies() {
		if ( get_option( 'add_reply_prefix' ) > 0 ) {
			return true;
		}
		return false;
	}

	//TODO!
	public static function getMailboxOpenTimeout() {
		if ( get_option( 'imap_opentimeout' ) > 0 ) {
			return get_option( 'imap_opentimeout' );
		}
		return 20;
	}	
	
	public static function getReplyPrefix() {		
		return get_option( 'reply_prefix', '' );
	}

	public static function getRecaptchaV2Keys() {
		$keys = array();
		$keys[ 'public' ] = get_option( 'recaptcha2_public_key', '' );
		$keys[ 'private' ] = get_option( 'recaptcha2_private_key', '' );
		return $keys;
	}
	
	public static function getRecaptchaParamString() { // no longer needed (was for reCAPTCHA v1)
		$theme = 'red';
		$lang = 'en';
		$paramStr =  "theme : '" . $theme . "', lang :'" . $lang . "'";			
		return $paramStr;
	}

    public static function getRecaptchaTheme(){
        return get_option( 'recaptcha_theme', 'light' );
    }
}