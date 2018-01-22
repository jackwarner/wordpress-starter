<?php
	if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
		die( 'These are not the droids you are looking for.' );
	}

//connection check
add_action( 'wp_ajax_conncheck', 'conncheck_callback' );
function conncheck_callback() {
	
	$task = sanitize_text_field($_POST["task"]);
	$mstUtils 	= MstFactory::getUtils();
	$mstConfig	= MstFactory::getConfig();
	$log	= MstFactory::getLogger();
    /** @var MailsterModelServer $serverModel */
    $serverModel = MstFactory::getServerModel();
	$resultArray = array();
	$res = __("Connection Check called", "wpmst-mailster");
	$mailSettingsLink = 'https://wpmailster.com/doc/mail-provider-settings';
	$ajaxParams =sanitize_text_field($_POST['mtrAjaxData']);
	$ajaxParams = $mstUtils->jsonDecode(stripslashes($ajaxParams));
	
	if($task == 'inboxConnCheck'){
		$res = __("Inbox Check called", "wpmst-mailster");

        $in_server 	 = $ajaxParams->{'in_server'};
		$in_host 	 = trim($ajaxParams->{'in_host'});
		$in_port 	 = trim($ajaxParams->{'in_port'});
		$in_user 	 = $ajaxParams->{'in_user'};
		$in_pw 		 = $ajaxParams->{'in_pw'};
		$in_secure	 = $ajaxParams->{'in_secure'};
		$in_sec_auth = $ajaxParams->{'in_sec_auth'};
		$in_protocol = $ajaxParams->{'in_protocol'};
		$in_params 	 = $ajaxParams->{'in_params'};
		
		if (extension_loaded('imap')){

           /* if($in_server > 0){
                $server = $serverModel->getServer($in_server);
                $server = $server[0];
                $in_host = $server->server_host;
                $in_port = $server->server_port;
                $in_secure = $server->secure_protocol;
                $in_sec_auth = $server->secure_authentication;
                $in_protocol = $server->protocol;
                $in_params = $server->connection_parameter;
            }*/
            $useSecAuth = $in_sec_auth !== '0' ? '/secure' : '';
            $useSec	 = $in_secure !== '' ? '/' . $in_secure : '';
            $protocol = $in_protocol !== '' ? '/' . $in_protocol : '';

			$openTimeoutOld = imap_timeout(IMAP_OPENTIMEOUT);
			$chgTimeoutSuccess = imap_timeout(IMAP_OPENTIMEOUT, $mstConfig->getMailboxOpenTimeout());
			$host = '{' . $in_host . ':' . $in_port . $useSecAuth . $protocol . $useSec . $in_params . '}'. 'INBOX';
			$mBox = @imap_open ($host, $in_user, $in_pw);
			$imapErrors = imap_errors();				
			if($mBox){
				$res = __("Inbox settings OK", "wpmst-mailster");
				//$imapcheck = imap_check($mBox);
				//$nMsgs = $imapcheck->Nmsgs; // number of messages in mailbox						
				imap_close($mBox); // close mail box
			}else{						
				if($imapErrors){
					$errorMsg = "\n"."\n".__("Errors", "wpmst-mailster").":\n";
					foreach($imapErrors as $error){
						$errorMsg =  $errorMsg."\n" . $error;
					}
				}else{
					$errorMsg = "\n"."\n".__("No error messages available", "wpmst-mailster");
				}
				$errorMsg =  $errorMsg."\n";
				$res = __("Connection NOT ok. Check your settings!", "wpmst-mailster"). $errorMsg;
			}
			$chgTimeoutSuccess = imap_timeout(IMAP_OPENTIMEOUT, $openTimeoutOld);						
		}else{
			$res = __("Connection not possible, no IMAP extension available in PHP installation.", "wpmst-mailster")."\n";
			$res .= __("Install PHP with IMAP library support.", "wpmst-mailster"); // "Install PHP with IMAP support.";
		}
	
	}else if($task == 'outboxConnCheck'){

       	$out_server 	 = $ajaxParams->{'out_server'};
		$list_name 		 = $ajaxParams->{'list_name'};
		$admin_email 	 = $ajaxParams->{'admin_email'};
		$use_cms_mailer  = $ajaxParams->{'use_cms_mailer'};
		$out_email 	 	 = $ajaxParams->{'out_email'};
		$out_user 	 	 = $ajaxParams->{'out_user'};
		$out_pw 	 	 = $ajaxParams->{'out_pw'};
		$out_host 		 = $ajaxParams->{'out_host'};
		$out_secure 	 = $ajaxParams->{'out_secure'};
		$out_sec_auth  	 = $ajaxParams->{'out_sec_auth'};
		$out_port	  	 = $ajaxParams->{'out_port'};
		$out_name 		 = get_bloginfo("name");

        /*if($out_server > 0){
            $server = $serverModel->getServer($out_server);
            $server = $server[0];
            $out_host = $server->server_host;
            $out_port = $server->server_port;
            $out_secure = $server->secure_protocol;
            $out_sec_auth = $server->secure_authentication;
        }*/
		
		$res = __("Sender check called", "wpmst-mailster");
		 
		$body = __("This is a test message sent from the mailing list", "wpmst-mailster") . ' \'' . $list_name  . '\' ';
		$body .= __("at the webpage", "wpmst-mailster") . ' \'' . $out_name . '\'' . "\n";
		$body .= __("You receive this email because you are the admin of the list.", "wpmst-mailster") . "\n\n";
		$body .= __("Working settings", "wpmst-mailster") . ":\n";
		$body .= '----------------------' . "\n";
		if($use_cms_mailer !== '1')
		{
			$body .= __("Host/Server", "wpmst-mailster").': ' . $out_host . "\n";
			$body .= __("Port", "wpmst-mailster").': ' . $out_port . "\n";
			$body .= __("User/Login", "wpmst-mailster") . ': ' . $out_user . "\n";
			$body .= __("Password", "wpmst-mailster") . ': ****** ('.__("hidden", "wpmst-mailster"). ")\n";;
			$body .= __("Secure setting", "wpmst-mailster").': ' . ( $out_secure != '' ? strtoupper($out_secure) : __("None", "wpmst-mailster") ). "\n";
			$body .= __("Use secure authentication", "wpmst-mailster").': ' . ($out_sec_auth == '0' ? __("No", "wpmst-mailster") : __("Yes", "wpmst-mailster")) . "\n\n";
			$body .= __("If you use a public mail provider consider sharing your settings with others users", "wpmst-mailster")."\n";
			$body .= ' (-> ' . $mailSettingsLink . ')'. "\n";
		}else{
		 	$body = $body . 'CMS Mailer'. "\n";
		}
		
		$mail2send = MstFactory::getMailer(); //todo
        if(property_exists($mail2send, 'SMTPAutoTLS')){
            $mail2send->SMTPAutoTLS = false;
        }

		if($use_cms_mailer !== '1')
		{
			$mail2send->From  = $out_email;
			$mail2send->useSMTP($out_sec_auth == '0' ? false : true, $out_host, $out_user, $out_pw, $out_secure, $out_port); 
			$mail2send->setSender(array($out_email, $list_name));	
		}
	    $mail2send->setSubject(__("Mailster - Mail Sender Test Email", "wpmst-mailster"));
		$mail2send->setBody($body);
		$mail2send->AddAddress($admin_email, $list_name . ' ' . __("Admin", "wpmst-mailster"));
		$mail2send->addReplyTo($out_email);
		
		
		ob_start(); // activate output buffering
		$mail2send->SMTPDebug = 2;
		$sendOk = false;
		try {
			$sendOk = $mail2send->Send();
		} catch (Exception $e) {
            $exceptionErrorMsg = 'Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
            $log->error($exceptionErrorMsg);
        }


		$smtpDebugOutput = ob_get_contents();
		if (ob_get_level()) {
			ob_end_clean();  // deactivate output buffering
		}
		$smtpDebugOutput = 'SMTP Debug Output: ' . $smtpDebugOutput;
		if( true === $sendOk ) {
			$res = __("Sender settings OK", "wpmst-mailster") . ".\n";
			$res .= __("Mailster has sent a test mail to the mailing list's admin", "wpmst-mailster") . "\n";
			$res .= '(' . __("Email", "wpmst-mailster") . ': ' . $admin_email . ') ' . "\n\n";
			$res .= __("Go check it!", "wpmst-mailster");
		} else {
			$res = __("Errors while sending a test mail to mailing list admin", "wpmst-mailster") . ' ';
			$res .= '(' . $admin_email . ').' . "\n" . __("Connection NOT ok. Check your settings!", "wpmst-mailster") ."\n\n";
			$res .= __("Errors", "wpmst-mailster") . ":\n" . $smtpDebugOutput;
		}		
	}
	
	$resultArray['checkresult'] = $res;				
	$jsonStr  = $mstUtils->jsonEncode($resultArray);
	echo "[" . $jsonStr . "]";	
	wp_die(); // this is required to terminate immediately and return a proper response
}


