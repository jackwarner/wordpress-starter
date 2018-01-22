<?php
	if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
		die( 'These are not the droids you are looking for.' );
	}
function mailster_install() {
	$log = MstFactory::getLogger();
	$dbUtils = MstFactory::getDBUtils();

	add_option('allow_send', true);
	add_option('version_license', 'free');
	add_option('current_version', 'free');

	$option_name = "mailster_db_version";
	if( !get_option( $option_name ) ) {
		add_option( $option_name, "1.0" );
		$log->info('STARTING NEW INSTALLATIONs', MstConsts::LOGENTRY_INSTALLER);
	} else {
		$dbUtils->checkAndFixDBCollations(true);
		$log->info('TABLES SEEM TO BE ALREADY INSTALLED, UPDATING FILES ONLY', MstConsts::LOGENTRY_INSTALLER);
		//mailster already exists (perhaps a different version)
	}

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();

	$log = MstFactory::getLogger();

	$table_name = $wpdb->prefix . "mailster_lists";
	$sql =
"CREATE TABLE IF NOT EXISTS $table_name (
  id int(11) NOT NULL AUTO_INCREMENT,
  asset_id INT NOT NULL DEFAULT 0,
  name varchar(45) DEFAULT NULL,
  admin_mail varchar(255) DEFAULT NULL,
  published tinyint(1) DEFAULT NULL,
  active tinyint(1) DEFAULT NULL,
  public_registration tinyint(1) DEFAULT NULL,
  sending_public tinyint(1) DEFAULT NULL,
  sending_recipients tinyint(1) DEFAULT NULL,
  sending_admin tinyint(1) DEFAULT NULL,
  sending_group tinyint(1) DEFAULT NULL,
  sending_group_id int(11) DEFAULT NULL,
  disable_mail_footer tinyint(1) DEFAULT NULL,
  allow_subscribe tinyint(1) DEFAULT '1',
  allow_unsubscribe tinyint(1) DEFAULT '1',
  reply_to_sender tinyint(1) DEFAULT NULL,
  list_mail varchar(255) DEFAULT NULL,
  subject_prefix varchar(45) DEFAULT NULL,
  use_cms_mailer tinyint(1) DEFAULT NULL,
  copy_to_sender tinyint(1) DEFAULT NULL,
  mail_in_user varchar(255) DEFAULT NULL,
  mail_in_pw varchar(45) DEFAULT NULL,
  mail_out_user varchar(255) DEFAULT NULL,
  mail_out_pw varchar(45) DEFAULT NULL,
  server_inb_id int(11) DEFAULT NULL,
  server_out_id int(11) DEFAULT NULL,
  custom_header_plain text,
  custom_footer_plain text,
  custom_header_html text,
  custom_footer_html text,
  mail_format_conv int(11) DEFAULT '1',
  mail_format_altbody tinyint(1) DEFAULT '1',
  alibi_to_mail varchar(255) DEFAULT NULL,
  addressing_mode tinyint(1) DEFAULT NULL,
  mail_from_mode tinyint(1) DEFAULT '0',
  name_from_mode tinyint(1) DEFAULT '0',
  archive_mode int(11) DEFAULT '0',
  archive2article tinyint(1) DEFAULT '0',
  archive2article_author int(11) DEFAULT '0',
  archive2article_cat int(11) DEFAULT '0',
  archive2article_state tinyint(3) DEFAULT '1',
  archive_offline int(11) DEFAULT '0',
  bounce_mode int(11) DEFAULT NULL,
  bounce_mail varchar(255) DEFAULT NULL,
  bcc_count int(11) DEFAULT NULL,
  incl_orig_headers tinyint(1) DEFAULT '0',
  max_send_attempts int(11) DEFAULT NULL,
  filter_mails tinyint(1) DEFAULT NULL,
  allow_bulk_precedence tinyint(1) DEFAULT '0',
  clean_up_subject tinyint(1) DEFAULT NULL,
  lock_id int(11) DEFAULT NULL,
  is_locked tinyint(1) DEFAULT NULL,
  last_lock timestamp NULL DEFAULT NULL,
  last_check timestamp NULL DEFAULT NULL,
  last_mail_retrieved timestamp NULL DEFAULT NULL,
  last_mail_sent timestamp NULL DEFAULT NULL,
  cstate int(11) DEFAULT '0',
  mail_size_limit int(11) DEFAULT '0',
  notify_not_fwd_sender tinyint(1) DEFAULT '1',
  save_send_reports int(11) DEFAULT '7',
  subscribe_mode int(11) DEFAULT '1',
  unsubscribe_mode int(11) DEFAULT '1',
  welcome_msg int(11) DEFAULT '1',
  welcome_msg_admin int(11) DEFAULT '0',
  goodbye_msg int(11) DEFAULT '1',
  goodbye_msg_admin int(11) DEFAULT '0',
  allow_digests tinyint(1) DEFAULT '0',
  front_archive_access tinyint(1) DEFAULT '0',
  mail_content text,
  PRIMARY KEY  (id)
) $charset_collate;";
	dbDelta( $sql );

	$table_name = $wpdb->prefix . "mailster_users";
	$sql =
