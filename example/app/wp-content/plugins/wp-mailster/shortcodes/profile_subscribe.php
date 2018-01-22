<?php
	$log = MstFactory::getLogger();
	$user = wp_get_current_user();
	$subscrUtils = MstFactory::getSubscribeUtils();

	if(isset($_GET['listID'])) {
		$listId = $_GET['listID'];
	} else {
		$listId = 0;
	}
	if( isset($_GET['bl']) ) {
		$backLink = base64_decode($_GET['bl']);
	} else {
		$backLink = null;
	}
	$message = "";

	if( !is_user_logged_in() ){
		_e("You need to login to access this section", "wpmst-mailster");
	} else {
		$listUtils = MstFactory::getMailingListUtils();
		$mList     = $listUtils->getMailingList( $listId );
        $userModel = MstFactory::getUserModel();
        $userObj   = $userModel->getUserData($user->ID, true);
        $userName = (property_exists($userObj, 'name') && $userObj->name && !empty($userObj->name) && (strlen(trim($userObj->name))>0)) ? $userObj->name : $user->display_name;
		if ( $mList ) {
			if ( $mList->allow_subscribe ) {
				if ( $mList->subscribe_mode != MstConsts::SUBSCRIBE_MODE_DOUBLE_OPT_IN ) {
					$log->debug( 'Double Opt-in subscribe mode not activated (frontend)' );
					$success = $subscrUtils->subscribeUserId( $user->ID, true, $listId );
					$subscrUtils->sendWelcomeOrGoodbyeSubscriberMsg( $userName, $user->user_email, $listId, MstConsts::SUB_TYPE_SUBSCRIBE );
					if ( $success ) {
						$message = __("Subscription Successful", "wpmst_mailster");
                        // ####### TRIGGER NEW EVENT #######
                        $mstEvents = MstFactory::getEvents();
                        $mstEvents->userSubscribedOnWebsite( $userName, $user->user_email, $listId);
                        // #################################
					} else {
						$message = __("Subscription Failed", "wpmst_mailster");
					}
				} else {
					$log->debug( 'Double Opt-in subscribe mode (frontend)' );
					$subscrUtils->subscribeUserWithDoubleOptIn( $userName, $user->user_login, $listId );
					$message = __("Subscription Successful. Please confirm by clicking the link in the confirmation email that was sent to you.", "wpmst_mailster");
				}
			}
		}
		echo $message;
	}