<?php
if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
	die('These are not the droids you are looking for.');
}

include_once $this->WPMST_PLUGIN_DIR."/models/MailsterModelGroup.php";
include_once $this->WPMST_PLUGIN_DIR."/models/MailsterModelUser.php";

$sid   = (isset($_GET['sid']) && $_GET['sid']!=''?intval($_GET['sid']):'');
if ( ! $sid ) {
	if ( isset( $_POST['sid'] ) ) {
		$sid = intval($_POST['sid']);
	}
}

$title  = __("New Group", 'wpmst-mailster');
$button = __('Add Group', 'wpmst-mailster');
$action = 'add';
$message = "";
$options = array();

$Group = null;
$User = new MailsterModelUser();

if($sid) {
	$Group = new MailsterModelGroup($sid);
} else {
	$Group = new MailsterModelGroup();
}

if ($_GET['jack'] == "hello") {
  # Convention is that the 2nd user is a hotel owner who is also a core user, but doesn't have to be
  $email = 'jwarner_ags@yahoocom2';
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "$email is not valid";
  }
  # TODO - get emails from external input and make sure they are valid syntactically
  $emails = array("jackwarner@me.com", "jwarner_ags@yahoo.com" );
  $group_id = $Group->getRelationshipGroup($emails);
  if (!empty($group_id)) {
    echo "Group id is $group_id - nothing to be done";
  }
  else {
    echo "we have work to do!";
    $group_options[ 'name' ] = implode('+', $emails);
    $Group->saveData( $group_options );
    $sid = $Group->getId();
    echo "Yay, group is $sid";
    # Now let's check on users
    $UserOne = new MailsterModelUser();
    $firstUser = $UserOne->isDuplicateEntry($emails[0]);
    $firstUserId = -1;
    $firstUserCore = 0;
    $secondUserId = -1;
    $secondUserCore = 0;
    $secondUserNiceName = '';
    if (empty($firstUser)) {
      echo "need to create user $emails[0]";
      $user_options['name'] = $emails[0];
      $user_options['email'] = $emails[0];
      $user_options['notes'] = 'created automatically from booking form';
      $firstUser = $UserOne->saveData($user_options);
      $firstUserId = $UserOne->getId();
    }
    else {
      echo "first user exists:";
      echo "<pre>";
      print_r($firstUser);
      echo "id is $firstUser->id";
      $firstUserId = $firstUser->id;
      $firstUserCore = $firstUser->is_core_user;
      echo "</pre>";
    }
    $UserTwo = new MailsterModelUser();
    $secondUser = $UserTwo->isDuplicateEntry($emails[1]);
    if (empty($secondUser)) {
      echo "need to create user $emails[1]";
      $user_options['name'] = $emails[1];
      $user_options['email'] = $emails[1];
      $user_options['notes'] = 'created automatically from booking form';
      $secondUser = $UserTwo->saveData($user_options);
      $secondUserId = $UserTwo->getId();
      $secondUserNiceName = $secondUserId;
    }
    else {
      echo "second user exists:";
      echo "<pre>";
      print_r($secondUser);

      $secondUserId = $secondUser->id;
      $secondUserNiceName = $secondUserId;
      $secondUserCore = $secondUser->is_core_user;
      if ($secondUserCore) {
        $secondUserId = $secondUser->ID;
        $secondUserNiceName = $secondUser->user_login;
      }

      echo "id is $secondUserId";
      echo "</pre>";

    }
    //exit();
    $Group->emtpyUsers();
    // both users exist, add them to the group!
    $res = $Group->addUserById( $firstUserId, $firstUserCore );
    $res = $Group->addUserById( $secondUserId, $secondUserCore );

    echo "list creation";
    $options['name'] = "Hand-Picked Riviera ";
    $options['list_mail'] = 'wpmailster@gmail.com';
    $options['admin_mail'] = 'wpmailster@gmail.com';
    $options['active'] = 1;
    $options['front_archive_access'] = 0;
    $options['server_inb_id'] = 3;
    $options['mail_in_user'] = 'wpmailster@gmail.com';
    $options['mail_in_pw'] = '4XtxJ39ZqCx7DkC97iyq';
    $options['use_cms_mailer'] = 0;
    $options['server_out_id'] = 4;
    $options['mail_out_user'] = 'wpmailster@gmail.com';
    $options['mail_out_pw'] = '4XtxJ39ZqCx7DkC97iyq';
    $options['subject_prefix'] = "";
    $options['custom_header_plain'] = "{name} ({date}):";
    $options['custom_footer_plain'] = "";
    $options['clean_up_subject'] = 1;
    $options['mail_format_conv'] = 0;
    $options['disable_mail_footer'] = 0;
    $options['mail_format_altbody'] = 1;
    $options['custom_header_html'] = "
{name} ({date}):
";
    $options['custom_footer_html'] = "";
    $options['copy_to_sender'] = 0;
    $options['mail_size_limit'] = 0;
    $options['filter_mails'] = 0;
    $options['allow_bulk_precedence'] = 0;
    $options['sending_public'] = 0;
    $options['sending_recipients'] = 0;
    $options['sending_admin'] = 1;
    $options['sending_group'] = 1;
    $options['sending_group_id'] = $Group->getId();
    $options['addressing_mode'] = 0;
    $options['bcc_count'] = 10;
    $options['incl_orig_headers'] = 0;
    $options['mail_from_mode'] = 2;
    $options['name_from_mode'] = 0;
    $options['reply_to_sender'] = 0;
    $options['bounce_mail'] = '';
    $options['bounce_mode'] = 0;
    $options['max_send_attempts'] = 5;
    $options['save_send_reports'] = 30;
    $options['allow_subscribe'] = 0;
    $options['public_registration'] = 0;
    $options['subscribe_mode'] = 0;
    $options['welcome_msg'] = 0;
    $options['welcome_msg_admin'] = 0;
    $options['allow_unsubscribe'] = 1;
    $options['unsubscribe_mode'] = 0;
    $options['goodbye_msg'] = 0;
    $options['goodbye_msg_admin'] = 0;
    $options['allow_digests'] = 0;
    $options['archive_mode'] = 0;
    $options['notify_not_fwd_sender'] = 1;

    $List = new MailsterModelList();
    $saved = $List->saveData($options);
    if ( $saved == null ) { //unsuccessful save
      #$message = $this->wpmst_view_message("updated", __("Something went wrong, data not saved. Please try again", 'wpmst-mailster'));
      echo "Failed!";
    } else {
      #$message = $this->wpmst_view_message("updated", __("Mailing list saved successfully.", 'wpmst-mailster'));
      $lid = $saved;
      echo "Saved $saved";

      $res = $List->addUserById( intval($secondUserId), intval($secondUserCore) );
      $res = $List->addUserById( intval($firstUserId), intval($firstUserCore) );
//      if ( $res && !$newUser->isRecip && $mList && $mList->welcome_msg > 0 && $mList->welcome_msg_admin > 0 ) {
//        $subscrUtils = MstFactory::getSubscribeUtils();
//        $subscrUtils->sendWelcomeOrGoodbyeSubscriberMsg( $userRow->name, $userRow->email, intval( $lid ), MstConsts::SUB_TYPE_SUBSCRIBE );
//        $log->debug( 'sent welcome message to user_id: ' . $userRow->name . ', ' . $userRow->email );
//      }

    }



  echo "<pre>";
  print_r($options);
  echo "</pre>";

  }
  exit();
}