"CREATE TABLE IF NOT EXISTS $table_name (
  id int(11) NOT NULL AUTO_INCREMENT,
  name varchar(255) NOT NULL,
  email varchar(255) NOT NULL,
  notes varchar(255) NOT NULL,
  PRIMARY KEY  (id)
) $charset_collate;";
	dbDelta( $sql );

	$table_name = $wpdb->prefix . "mailster_list_members";
	$sql =
"CREATE TABLE IF NOT EXISTS $table_name (
  list_id int(11) NOT NULL,
  user_id int(11) NOT NULL,
  is_core_user tinyint(1) NOT NULL,
  receive_mails TINYINT(1) NULL ,
  send_mails TINYINT(1) NULL
) $charset_collate;";
	dbDelta( $sql );

	$table_name = $wpdb->prefix . "mailster_servers";
	$sql =
"CREATE TABLE IF NOT EXISTS $table_name (
  id int(11) NOT NULL AUTO_INCREMENT,
  name varchar(255) NOT NULL,
  published tinyint(1) DEFAULT NULL,
  provider_type INT NOT NULL DEFAULT 0,
  user_edited TINYINT(1) NOT NULL DEFAULT 1,
  server_type int(11) NOT NULL,
  server_host varchar(255) DEFAULT NULL,
  server_port int(11) DEFAULT NULL,
  secure_protocol varchar(255) DEFAULT NULL,
  secure_authentication tinyint(1) DEFAULT NULL,
  protocol varchar(45) DEFAULT NULL,
  connection_parameter varchar(255) DEFAULT NULL,
  api_key1 varchar(255) DEFAULT NULL,
  api_key2 varchar(255) DEFAULT NULL,
  api_endpoint varchar(255) DEFAULT NULL,
  PRIMARY KEY  (id)
) $charset_collate;";
	dbDelta( $sql );

	$table_name = $wpdb->prefix . "mailster_list_stats";
	$sql =
"CREATE TABLE IF NOT EXISTS $table_name (
  id int(11) NOT NULL AUTO_INCREMENT,
  list_id varchar(45) NOT NULL,
  stat_date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  stat_hour tinyint(2) NOT NULL,
  stat_minute tinyint(2) NOT NULL,
  send_mails int(11) NOT NULL DEFAULT '0',
  send_recipients int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY  (id)
) $charset_collate;";
	dbDelta( $sql );

	$table_name = $wpdb->prefix . "mailster_mails";
	$sql =
