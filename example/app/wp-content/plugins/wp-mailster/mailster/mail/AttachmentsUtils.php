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

class MstAttachmentsUtils
{

	public static function getDispositionType($dispositionStr){
		$dispositionStr = trim(strtoupper($dispositionStr));
		if($dispositionStr === 'ATTACHMENT'){
			return MstConsts::DISPOSITION_TYPE_ATTACH;
		}elseif($dispositionStr === 'INLINE'){
			return MstConsts::DISPOSITION_TYPE_INLINE;
		}
		return MstConsts::DISPOSITION_TYPE_ATTACH;
	}

	public static function storeAttachments($baseDir, $attachs){
		$log = MstFactory::getLogger();
		$mstUtils 	= MstFactory::getUtils();
		$hashUtils 	= MstFactory::getHashUtils();
		$fileUtils 	= MstFactory::getFileUtils();
		$convUtils 	= MstFactory::getConverterUtils();
		$upload_dir = wp_upload_dir();

		$dir = $upload_dir["path"] ."/";
		$partialpath = $upload_dir["subdir"] ."/";
		$fullpath = $upload_dir["path"] ."/";
		//$dir = str_replace(get_home_path(), "", $dir);
		$nrAttachs = count($attachs);
		$log->debug('storeAttachments called with ' . $nrAttachs . ' attachments in the queue');
		$log->debug('attachment base dir: ' . $baseDir );
		$savedAttachs = array();
		if ($nrAttachs > 0) {
			$a = 0;
			if (!is_dir($fullpath)) { //create specific folders
				$log->debug('we have to create (day) dir ' . $fullpath);

				$ok = wp_mkdir_p($fullpath);
				$ok ? $log->debug('dir creation was ok') : $fileUtils->isDirWritable($fullpath, true, 'ERROR');
			}
			$rndStr = $hashUtils->getFixedLengthRandomString();
			$dirpath = $dir."/".$rndStr;
			$fulldirpath = $fullpath.$rndStr;
			$partialdirpath = $partialpath.$rndStr;

			$log->debug('target dir ' . $fulldirpath);
			$cr = 0;
			while(is_dir($fulldirpath) && $cr < 10) {
				$log->warning('dir already existing, build new random name, try no ' . ($cr+1));
				$rndStr = $hashUtils->getFixedLengthRandomString();
				$dirpath = $dir . "/" . $rndStr;
				$fulldirpath = $fullpath.$rndStr;
				$partialdirpath = $partialpath.$rndStr;
				$log->debug('target dir ' . $fulldirpath);
				$cr = $cr + 1;  // do not try forever
			}
			if(!is_dir($fulldirpath)){
				$log->debug('creating (mail) dir ' . $fulldirpath);
				$ok = wp_mkdir_p($fulldirpath);
				$ok ? $log->debug('dir creation was ok') : $fileUtils->isDirWritable($fulldirpath, true, 'ERROR');
				while ($a <= $nrAttachs-1) {
					$filename = utf8_decode($convUtils->imapUtf8($attachs[$a]['filename']));
					$log->debug('Untouched filename: '.$attachs[$a]['filename']);
					$log->debug('imapUtf8 filename: '.$convUtils->imapUtf8($attachs[$a]['filename']));
					$log->debug('UTF8 decoded filename: '.$filename);
					if ($filename == '') {
						$log->warning('We have to insert attachment file name "' .  MstConsts::ATTACHMENT_NO_FILENAME_FOUND . '" as no name is given...');
						$filename = MstConsts::ATTACHMENT_NO_FILENAME_FOUND;
					}
					$dispositionNr = self::getDispositionType($attachs[$a]['disposition']);
					$contentId 	= $attachs[$a]['content_id'];
					$input 		= $attachs[$a]['filedata'];
					$type	 	= $attachs[$a]['type'];
					$subtype	= $attachs[$a]['subtype'];
					$params		= $attachs[$a]['params'];

					$filePath = $dirpath."/".$filename;
					$fullfilePath = $fulldirpath."/".$filename;
					$partialfilePath = $partialdirpath."/".$filename;
					$attachPath = $fullfilePath;
					$log->info('storeAttachments: ' . $filename . ' -> ' . $attachPath);

					$newSavedAttac = new stdClass();
					$newSavedAttac->filename = $filename;
					$newSavedAttac->filepath = $partialdirpath;
					$newSavedAttac->content_id = $contentId;
					$newSavedAttac->disposition = $dispositionNr;
					$newSavedAttac->type = $type;
					$newSavedAttac->subtype = $subtype;
					$newSavedAttac->params = $params;

					$log->debug('storeAttachments: File Info: ' . print_r($newSavedAttac, true));
					$log->debug('Will save file to: ' . $attachPath);

					if (file_put_contents($attachPath, $input)) {
						$newSavedAttac->success = true;
						$log->info('File stored successfully');
					} else {
						$newSavedAttac->success = false;
						$log->error('ERROR, file was not stored');
						$fileUtils->isFileWritable($fullfilePath, $attachPath, true, 'ERROR');
					}
					$savedAttachs[] =  $newSavedAttac;

					$a++; // next attachment, no matter if succeeded or not
				}
			}else{
				$log->error('Could not create target dir in ' . $dir);
			}
		}
		$log->debug('leaving storeAttachments with ' . count($savedAttachs) . ' attachments stored');
		return $savedAttachs;
	}