if(isset($_POST['group_action']) && null !== $_POST['group_action'] ) { //if form is submitted
	if ( isset( $_POST[ 'add_group' ] ) ) {
		$addGroup = sanitize_text_field($_POST['add_group']);
		if( intval($_POST[ 'sid' ]) ) {
			$group_options[ 'id' ] = intval($_POST[ 'sid' ]);
		}
		$group_options[ 'name' ] = sanitize_text_field($_POST[ 'group_name' ]);

		$Group->saveData( $group_options, $addGroup );
		$sid = $Group->getId();

		//if we are editing an existing group, we can add members
		if ( $addGroup == 'edit' ) {
			//remove all members and then insert all new ones
			$Group->emtpyUsers();
			//insert the selected members in the group
			if( $_POST['searchable'] ) {
				foreach ( $_POST['searchable'] as $k => $v ) {
					if( isset( $v ) && $v != '' ) {
						$val = explode( '-', $v );
						$user_id = intval($val[0]);
						$is_core_user = intval($val[1]);
						$res = $Group->addUserById( $user_id, $is_core_user );
					}
				}
			}
		}
	}
}
$values = null;
if($sid) {
	$title = __("Edit Group", 'wpmst-mailster');
	$button = __('Update Group', 'wpmst-mailster');
	$action = 'edit';
}

if( $sid ) {
	//fetch existing group members
	$grp_list2 = $Group->getAllUsers();
	//store the existing members in an array
	$selected2 = array();
	foreach($grp_list2 as $gk2=>$gv2){
		$selected2[] = $gv2->user_id.'-'.$gv2->is_core_user;
	}

	//get all users (wp and mailster)
	$tm_list  = $User->getAllUsers();
	//get all the group's users
	$grp_list = $Group->getAllUsers();
	$selected = array();
	foreach($grp_list as $gv){
		$selected[] = $gv->user_id.'-'.$gv->is_core_user;
	}
}

$options = $Group->getFormData();
?>

<div class="mst_container">
	<div class="wrap">
		<h2><?php echo $title; ?></h2>
		<?php echo (isset($message) && $message!=''?$message:'');?>
		<form action="" method="post">
			<?php wp_nonce_field( 'add-group_'.$sid ); ?>
			<table class="form-table">
				<tbody>
					<?php
					$this->mst_display_hidden_field( "sid", $sid );
					$this->mst_display_hidden_field( "add_group", $action );
					$this->mst_display_input_field( __("Group Name", 'wpmst-mailster'), 'group_name', $options->name, null, false, false, null );
					?>
				</tbody>
			</table>

			<?php if( $sid ) { ?>
			<div class="ms2side__header"><?php __("Choose users to add to group",'wpmst-mailster'); ?></div>
			<select name="searchable[]" id='searchable' multiple='multiple' >
			<?php
			if( !empty( $tm_list ) ){
				$index = 1;
				foreach($tm_list as $single_tm) {
					$check = $single_tm->uid.'-'.$single_tm->is_core_user;
					$user_type = (isset($single_tm->is_core_user) && $single_tm->is_core_user==1?'WP User':'Mailster');
					if(in_array($check,$selected)){
						?>
				<option value="<?php echo $single_tm->uid.'-'.$single_tm->is_core_user; ?>" selected ><?php echo $single_tm->Name . '&lt;'.$single_tm->Email.'&gt; ('. $user_type . ')'; ?></option>
					<?php } else { ?>
				<option value="<?php echo $single_tm->uid.'-'.$single_tm->is_core_user; ?>"><?php echo $single_tm->Name . '&lt;'.$single_tm->Email.'&gt; ('. $user_type . ')'; ?></option>
					<?php }
				} //endforeach
			} ?>
			</select>
			<?php } ?>
			<input type="submit" class="button-primary" name="group_action" value="<?php echo $button; ?>">
		</form>
	</div>
</div>