"CREATE TABLE IF NOT EXISTS $table_name (
  id int(11) NOT NULL AUTO_INCREMENT,
  list_id int(11) NOT NULL,
  thread_id int(11) NOT NULL,
  hashkey varchar(45) DEFAULT NULL,
  message_id varchar(255) DEFAULT NULL,
  in_reply_to varchar(255) DEFAULT NULL,
  references_to varchar(255) DEFAULT NULL,
  receive_timestamp timestamp NULL DEFAULT NULL,
  from_name varchar(255) DEFAULT NULL,
  from_email varchar(255) DEFAULT NULL,
  sender_user_id int(11) NOT NULL DEFAULT '0',
  sender_is_core_user tinyint(1) NOT NULL DEFAULT '0',
  orig_to_recips text,
  orig_cc_recips text,
  subject varchar(255) DEFAULT NULL,
  body mediumtext,
  html mediumtext,
  has_attachments tinyint(1) DEFAULT NULL,
  fwd_errors tinyint(1) DEFAULT NULL,
  fwd_completed tinyint(1) DEFAULT NULL,
  fwd_completed_timestamp timestamp NULL DEFAULT NULL,
  blocked_mail tinyint(1) DEFAULT NULL,
  bounced_mail tinyint(1) DEFAULT NULL,
  no_content tinyint(1) DEFAULT '0',
  has_send_report tinyint(1) DEFAULT '0',
  size_in_bytes int(11) DEFAULT '-1',
  PRIMARY KEY  (id)
) $charset_collate;";
	dbDelta( $sql );

	$table_name = $wpdb->prefix . "mailster_oa_mails";
	$sql =
"CREATE TABLE IF NOT EXISTS $table_name (
  id int(11) NOT NULL AUTO_INCREMENT,
  list_id int(11) NOT NULL,
  thread_id int(11) NOT NULL,
  hashkey varchar(45) DEFAULT NULL,
  message_id varchar(255) DEFAULT NULL,
  in_reply_to varchar(255) DEFAULT NULL,
  references_to varchar(255) DEFAULT NULL,
  receive_timestamp timestamp NULL DEFAULT NULL,
  from_name varchar(255) DEFAULT NULL,
  from_email varchar(255) DEFAULT NULL,
  sender_user_id int(11) NOT NULL DEFAULT '0',
  sender_is_core_user tinyint(1) NOT NULL DEFAULT '0',
  orig_to_recips text,
  orig_cc_recips text,
  subject varchar(255) DEFAULT NULL,
  body mediumtext,
  html mediumtext,
  has_attachments tinyint(1) DEFAULT NULL,
  fwd_errors tinyint(1) DEFAULT NULL,
  fwd_completed tinyint(1) DEFAULT NULL,
  fwd_completed_timestamp timestamp NULL DEFAULT NULL,
  blocked_mail tinyint(1) DEFAULT NULL,
  bounced_mail tinyint(1) DEFAULT NULL,
  no_content tinyint(1) DEFAULT '0',
  has_send_report tinyint(1) DEFAULT '0',
  size_in_bytes int(11) DEFAULT '-1',
  PRIMARY KEY  (id)
) $charset_collate;";
	dbDelta( $sql );

	$table_name = $wpdb->prefix . "mailster_groups";
	$sql =
"CREATE TABLE IF NOT EXISTS $table_name (
  id int(11) NOT NULL AUTO_INCREMENT,
  name varchar(255) NOT NULL,
  is_core_group tinyint(1) NOT NULL,
  core_group_id int(200) NOT NULL,
  PRIMARY KEY  (id)
) $charset_collate;";
	dbDelta( $sql );

	$table_name = $wpdb->prefix . "mailster_list_groups";
	$sql =
"CREATE TABLE IF NOT EXISTS $table_name (
  list_id int(11) NOT NULL,
  group_id int(11) NOT NULL,
  receive_mails TINYINT(1) NULL ,
  send_mails TINYINT(1) NULL
) $charset_collate;";
	dbDelta( $sql );

	$table_name = $wpdb->prefix . "mailster_group_users";
	$sql =
"CREATE TABLE IF NOT EXISTS $table_name (
  group_id int(11) NOT NULL,
  user_id int(200) NOT NULL,
  is_core_user tinyint(1) NOT NULL
) $charset_collate;";
	dbDelta( $sql );

	$table_name = $wpdb->prefix . "mailster_queued_mails";
	$sql =