	public static function getAttachmentTypeString($type, $subtype){
		$log = MstFactory::getLogger();
		$mailUtils 	= MstFactory::getMailUtils();
		$typeStr 	= $mailUtils->getContentTypeString($type);
		$typeStr 	= trim(strtolower($typeStr)) . '/' . trim(strtolower($subtype));
		return $typeStr;
	}

	/**
	 * @param $mailId
	 * @param $attachs
	 */
	public static function saveAttachmentsInDB($mailId, $attachs){
		$log = MstFactory::getLogger();
		global $wpdb;

		for($i=0; $i < count($attachs); $i++){
			$attach = &$attachs[$i];
			$log->debug('Saving to database: ' . print_r($attach, true));
			$query = 'INSERT INTO '
			         . $wpdb->prefix . 'mailster_attachments'
			         . '(id,'
			         . ' mail_id,'
			         . ' filename,'
			         . ' filepath,'
			         . ' content_id,'
			         . ' disposition,'
			         . ' type,'
			         . ' subtype,'
			         . ' params'
			         . ' )VALUES'
			         . ' (NULL,'
			         . '  \'' . $mailId . '\','
			         . '  \'' . $wpdb->_real_escape($attach->filename) . '\','
			         . '  \'' . $wpdb->_real_escape($attach->filepath) . '\','
			         . '  \'' . $wpdb->_real_escape($attach->content_id). '\','
			         . '  \'' . $wpdb->_real_escape($attach->disposition). '\','
			         . '  \'' . $attach->type . '\','
			         . '  \'' . $wpdb->_real_escape($attach->subtype). '\','
			         . '  \'' . $wpdb->_real_escape($attach->params). '\''
			         . ')';
			$result = false;
			$errorMsg = '';
			try {
				$result = $wpdb->query( $query ); // update cache version/state
			} catch (DatabaseException $e) {
				$errorMsg = 'Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
			}
			$attachId = $wpdb->insert_id;
			if($attachId < 1){
				$log->error('Inserting of attachment failed, ' . $errorMsg);
			}else{
				$log->info('Saved attachment ' . $attach->filename . ' of mail ' . $mailId . '  new id: ' . $attachId);
			}
		}
	}

	public static function copyAttachments2Mail($mailIdSource, $mailIdTarget){
		$log = MstFactory::getLogger();
		$log->debug('copyAttachments2Mail: copy attachments of mail '.$mailIdSource . ' to mail '.$mailIdTarget);
		$attachs = self::getAttachmentsOfMail($mailIdSource);
		$log->debug('copyAttachments2Mail: mail '.$mailIdSource . ' has '.count($attachs). ' attachments');
		self::saveAttachmentsInDB($mailIdTarget, $attachs);
	}

	public static function getAttachmentsOfMail($mailId){
		$log = MstFactory::getLogger();
		global $wpdb;
		$query = 'SELECT * from ' . $wpdb->prefix . 'mailster_attachments WHERE mail_id = \''.$mailId.'\'';
		$attachs = $wpdb->get_results( $query );
		return $attachs;
	}

	public static function getAttachment($attachId){
		$log = MstFactory::getLogger();
		global $wpdb;
		$query = 'SELECT * from ' . $wpdb->prefix . 'mailster_attachments WHERE id = \''.$attachId.'\'';

		$attach = $wpdb->get_row( $query );
		return $attach;
	}

	public static function deleteAttachmentsOfMail($mailId, $removeEntryFromDB=true){
		$log = MstFactory::getLogger();
		$mailModel = MstFactory::getMailModel();
		$mailModel->setId($mailId);
		$mail = $mailModel->getData();
		if($mail->has_attachments == 1){
			$log->debug('Mail ' . $mailId . ' has attachments, delete them');
			$attachFolder = '';
			$attachs = self::getAttachmentsOfMail($mailId);
			for($i=0; $i < count($attachs); $i++){
				$attach = &$attachs[$i];
				$filePath = $attach->filepath;
				$fileName = $attach->filename;
				$fname = wp_get_upload_dir()."/".$filePath."/".$fileName;
				$attachFolder = wp_get_upload_dir()."/".$filePath;
				$log->debug('Work with attachment: ' . $fname .' (ID: '.$attach->id.')');
				if(!is_dir($fname)){
					if(file_exists($fname)){
						if(!touch($fname)){
							$log->error('can not touch ' . $fname);
						}
						if(unlink($fname)){
							$log->debug('attachment successfully deleted/unlinked: ' . $fname);
						}else{
							$log->error('attachment not deleted/unlinked: ' . $fname);
						}
					}else{
						$log->error('attachment does not exist: ' . $fname);
					}
				}else{
					$log->error('attachment not a file: ' . $fname);
				}

				if($removeEntryFromDB && $attach->id > 0){
					$log = MstFactory::getLogger();
					global $wpdb;

					$query = 'DELETE FROM ' . $wpdb->prefix . 'mailster_attachments'
					         . ' WHERE id = \''. $attach->id .'\'';
					try {
						$result = $wpdb->query( $query );
					} catch (DatabaseException $e) {
						$errorMsg = 'Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
						$log->error('deleteAttachmentsOfMail Failed to delete attachment DB entry of attachment ID: '.$attach->id.', error: ' . $errorMsg);
						return false;
					}
				}
			}

			// try to delete directory that contained attachments
			$filePath = $attachFolder;
			if(is_dir($filePath)){
				if(rmdir($filePath)){
					$log->debug('attachment dir successfully deleted: ' . $filePath);
				}else{
					$log->error('attachment dir not deleted: ' . $filePath);
				}
			}
		}else{
			$log->debug('Mail ' . $mailId . ' has no attachments to delete');
		}
	}
	
	
}