add_action( 'wp_ajax_getInboxStatus', 'getInboxStatus_callback' );
function getInboxStatus_callback() {

	$mstUtils 	= MstFactory::getUtils();
	$listUtils	= MstFactory::getMailingListUtils();
	$mailbox 	= MstFactory::getMailingListMailbox();
	$resultArray = array();
	$res = __( 'Get Inbox status called', 'wpmst-mailster' );
	$ajaxParams = sanitize_text_field($_POST['mtrAjaxData']);
	$ajaxParams = $mstUtils->jsonDecode(stripslashes($ajaxParams));
	$res = true;
	
	$mailboxStatus = __(" NOT ok. Check your settings!", "wpmst-mailster");
	$listId = $ajaxParams->{'listId'};
	$mList = $listUtils->getMailingList($listId);
	if($listUtils->lockMailingList($mList->id)){
		if($mailbox->open($mList)){
			$mailboxStatus = $mailbox->getInboxStatus();
			$mailbox->close();
		}else{
			$res = false;
		}
		$listUtils->unlockMailingList($mList->id);
	}else{
		$res = false;
	}
		
	$res = ($res ? 'true' : 'false');
	
	$mailboxStatusText =__( 'Inbox status', 'wpmst-mailster' ) . print_r($mailboxStatus, true);
	$resultArray['res'] = $res;
	$resultArray['mailboxStatus'] = $mailboxStatus;
	$resultArray['mailboxStatusText'] = $mailboxStatusText;
	$jsonStr  = $mstUtils->jsonEncode($resultArray);
	echo "[" . $jsonStr . "]";
	wp_die(); 
}