"CREATE TABLE IF NOT EXISTS $table_name (
  mail_id int(11) NOT NULL,
  name varchar(255) DEFAULT NULL,
  email varchar(255) NOT NULL,
  error_count int(11) DEFAULT NULL,
  lock_id int(11) DEFAULT NULL,
  is_locked tinyint(1) DEFAULT NULL,
  last_lock timestamp NULL DEFAULT NULL
) $charset_collate;";
	dbDelta( $sql );

	$table_name = $wpdb->prefix . "mailster_threads";
	$sql =
"CREATE TABLE IF NOT EXISTS $table_name (
  id int(11) NOT NULL AUTO_INCREMENT,
  first_mail_id int(11) NOT NULL,
  last_mail_id int(11) NOT NULL,
  ref_message_id varchar(45) DEFAULT NULL,
  PRIMARY KEY  (id)
) $charset_collate;";
	dbDelta( $sql );

	$table_name = $wpdb->prefix . "mailster_attachments";
	$sql =
"CREATE TABLE IF NOT EXISTS $table_name (
  id int(11) NOT NULL AUTO_INCREMENT,
  mail_id int(11) NOT NULL,
  filename varchar(255) NOT NULL,
  filepath varchar(255) NOT NULL,
  content_id varchar(255) DEFAULT NULL,
  disposition tinyint(1) NOT NULL,
  type int(11) NOT NULL,
  subtype varchar(45) DEFAULT NULL,
  params varchar(255) DEFAULT NULL,
  PRIMARY KEY  (id)
) $charset_collate;";
	dbDelta( $sql );

	$table_name = $wpdb->prefix . "mailster_oa_attachments";
	$sql =
"CREATE TABLE IF NOT EXISTS $table_name (
  id int(11) NOT NULL AUTO_INCREMENT,
  mail_id int(11) NOT NULL,
  filename varchar(255) NOT NULL,
  filepath varchar(255) NOT NULL,
  content_id varchar(255) DEFAULT NULL,
  disposition tinyint(1) NOT NULL,
  type int(11) NOT NULL,
  subtype varchar(45) DEFAULT NULL,
  params varchar(255) DEFAULT NULL,
  PRIMARY KEY  (id)
) $charset_collate;";
	dbDelta( $sql );

	$table_name = $wpdb->prefix . "mailster_notifies";
	$sql =
"CREATE TABLE IF NOT EXISTS $table_name (
  id int(11) NOT NULL AUTO_INCREMENT,
  notify_type int(11) DEFAULT NULL,
  trigger_type int(11) DEFAULT NULL,
  target_type int(11) DEFAULT NULL,
  list_id int(11) DEFAULT NULL,
  group_id int(11) DEFAULT NULL,
  user_id int(11) DEFAULT NULL,
  PRIMARY KEY  (id)
) $charset_collate;";
	dbDelta( $sql );

	$table_name = $wpdb->prefix . "mailster_send_reports";
	$sql =
"CREATE TABLE IF NOT EXISTS $table_name (
  id int(11) NOT NULL AUTO_INCREMENT,
  mail_id int(11) NOT NULL,
  event_time timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  event_type int(11) NOT NULL DEFAULT '0',
  recips text,
  int_val1 int(11) NOT NULL,
  int_val2 int(11) NOT NULL,
  int_val3 int(11) NOT NULL,
  int_val4 int(11) NOT NULL,
  msg text,
  PRIMARY KEY  (id)
) $charset_collate;";
	dbDelta( $sql );

	$table_name = $wpdb->prefix . "mailster_log";
	$sql =
"CREATE TABLE IF NOT EXISTS $table_name (
  id int(11) NOT NULL AUTO_INCREMENT,
  level int(11) NOT NULL DEFAULT '0',
  type int(11) NOT NULL DEFAULT '0',
  log_time timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  msg text,
  PRIMARY KEY  (id)
) $charset_collate;";
	dbDelta( $sql );

	$table_name = $wpdb->prefix . "mailster_subscriptions";
	$sql =
