<?php
	if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
		die( 'These are not the droids you are looking for.' );
	}

	// If uninstall is not called from WordPress, exit
	if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
		exit();
	}


	if(get_option('uninstall_delete_data')) {
		global $wpdb;
		$wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->prefix . "mailster_attachments`" );
		$wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->prefix . "mailster_digests`" );
		$wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->prefix . "mailster_digest_queue`" );
		$wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->prefix . "mailster_groups`" );
		$wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->prefix . "mailster_group_users`" );
		$wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->prefix . "mailster_lists`" );
		$wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->prefix . "mailster_list_groups`" );
		$wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->prefix . "mailster_list_members`" );
		$wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->prefix . "mailster_list_stats`" );
		$wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->prefix . "mailster_log`" );
		$wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->prefix . "mailster_mails`" );
		$wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->prefix . "mailster_notifies`" );
		$wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->prefix . "mailster_oa_attachments`" );
		$wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->prefix . "mailster_oa_mails`" );
		$wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->prefix . "mailster_queued_mails`" );
		$wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->prefix . "mailster_send_reports`" );
		$wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->prefix . "mailster_servers`" );
		$wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->prefix . "mailster_subscriptions`" );
		$wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->prefix . "mailster_threads`" );
		$wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->prefix . "mailster_users`" );

		unregister_setting( 'wp_mailster_settings', 'license_key' );
		unregister_setting( 'wp_mailster_settings', 'current_version' );
		unregister_setting( 'wp_mailster_settings', 'version_license' );
		unregister_setting( 'wp_mailster_settings', 'uninstall_delete_data' );
		unregister_setting( 'wp_mailster_settings', 'allow_send' );
		unregister_setting( 'wp_mailster_settings', 'cron_job_key' );
		unregister_setting( 'wp_mailster_settings', 'undo_line_wrapping' );
		unregister_setting( 'wp_mailster_settings', 'logging_level' );
		unregister_setting( 'wp_mailster_settings', 'mail_date_format' );
		unregister_setting( 'wp_mailster_settings', 'mail_date_format_without_time' );
		unregister_setting( 'wp_mailster_settings', 'add_reply_prefix' );
		unregister_setting( 'wp_mailster_settings', 'reply_prefix' );
		unregister_setting( 'wp_mailster_settings', 'undo_line_wrapping' );
		unregister_setting( 'wp_mailster_settings', 'trigger_source' );
		unregister_setting( 'wp_mailster_settings', 'mail_from_field' );
		unregister_setting( 'wp_mailster_settings', 'name_from_field' );
		unregister_setting( 'wp_mailster_settings', 'mail_from_email_for_from_name_field' );
		unregister_setting( 'wp_mailster_settings', 'tag_mailster_mails' );
		unregister_setting( 'wp_mailster_settings', 'blocked_email_addresses' );
		unregister_setting( 'wp_mailster_settings', 'words_to_filter' );
		unregister_setting( 'wp_mailster_settings', 'keep_blocked_mails_for_days' );
		unregister_setting( 'wp_mailster_settings', 'keep_bounced_mails_for_days' );
		unregister_setting( 'wp_mailster_settings', 'recaptcha2_public_key' );
		unregister_setting( 'wp_mailster_settings', 'recaptcha2_private_key' );
		unregister_setting( 'wp_mailster_settings', 'recaptcha_theme' );
		unregister_setting( 'wp_mailster_settings', 'use_alt_txt_vars' );
		unregister_setting( 'wp_mailster_settings', 'include_body_in_blocked_bounced_notifies' );
		unregister_setting( 'wp_mailster_settings', 'max_mails_per_hour' );
		unregister_setting( 'wp_mailster_settings', 'max_mails_per_minute' );
		unregister_setting( 'wp_mailster_settings', 'wait_between_two_mails' );
		unregister_setting( 'wp_mailster_settings', 'imap_opentimeout' );
		unregister_setting( 'wp_mailster_settings', 'imap_opentimeout' );
		unregister_setting( 'wp_mailster_settings', 'include_body_in_blocked_bounced_notifies' );
		unregister_setting( 'wp_mailster_settings', 'digest_format_html_or_plain' );
		unregister_setting( 'wp_mailster_settings', 'logging_level' );
		unregister_setting( 'wp_mailster_settings', 'log_entry_destination' );
		unregister_setting( 'wp_mailster_settings', 'force_logging' );
		unregister_setting( 'wp_mailster_settings', 'log_file_warning_size_mb' );
		unregister_setting( 'wp_mailster_settings', 'log_db_warning_entries_nr' );
		unregister_setting( 'wp_mailster_settings', 'last_mail_sent_at' );
		unregister_setting( 'wp_mailster_settings', 'last_hour_mail_sent_in' );
		unregister_setting( 'wp_mailster_settings', 'nr_of_mails_sent_in_last_hour' );
		unregister_setting( 'wp_mailster_settings', 'last_day_mail_sent_in' );
		unregister_setting( 'wp_mailster_settings', 'nr_of_mails_sent_in_last_day' );
		unregister_setting( 'wp_mailster_settings', 'minchecktime' );
		unregister_setting( 'wp_mailster_settings', 'minsendtime' );
		unregister_setting( 'wp_mailster_settings', 'minmaintenance' );
		unregister_setting( 'wp_mailster_settings', 'maxexectime' );
		unregister_setting( 'wp_mailster_settings', 'minduration' );

		delete_option( 'mailster_db_version' );
		delete_option( 'license_key' );
		delete_option('current_version' );
		delete_option('version_license' );
		delete_option('uninstall_delete_data' );
		delete_option('allow_send' );
		delete_option('cron_job_key' );
		delete_option('undo_line_wrapping' );
		delete_option('logging_level' );
		delete_option('mail_date_format' );
		delete_option('mail_date_format_without_time' );
		delete_option('add_reply_prefix' );
		delete_option('reply_prefix' );
		delete_option('undo_line_wrapping' );
		delete_option('mail_from_field' );
		delete_option('name_from_field' );
		delete_option('mail_from_email_for_from_name_field' );
		delete_option('tag_mailster_mails' );
		delete_option('blocked_email_addresses' );
		delete_option('words_to_filter' );
		delete_option('keep_blocked_mails_for_days' );
		delete_option('keep_bounced_mails_for_days' );
		delete_option('recaptcha2_public_key' );
		delete_option('recaptcha2_private_key' );
		delete_option('recaptcha_theme' );
		delete_option('use_alt_txt_vars' );
		delete_option('include_body_in_blocked_bounced_notifies' );
		delete_option('max_mails_per_hour' );
		delete_option('max_mails_per_minute' );
		delete_option('wait_between_two_mails' );
		delete_option('imap_opentimeout' );
		delete_option('imap_opentimeout' );
		delete_option('include_body_in_blocked_bounced_notifies' );
		delete_option('digest_format_html_or_plain' );
		delete_option('logging_level' );
		delete_option('log_entry_destination' );
		delete_option('force_logging' );
		delete_option('log_file_warning_size_mb' );
		delete_option('log_db_warning_entries_nr' );
		delete_option('last_mail_sent_at' );
		delete_option('last_hour_mail_sent_in' );
		delete_option('nr_of_mails_sent_in_last_hour' );
		delete_option('last_day_mail_sent_in' );
		delete_option('nr_of_mails_sent_in_last_day' );
		delete_option('minchecktime' );
		delete_option('minsendtime' );
		delete_option('minmaintenance' );
		delete_option('maxexectime' );
		delete_option('minduration' );
	}