add_action( 'wp_ajax_removeFirstMailFromMailbox', 'removeFirstMailFromMailbox_callback' );
function removeFirstMailFromMailbox_callback(){
	/*if(!$this->canDo->get('core.edit')){
		return MstFactory::getAuthorization()->noPermissionForAction('lists->removeFirstMailFromMailbox (core.edit)');
	}*/
	$mstUtils 	= MstFactory::getUtils();
	$listUtils	= MstFactory::getMailingListUtils();
	$mailbox 	= MstFactory::getMailingListMailbox();
	$resultArray = array();
	$res = __( 'Delete first email in inbox called', 'wpmst-mailster' );	
	$ajaxParams = sanitize_text_field($_POST['mtrAjaxData']);
	$ajaxParams = $mstUtils->jsonDecode(stripslashes($ajaxParams));
	$res = true;
	
	$listId = $ajaxParams->{'listId'};
	$mList = $listUtils->getMailingList($listId);
	if($listUtils->lockMailingList($mList->id)){
		if($mailbox->open($mList)){
			$mailbox->removeFirstMailFromMailbox();
			$mailbox->close();
		}else{
			$res = false;
		}
		$listUtils->unlockMailingList($mList->id);
	}else{
		$res = false;
	}
		
	$res = ($res ? 'true' : 'false');
	
	$resultArray['res'] = $res;			
	$jsonStr  = $mstUtils->jsonEncode($resultArray);
	echo "[" . $jsonStr . "]";	
	wp_die();
}

add_action( 'wp_ajax_removeAllMailsFromMailbox', 'removeAllMailsFromMailbox_callback' );
function removeAllMailsFromMailbox_callback(){
	$mstUtils 	= MstFactory::getUtils();
	$listUtils	= MstFactory::getMailingListUtils();
	$mailbox 	= MstFactory::getMailingListMailbox();
	$resultArray = array();
	$res = __( 'Remove all emails in the send queue called', 'wpmst-mailster' );	
	$ajaxParams = sanitize_text_field($_POST['mtrAjaxData']);
	$ajaxParams = $mstUtils->jsonDecode(stripslashes($ajaxParams));
	$res = true;
	
	$listId = $ajaxParams->{'listId'};
	$mList = $listUtils->getMailingList($listId);
	if($listUtils->lockMailingList($mList->id)){
		if($mailbox->open($mList)){
			$mailbox->removeAllMailsFromMailbox();
			$mailbox->close();
		}else{
			$res = false;
		}
		$listUtils->unlockMailingList($mList->id);
	}else{
		$res = false;
	}
		
	$res = ($res ? 'true' : 'false');
	
	$resultArray['res'] = $res;			
	$jsonStr  = $mstUtils->jsonEncode($resultArray);
	echo "[" . $jsonStr . "]";	
	wp_die();
}

add_action( 'wp_ajax_removeAllMailsInSendQueue', 'removeAllMailsInSendQueue_callback' );
function removeAllMailsInSendQueue_callback(){
	$mstUtils 	= MstFactory::getUtils();
	$mstQueue = MstFactory::getMailQueue();
	$resultArray = array();
	$res = __( 'Remove all emails in the send queue called', 'wpmst-mailster' );	
	$ajaxParams = sanitize_text_field($_POST['mtrAjaxData']);
	$ajaxParams = $mstUtils->jsonDecode(stripslashes($ajaxParams));
	$res = true;
	
	$listId = $ajaxParams->{'listId'};
	$res = $mstQueue->removeAllMailsFromListFromQueue($listId);
		
	$res = ($res ? 'true' : 'false');
	
	$resultArray['res'] = $res;			
	$jsonStr  = $mstUtils->jsonEncode($resultArray);
	echo "[" . $jsonStr . "]";	
	wp_die();
}

add_action( 'wp_ajax_unlockMailingList', 'unlockMailingList_callback' );
function unlockMailingList(){
	$mstUtils 	= MstFactory::getUtils();
	$listUtils 	= MstFactory::getMailingListUtils();
	$resultArray = array();
	$res = __( 'Unlock mailing list called', 'wpmst-mailster' );	
	$ajaxParams = sanitize_text_field($_POST['mtrAjaxData']);
	$ajaxParams = $mstUtils->jsonDecode(stripslashes($ajaxParams));
	$res = true;
	
	$listId = $ajaxParams->{'listId'};
	$res = $listUtils->unlockMailingList($listId);
	$res = ($res ? 'true' : 'false');
	
	$resultArray['res'] = $res;			
	$jsonStr  = $mstUtils->jsonEncode($resultArray);
	echo "[" . $jsonStr . "]";	
	wp_die();
}