"CREATE TABLE IF NOT EXISTS $table_name (
  id int(11) NOT NULL AUTO_INCREMENT,
  list_id int(11) DEFAULT NULL,
  user_id int(11) DEFAULT NULL,
  add2group int(11) DEFAULT '0',
  name varchar(255) DEFAULT NULL,
  email varchar(255) DEFAULT NULL,
  sub_type int(11) DEFAULT NULL,
  sub_date timestamp NULL DEFAULT NULL,
  hashkey varchar(45) DEFAULT NULL,
  digest_freq tinyint(1) DEFAULT '0',
  PRIMARY KEY  (id)
) $charset_collate;";
	dbDelta( $sql );

	$table_name = $wpdb->prefix . "mailster_digests";
	$sql =
"CREATE TABLE IF NOT EXISTS $table_name (
  id int(11) NOT NULL AUTO_INCREMENT,
  list_id int(11) NOT NULL,
  user_id int(11) NOT NULL,
  is_core_user tinyint(1) NOT NULL,
  digest_freq tinyint(1) NOT NULL,
  last_send_date timestamp NULL DEFAULT NULL,
  next_send_date timestamp NULL DEFAULT NULL,
  PRIMARY KEY  (id)
) $charset_collate;";
	dbDelta( $sql );

	$table_name = $wpdb->prefix . "mailster_digest_queue";
	$sql =
"CREATE TABLE IF NOT EXISTS $table_name (
  digest_id int(11) NOT NULL,
  mail_id int(11) NOT NULL,
  thread_id int(11) NOT NULL,
  digest_time timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) $charset_collate;";
	dbDelta( $sql );

	/* ----- General Updates ----- */
	$res = array();
	// Create Indexes that are not existent
	$res = array_merge($res, createIndexes() );
	//$ok = checkUpdateSuccess('General updates', $res);

	$dbUtils->checkAndFixDBCollations(true);

	$log->info('FINISHED INSTALL OPERATIONS', MstConsts::LOGENTRY_INSTALLER);

}

function mailster_install_data() {
	$log = MstFactory::getLogger();
	$res = array();
	// Create and update default email provider server settings
	$res = array_merge($res, createAndUpdateDefaultEmailProviderSettings() );
	$ok = checkUpdateSuccess('General updates', $res);
	$log->info('FINISHED POST INSTALL OPERATIONS', MstConsts::LOGENTRY_INSTALLER);
	/*global $wpdb;
	
	$settings = '';
	$table_name = $wpdb->prefix . "mailster_settings";
	$wpdb->insert($table_name, array('setting'=>'mailster_settings','value'=>$settings));*/
}


