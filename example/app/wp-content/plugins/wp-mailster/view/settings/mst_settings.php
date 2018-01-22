<?php
	if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
		die('These are not the droids you are looking for.');
	}
?>
<div class="wrap">
<h2><?php _e("Mailster Settings", 'wpmst-mailster'); ?></h2>

<form method="post" action="options.php">
	<?php wp_nonce_field( 'mst-settings' ); ?>
	<?php settings_fields( 'wp_mailster_settings' ); ?>
	<?php do_settings_sections( 'wp_mailster_settings' ); ?>
	<table class="form-table">
		<?php
		/*  */
		?>
		<tr id="tool_info_display_container">
			<td colspan="2">
				<div id="tool_info_display"></div>
			</td>
		</tr>
		<?php
        if(MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_DD_CRON)) {
		    $this->mst_display_input_field( __("Cron Job Key", 'wpmst-mailster'), 'cron_job_key', get_option('cron_job_key'), "", false, false, __("The cronjob key is used for dedicated cronjobs. The cronjob has to be provided with the parameter 'key' in the cronjob URL (e.g. " . get_site_url() . "?wpmst_cronjob=execute&task=all&key=pass123).", 'wpmst-mailster') );
		?>
		<tr>
			<td colspan="2">
			<?php _e("You can use this url for your cron jobs:", "wpmst-mailster"); ?> <?php echo get_site_url(); ?>?wpmst_cronjob=execute&task=all&key=<?php echo get_option('cron_job_key'); ?>
			</td>
		</tr>
		<?php
        }else{
            $this->mst_display_hidden_field("cron_job_key", get_option('cron_job_key') );
        }
			$disabled = array();
			if(!MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_DD_CRON)) {
				$disabled = array("cron");
			}
		$this->mst_display_select_field( __("Trigger source", 'wpmst-mailster'), 'trigger_source',
			array(
				"all" =>  __("All page loads (front- and backend, recommeded when not using a cron job)", 'wpmst-mailster'),
				"admin" =>  __("Backend activity only (recommended for cron jobs in the free edition)", 'wpmst-mailster'),
				"cron" =>  __("Dedicated cronjobs only (WP Mailster Society & Enterprise only, recommended for cron jobs)", 'wpmst-mailster')
			),
			get_option('trigger_source'),
			false,
			false,
			__("The trigger source determines which page loads of the website are used to retrieve and send emails in the backround. Mailster needs to have regular site activity (page loads), otherwise the email delivery is delayed or never done.", 'wpmst-mailster'),
			$disabled
		);

		$this->mst_display_truefalse_field( __("Add subject prefix to replies", 'wpmst-mailster'), 'add_reply_prefix', get_option('add_reply_prefix'), false, __("Add text defined below in front of the subject in mails that are replies (to a previous post)", 'wpmst-mailster'));
		$this->mst_display_input_field( __("Reply Subject Prefix", 'wpmst-mailster'), 'reply_prefix', get_option('reply_prefix', "Re:"), __("Re:", 'wpmst-mailster'), false, false, __("Text to put in front of the subject of reply mails.", 'wpmst-mailster') );
		$this->mst_display_truefalse_field( __("Undo Line Wrapping", 'wpmst-mailster'), 'undo_line_wrapping', get_option('undo_line_wrapping'), false, __("Some email servers automatically do a line wrapping after a fixed number of characters. This option removes those line breaks.", 'wpmst-mailster'));
        $this->mst_display_input_field( __("Date/Time format in WP Mailster", 'wpmst-mailster' ), 'mail_date_format', (get_option( 'mail_date_format' ) ? get_option( 'mail_date_format' ) : get_option( 'date_format', 'd/m/Y' ) . " " . get_option( 'time_format', 'H:i:s' ) ), 'd/m/Y H:i:s', false, false, __( "Format analog to PHP date time format, see http://php.net/manual/en/function.date.php", 'wpmst-mailster' ) );
        $this->mst_display_input_field( __("Date-only format in WP Mailster", 'wpmst-mailster'), 'mail_date_format_without_time', get_option('mail_date_format_without_time', get_option('date_format')), 'd/m/Y', false, false, __("Format analog to PHP date time format, see http://php.net/manual/en/function.date.php", 'wpmst-mailster') );
		$this->mst_display_select_field( __("'From' email address", 'wpmst-mailster'), 'mail_from_field',
			array(
				0 => __("Sender address", 'wpmst-mailster'),
				1 => __("Mailing list address", 'wpmst-mailster')
			),
			get_option('mail_from_field'),
			false,
			false,
			__("The emails forwarded from Mailster use this name in the 'From' field.", 'wpmst-mailster')
		);
		$this->mst_display_select_field( __("'From' name", 'wpmst-mailster'), 'name_from_field',
			array(
				0 => __("Sender name", 'wpmst-mailster'),
				1 => __("Mailing list name", 'wpmst-mailster')
			),
			get_option('name_from_field'),
			false,
			false,
			__("The emails forwarded from Mailster use this name in the 'From' field.", 'wpmst-mailster')
		);
		$this->mst_display_truefalse_field( __("No sender name: take email as name", 'wpmst-mailster'), 'mail_from_email_for_from_name_field', get_option('mail_from_email_for_from_name_field'), false, __("When no sender name is included in the email the sender email address can be inserted as the sender name (only when the 'From' email address is set to the sender address)", 'wpmst-mailster'));
		$this->mst_display_truefalse_field( __("Mailster email header", 'wpmst-mailster'), 'tag_mailster_mails', get_option('tag_mailster_mails'), false, __("All mails forwarded from Mailster have a fixed header field that tags them as originating from Mailster (useful for filter purposes)", 'wpmst-mailster'));
		$this->mst_display_textarea_field( __("Blocked Email Addresses", 'wpmst-mailster'), 'blocked_email_addresses', get_option('blocked_email_addresses', 'bounce@*, bounces@*, mailer-daemon@*'), null, false, __("Emails coming from one of the configured email addresses will not be forwarded. Here you can configure both complete email addresses as so called wildcard addresses. Wildcard means, that only the local part (the name before the @-character) is checked for, not the domain of the email address. An example is the wildcard address john@* - that means that all email addresses that start with john@ will not be forwarded.", 'wpmst-mailster') );
		$this->mst_display_textarea_field( __("Words to filter", 'wpmst-mailster'), 'words_to_filter', get_option('words_to_filter', 'BadWord, Very Bad Words, Really Bad Words'), null, false, __("When mail filtering is activated in the mailing list, mails containing on of these words in the subject or body are not forwarded. Separate the key words with comma.", 'wpmst-mailster') );
		$this->mst_display_select_field( __("Keep blocked emails for #days", 'wpmst-mailster'), 'keep_blocked_mails_for_days',
			array(
				1 => '1',
				3 => '3',
				7 => '7',
				14 => '14',
				30 => '30',
				90 => '90',
				365 => '365'				
			),
			get_option('keep_blocked_mails_for_days', 30),
			false,
			false,
			__("After the time period the emails are deleted to keep a smaller database", 'wpmst-mailster')
		);
		$this->mst_display_select_field( __("Keep bounced emails for #days", 'wpmst-mailster'), 'keep_bounced_mails_for_days',
			array(
				1 => '1',
				3 => '3',
				7 => '7',
				14 => '14',
				30 => '30',
				90 => '90',
				365 => '365'				
			),
			get_option('keep_bounced_mails_for_days', 30),
			false,
			false,
			__("After the time period the emails are deleted to keep a smaller database", 'wpmst-mailster')
		);
		$readonly = false;
		$colors = array();
		$languages = array();
		if(!MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_CAPTCHA)) {
			$readonly = true;
			$colors = array('red', 'white', 'blackglass', 'clean');
			$languages = array('en', 'nl', 'fr', 'de', 'pt', 'ru', 'es', 'tr');
            $aInPrEd = sprintf(__( "Available in %s", 'wpmst-mailster' ), MstFactory::getV()->getMinV4Ft(MstVersionMgmt::MST_FT_ID_CAPTCHA));
            echo '<tr><td colspan="2"><span class="mst-pro-only">'.sprintf(__( "%s feature available in %s", 'wpmst-mailster' ), __( "Captcha" ), MstFactory::getV()->getMinV4Ft(MstVersionMgmt::MST_FT_ID_CAPTCHA)).'</span></td></tr>';
		}
		$this->mst_display_input_field( __( "reCAPTCHA public key", 'wpmst-mailster' ), 'recaptcha2_public_key', get_option( 'recaptcha2_public_key' ), null, false, false, ($readonly ? $aInPrEd.' - ' : '').__( "To use the reCAPTCHA (which is provided externally through Google) you have to have a private and public API key for your domain. reCAPTCHA API keys can be retrieved for free from https://www.google.com/recaptcha/admin/create", 'wpmst-mailster' ), $readonly );
		$this->mst_display_input_field( __( "reCAPTCHA private key", 'wpmst-mailster' ), 'recaptcha2_private_key', get_option( 'recaptcha2_private_key' ), null, false, false, ($readonly ? $aInPrEd.' - ' : '').__( "To use the reCAPTCHA (which is provided externally through Google) you have to have a private and public API key for your domain. reCAPTCHA API keys can be retrieved for free from https://www.google.com/recaptcha/admin/create", 'wpmst-mailster' ), $readonly );
		$this->mst_display_select_field( __( "reCAPTCHA Theme", 'wpmst-mailster' ), 'recaptcha_theme',
			array(
				'light'    => __( "Light", 'wpmst-mailster' ),
				'dark'     => __( "Dark", 'wpmst-mailster' )
			),
			get_option( 'recaptcha_theme', 'light' ),
			false,
			false,
            ($readonly ? $aInPrEd.' - ' : '').__( "Change the design of the captcha to fit your site appearance", 'wpmst-mailster' ),
			$colors,
            $readonly
		);

		$this->mst_display_truefalse_field( __("Alternative text variables", 'wpmst-mailster'), 'use_alt_txt_vars', get_option('use_alt_txt_vars'), false, __("You can use alternative text variables. This is only recommended for the rare case when the text variables are incompatible with e.g. your mailing server", 'wpmst-mailster'));
		$this->mst_display_input_field( __("Send limit (per hour)", 'wpmst-mailster'), 'max_mails_per_hour', get_option('max_mails_per_hour', 0), null, false, false, __("Max. number of emails to send per hour. 0 and empty field means unthrottled/unlimited sending.", 'wpmst-mailster') );
		$this->mst_display_input_field( __("Send limit (per minute)", 'wpmst-mailster'), 'max_mails_per_minute', get_option('max_mails_per_minute', 0), null, false, false, __("Max. number of emails to send per minute. 0 and empty field means unthrottled/unlimited sending.", 'wpmst-mailster') );
		$this->mst_display_input_field( __("Wait between the sending of two emails", 'wpmst-mailster'), 'wait_between_two_mails', get_option('wait_between_two_mails', 0), null, false, false, __("Wait between the sending of two emails at least the defined time period (in seconds). This can throttle the sending process and is there for you, in case your webhoster has send limit requirements. Enter 0 to not throttle the sending (this is also the default). ATTENTION! This feature ONLY works in the throttling-supporting product editions!", 'wpmst-mailster') );
		$this->mst_display_input_field( __("Open mailbox timeout", 'wpmst-mailster'), 'imap_opentimeout', get_option('imap_opentimeout', 20), null, false, false, __("Time in seconds for a mailbox connection to be established. Otherwise a timeout occurs.", 'wpmst-mailster') );

        $this->mst_display_input_field( __("Minimal time between email retrievals", 'wpmst-mailster'), 'minchecktime', get_option('minchecktime', 240), null, false, true, __("Try to retrieve emails every x seconds", 'wpmst-mailster'));
        $this->mst_display_input_field( __("Minimal time between email sending", 'wpmst-mailster'), 'minsendtime', get_option('minsendtime', 60), null, false, true, __("Continue to forward/send enqueued mails every x seconds", 'wpmst-mailster'));
        $this->mst_display_input_field( __("Minimal time between maintenance runs", 'wpmst-mailster'), 'minmaintenance', get_option('minmaintenance', 3600), null, false, true, __("Execute system maintenance every x seconds (recommended: at least 3600 seconds = 1 hour, not more than 86400 seconds = 24 hours)", 'wpmst-mailster'));
        $this->mst_display_input_field( __("Max. execution time", 'wpmst-mailster'), 'maxexectime', get_option('maxexectime', 10), null, false, true, __("Max. time (seconds) to take for retrieving/sending of emails. Recommended: not more than half of the PHP Max Execution Time setting", 'wpmst-mailster'));
        $this->mst_display_input_field( __("Minimal operation duration", 'wpmst-mailster'), 'minduration', get_option('minduration', 2), null, false, true, __("Time (seconds) that is needed to complete an action like connect to a mailbox, adjust it to server performance -> increase it when you experience time outs, has to be significantly lower than Max. Execution Time", 'wpmst-mailster'));

        $this->mst_display_truefalse_field( __("Include mail body in bounced/blocked mail notifications", 'wpmst-mailster'), 'include_body_in_blocked_bounced_notifies', get_option('include_body_in_blocked_bounced_notifies', 1), false, __("The text of the bounced/blocked email can be included in the notification messages", 'wpmst-mailster'));
		$this->mst_display_select_field( __("Message format to use within digests", 'wpmst-mailster'), 'digest_format_html_or_plain',
			array(
				'plain' =>  __("Plain", 'wpmst-mailster'),
				'html' =>  __("HTML", 'wpmst-mailster')
			),
			get_option('digest_format_html_or_plain'),
			false,
			false,
			__("Either the original HTML is used or a conversion into plain text can be done to get a more uniform looking digest", 'wpmst-mailster')
		);
		$this->mst_display_select_field( __("Logging Level", 'wpmst-mailster'), 'logging_level',
			array(
				0 =>  __("Disabled", 'wpmst-mailster'),
				1 =>  __("Errors only", 'wpmst-mailster'),
				3 =>  __("Normal", 'wpmst-mailster'),
				4 =>  __("Max. Logging (Debug)", 'wpmst-mailster')
			),
			get_option('logging_level', MstConsts::LOG_LEVEL_INFO),
			false,
			false,
			__("The logging level determines which kind of events (e.g. an error while retrieving mails) should be written to Mailster's log file", 'wpmst-mailster')
		);
		$this->mst_display_select_field( __("Log destination", 'wpmst-mailster'), 'log_entry_destination',
			array(
				MstConsts::LOG_DEST_FILE =>  __("File", 'wpmst-mailster'),
				MstConsts::LOG_DEST_DB =>  __("Database", 'wpmst-mailster'),
				MstConsts::LOG_DEST_DB_AND_FILE =>  __("Database and File", 'wpmst-mailster')
			),
			get_option('log_entry_destination'),
			false,
			false,
			__("Determines where (how) the log entries are saved", 'wpmst-mailster')
		);

		$this->mst_display_truefalse_field( __("Force logging", 'wpmst-mailster'), 'force_logging', get_option('force_logging'), false, __("Deactivates checks whether log dir is existing and log file is writable. Use with caution! This can not work e.g. if your log path is configured wrong.", 'wpmst-mailster'));
		$this->mst_display_input_field( __("Log file warning size limit (MB)", 'wpmst-mailster'), 'log_file_warning_size_mb', get_option('log_file_warning_size_mb', 25), null, false, true, __("When this size of the log file (in megabyte) is exceeded a warning message is produced", 'wpmst-mailster') );
		$this->mst_display_input_field( __("Log database warning entries limit", 'wpmst-mailster'), 'log_db_warning_entries_nr', get_option('log_db_warning_entries_nr', 1000), null, false, true, __("The count of entries in the log database after that a warning message is produced", 'wpmst-mailster') );

		$this->mst_display_truefalse_field( __("Delete data when uninstalling the plugin", 'wpmst-mailster'), 'uninstall_delete_data', get_option('uninstall_delete_data'), false, __("Checking this option will remove all WP Mailster related data, including your lists, groups and WP Mailster users.", 'wpmst-mailster'));

		$this->mst_display_hidden_field("last_mail_sent_at", get_option('last_mail_sent_at') );
		$this->mst_display_hidden_field("last_hour_mail_sent_in", get_option('last_hour_mail_sent_in') );
		$this->mst_display_hidden_field("nr_of_mails_sent_in_last_hour", get_option('nr_of_mails_sent_in_last_hour') );
		$this->mst_display_hidden_field("last_day_mail_sent_in", get_option('last_day_mail_sent_in') );
		$this->mst_display_hidden_field("nr_of_mails_sent_in_last_day", get_option('nr_of_mails_sent_in_last_day') );
		?>
	</table>
	<?php submit_button(); ?>
	<a href="https://wpmailster.com" target="_blank"><?php _e("Want to make the best out of WPMailster? Click here to find out more", "wpmst-mailster"); ?></a>
</form>
</div>
