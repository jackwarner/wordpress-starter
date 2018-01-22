<?php
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
		$log         = MstFactory::getLogger();
		$user = wp_get_current_user();
		$subscrUtils = MstFactory::getSubscribeUtils();

		$listUtils = MstFactory::getMailingListUtils();
		$mList     = $listUtils->getMailingList( $listId );
		if ( $mList ) {
			if ( $mList->allow_unsubscribe ) {
				if ( $mList->unsubscribe_mode != MstConsts::UNSUBSCRIBE_MODE_DOUBLE_OPT_IN ) {
					$log->debug( 'Double Opt-in unsubscribe mode not activated (frontend)' );
					$success = $subscrUtils->unsubscribeUserId( $user->ID, true, $listId );
					$tmpUser = $subscrUtils->getUserByEmail( $user->user_email );
					$subscrUtils->sendWelcomeOrGoodbyeSubscriberMsg( $tmpUser['name'], $user->user_email, $listId, MstConsts::SUB_TYPE_UNSUBSCRIBE );
					if ( $success ) {
						$message = __("Unsubscription Successful", "wpmst_mailster");
                        // ####### TRIGGER NEW EVENT #######
                        $mstEvents = MstFactory::getEvents();
                        $mstEvents->userUnsubscribedOnWebsite($user->user_email, $listId);
                        // #################################
					} else {
						$message = __("Unsubscription Failed", "wpmst_mailster");
					}
				} else {
					$log->debug( 'Double Opt-in unsubscribe mode (frontend)' );
					$subscrUtils->unsubscribeUserWithDoubleOptIn( $user->user_email, $listId );
					$message = __("Unsubscription Successful. Please confirm by clicking the link in the confirmation email that was sent to you.", "wpmst_mailster");
				}
			}
		}
		echo $message;
	}