function createIndexes(){		
	global $wpdb;
	include_once( plugin_dir_path( __FILE__ ) . "../mailster/utils/DBUtils.php" );
	$dbUtils = new MstDBUtils();

	$digestsTbl 	= $wpdb->prefix.'mailster_digests';
	$digestQueueTbl = $wpdb->prefix.'mailster_digest_queue';
	$groupUsersTbl 	= $wpdb->prefix.'mailster_group_users';
	$listGroupsTbl 	= $wpdb->prefix.'mailster_list_groups';
	$listMembersTbl = $wpdb->prefix.'mailster_list_members';
	$listStatsTbl   = $wpdb->prefix.'mailster_list_stats';
	$logTbl			= $wpdb->prefix.'mailster_log';
	$mailsTbl 		= $wpdb->prefix.'mailster_mails';
	$queuedMailsTbl = $wpdb->prefix.'mailster_queued_mails';
	$sendReports 	= $wpdb->prefix.'mailster_send_reports';
	$serversTbl 	= $wpdb->prefix.'mailster_servers';
	$threadsTbl 	= $wpdb->prefix.'mailster_threads';
	$usersTbl 		= $wpdb->prefix.'mailster_users';

	$res = array();

	$cols = array();
	$cols[] = 'group_id';
	$index = 'group_id';
	$res[] 	= $dbUtils->createIndexIfNotExists($groupUsersTbl, $index, $cols);
	$cols = array();
	$cols[] = 'user_id';
	$cols[] = 'is_core_user';
	$index = 'user';
	$res[] 	= $dbUtils->createIndexIfNotExists($groupUsersTbl, $index, $cols);

	$cols = array();
	$cols[] = 'list_id';
	$index = 'list_id';
	$res[] 	= $dbUtils->createIndexIfNotExists($listGroupsTbl, $index, $cols);		
	$cols = array();
	$cols[] = 'list_id';
	$cols[] = 'group_id';
	$index = 'list_group';
	$res[] 	= $dbUtils->createIndexIfNotExists($listGroupsTbl, $index, $cols);

	$cols = array();
	$cols[] = 'list_id';
	$index = 'list_id';
	$res[] 	= $dbUtils->createIndexIfNotExists($listMembersTbl, $index, $cols);
	$cols = array();
	$cols[] = 'user_id';
	$cols[] = 'is_core_user';
	$index = 'user';
	$res[] 	= $dbUtils->createIndexIfNotExists($listMembersTbl, $index, $cols);

	$cols = array();
	$cols[] = 'list_id';
	$index = 'list_id';
	$res[] 	= $dbUtils->createIndexIfNotExists($mailsTbl, $index, $cols);
	$cols = array();
	$cols[] = 'blocked_mail';
	$index = 'blocked_mail';
	$res[] 	= $dbUtils->createIndexIfNotExists($mailsTbl, $index, $cols);
	$cols = array();
	$cols[] = 'bounced_mail';
	$index = 'bounced_mail';
	$res[] 	= $dbUtils->createIndexIfNotExists($mailsTbl, $index, $cols);
	$cols = array();
	$cols[] = 'blocked_mail';
	$cols[] = 'bounced_mail';
	$index = 'blocked_bounced';
	$res[] 	= $dbUtils->createIndexIfNotExists($mailsTbl, $index, $cols);
	$cols = array();
	$cols[] = 'fwd_errors';
	$index = 'fwd_errors';
	$res[] 	= $dbUtils->createIndexIfNotExists($mailsTbl, $index, $cols);
	$cols = array();
	$cols[] = 'fwd_completed';
	$index = 'fwd_completed';
	$res[] 	= $dbUtils->createIndexIfNotExists($mailsTbl, $index, $cols);
	$cols = array();
	$cols[] = 'thread_id';
	$index = 'thread_id';
	$res[] 	= $dbUtils->createIndexIfNotExists($mailsTbl, $index, $cols);

	$cols = array();
	$cols[] = 'mail_id';
	$index = 'mail_id';
	$res[] 	= $dbUtils->createIndexIfNotExists($queuedMailsTbl, $index, $cols);
	$cols = array();
	$cols[] = 'mail_id';
	$cols[] = 'is_locked';
	$index = 'mail_queued_locked';
	$res[] 	= $dbUtils->createIndexIfNotExists($queuedMailsTbl, $index, $cols);
	$cols = array();
	$cols[] = 'mail_id';
	$cols[] = 'email';
	$index = 'mail_queued';
	$res[] 	= $dbUtils->createIndexIfNotExists($queuedMailsTbl, $index, $cols);

	$cols = array();
	$cols[] = 'email';
	$index = 'email';
	$res[] 	= $dbUtils->createIndexIfNotExists($usersTbl, $index, $cols);
			
	$cols = array();
	$cols[] = 'ref_message_id';
	$index = 'ref_message_id';
	$res[] 	= $dbUtils->createIndexIfNotExists($threadsTbl, $index, $cols);

	$cols = array();
	$cols[] = 'type';
	$index = 'log_type';
	$res[] 	= $dbUtils->createIndexIfNotExists($logTbl, $index, $cols);		
	$cols = array();
	$cols[] = 'log_time';
	$index = 'log_time';
	$res[] 	= $dbUtils->createIndexIfNotExists($logTbl, $index, $cols);

	$cols = array();
	$cols[] = 'list_id';
	$index = 'list_id';
	$res[] 	= $dbUtils->createIndexIfNotExists($digestsTbl, $index, $cols);

	$cols = array();
	$cols[] = 'user_id';
	$cols[] = 'is_core_user';
	$index = 'digest_user';
	$res[] 	= $dbUtils->createIndexIfNotExists($digestsTbl, $index, $cols);

	$cols = array();
	$cols[] = 'digest_id';
	$index = 'digest_id';
	$res[] 	= $dbUtils->createIndexIfNotExists($digestQueueTbl, $index, $cols);

	$cols = array();
	$cols[] = 'thread_id';
	$index = 'thread_id';
	$res[] 	= $dbUtils->createIndexIfNotExists($digestQueueTbl, $index, $cols);

	$cols = array();
	$cols[] = 'mail_id';
	$index = 'mail_id';
	$res[] 	= $dbUtils->createIndexIfNotExists($sendReports, $index, $cols);

	$cols = array();
	$cols[] = 'list_id';
	$index = 'list_id';
	$res[] 	= $dbUtils->createIndexIfNotExists($listStatsTbl, $index, $cols);

	$cols = array();
	$cols[] = 'server_type';
	$index = 'server_type';
	$res[] 	= $dbUtils->createIndexIfNotExists($serversTbl, $index, $cols);

	$cols = array();
	$cols[] = 'provider_type';
	$index = 'provider_type';
	$res[] 	= $dbUtils->createIndexIfNotExists($serversTbl, $index, $cols);
				
	return $res;
}


function createAndUpdateDefaultEmailProviderSettings(){
	require_once( plugin_dir_path( __FILE__ ) .'../mailster/mail/ServerSettings.php');
	/*
	     const SERVER_PROVIDER_TYPE_USER_SPECIFIC = 0;
	     const SERVER_PROVIDER_AOL_INBOX = 1;
	     const SERVER_PROVIDER_AOL_SENDER = 2;
	     const SERVER_PROVIDER_GMAIL_INBOX = 3;
	     const SERVER_PROVIDER_GMAIL_SENDER = 4;
	     const SERVER_PROVIDER_ONE_COM_INBOX = 5;
	     const SERVER_PROVIDER_ONE_COM_SENDER = 6;
	     const SERVER_PROVIDER_OUTLOOK_INBOX = 7;
	     const SERVER_PROVIDER_OUTLOOK_SENDER = 8;
	     const SERVER_PROVIDER_YAHOO_INBOX = 9;
	     const SERVER_PROVIDER_YAHOO_SENDER = 10;
	 */
	$res = array();
	$res[] = createUpdateEmailProviderSetting(1, MstServerSettings::getAOLInbox());
	$res[] = createUpdateEmailProviderSetting(2, MstServerSettings::getAOLSMTP());
	$res[] = createUpdateEmailProviderSetting(3, MstServerSettings::getGoogleMailInbox());
	$res[] = createUpdateEmailProviderSetting(4, MstServerSettings::getGoogleMailSMTP());
	$res[] = createUpdateEmailProviderSetting(5, MstServerSettings::getOneComInbox());
	$res[] = createUpdateEmailProviderSetting(6, MstServerSettings::getOneComSMTP());
	$res[] = createUpdateEmailProviderSetting(7, MstServerSettings::getOutlookInbox());
	$res[] = createUpdateEmailProviderSetting(8, MstServerSettings::getOutlookSMTP());
	$res[] = createUpdateEmailProviderSetting(9, MstServerSettings::getYahooInbox());
	$res[] = createUpdateEmailProviderSetting(10, MstServerSettings::getYahooSMTP());
	return $res;
}

function createUpdateEmailProviderSetting($providerType, $defaultSettings){
	$log = MstFactory::getLogger();
	require_once( plugin_dir_path( __FILE__ ) .'../models/MailsterModelServer.php');
	$serverModel = new MailsterModelServer();
	//$serversModel = new MailsterModelServers();
	$serverSetting = $serverModel->getProviderTypeSettings($providerType);
	$needToStoreSettings = false;

	if(is_array($serverSetting)){
	    if(count($serverSetting)){
	        $serverSetting = $serverSetting[0];
	    }
	}

	if(!$serverSetting || ($serverSetting['id'] > 0 && $serverSetting['user_edited'] < 1)){
	    $needToStoreSettings = true;
	}

	if(!$serverSetting){
	    $log->info('Create default mail provider settings for provider type '.$providerType.' using default settings: '.print_r($defaultSettings, true), MstConsts::LOGENTRY_INSTALLER);
	    // need to create provider's email settings
	    $serverSetting = array();
	    $serverSetting['id'] = 0;
	    $serverSetting['published'] = 1;
	    $serverSetting['provider_type'] = $providerType;
	    $serverSetting['user_edited'] = 0;
	}

	if($serverSetting['id'] == 0 || ($serverSetting['id'] > 0 && $serverSetting['user_edited'] < 1)){
	    // was not updated, so we can update existing settings
	    $serverSetting['name'] = $defaultSettings->name;
	    $serverSetting['server_type'] = $defaultSettings->server_type;
	    $serverSetting['server_host'] = $defaultSettings->server_host;
	    $serverSetting['server_port'] = $defaultSettings->server_port;
	    $serverSetting['secure_protocol'] = $defaultSettings->secure_protocol;
	    $serverSetting['secure_authentication'] = $defaultSettings->secure_authentication;
	    $serverSetting['protocol'] = $defaultSettings->protocol;
	    $serverSetting['connection_parameter'] = $defaultSettings->connection_parameter;
	    $serverSetting['api_key1'] = $defaultSettings->api_key1;
	    $serverSetting['api_key2'] = $defaultSettings->api_key2;
	    $serverSetting['api_endpoint'] = $defaultSettings->api_endpoint;
	}

	if($needToStoreSettings){
	    $log->debug('Store/Update provider settings: '.$serverSetting['name'], MstConsts::LOGENTRY_INSTALLER);
	    global $wpdb;
	    $table = $serverModel->getTable();
	    $result = $wpdb->replace( $table, $serverSetting);
	    if(false === $result){
	        return -1;
	    }else{
	        return 1;
	    }
	}else{
	    $log->debug('Can/should not update settings: '.print_r($serverSetting, true), MstConsts::LOGENTRY_INSTALLER);
	    return 0;
	}
}

function checkUpdateSuccess($version, $res, $printErrorsOnly=false, $dbUpdate=true){
	$updError = false;
	$updAction = false;

    $msg = '';
    $msgcolor = '';
	for($i=0; $i < count($res); $i++){
		if($res[$i] < 0){
			$updError = true;
			$msgcolor = "#FFB0B0";
			$msg =  $version . ': error in step ' . ($i+1) . '';
			if($dbUpdate){
				$msg = sprintf( __( 'Update to version %s: error in step %d', "wpmst-mailster" ), $version, ($i+1) );
			}
		}
		if($res[$i] > 0){
			$msgcolor = "#B0FFB0";
			$msg =  sprintf( __('Update to version %s: step %d OK', "wpmst-mailster" ) , $version, ($i+1) );
			$updAction = true;
		}
		if(($res[$i] != 0 && !$printErrorsOnly) || ($res[$i]<0 && $printErrorsOnly)){
			addMsgRow($msg, $msgcolor);
		}	
	}
	
	if($dbUpdate){
		if($updError){
			$msgcolor = "#FFB0B0";
			$msg = sprintf( __('Database update to version %s FAILED, please copy errors or take a screenshot and contact support.', "wpmst-mailster" ) , $version );
		}else{
			if($updAction){
				$msgcolor = "#B0FFB0";
				$msg = sprintf( __('Database update to version %s successful!', "wpmst-mailster" ) , $version );
			}else{
				// no update action for this version done, 
				// probably an older version as the last installed version
			}
		}

		if($updError || $updAction){
			addMsgRow($msg, $msgcolor);
		}
	}
	
	return !$updError;
}

function addMsgRow($msg, $msgcolor, $onlyHighlightMsg=false, $emptyColCount = 1){
	/*if($onlyHighlightMsg){
		echo '<tr style="height:30px"><td>&nbsp;</td><td bgcolor="'. $msgcolor .'" >' . $msg . '</td><td colspan="'.$emptyColCount.'">&nbsp;</td>';
	}else{
		echo '<tr bgcolor="'. $msgcolor .'" style="height:30px"><td>&nbsp;</td><td>' . $msg . '</td><td colspan="'.$emptyColCount.'">&nbsp;</td>';
	}
	echo '</tr>';*/
}