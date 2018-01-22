<?php if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
	die( 'These are not the droids you are looking for.' );
}
?>
<table class="form-table">
	<tbody>
		<tr>
			<th scope="row">
				<label for="addressing_mode"><?php _e("Recipient addressing", "wpmst-mailster"); ?> </label>
			</th>
			<td>
				<?php 
				$checked = false;
				if($options->addressing_mode == 1) {
					$checked = true;
				}
				$this->mst_display_simple_radio_field("addressing_mode", 1, "use_bcc", __("Use BCC addressing, hide recipients (recommended)", 'wpmst-mailster'), $checked); ?>				
				<br>
				<div class="suboptions" id="use_bcc_suboptions">
					<input type="text" name="bcc_count" class="small-text" value="<?php echo $options->bcc_count; ?>" placeholder="<?php _e("BCC recipients per mail", 'wpmst-mailster'); ?>" >					
					<label for="bcc_count"><?php _e("BCC recipients per mail", 'wpmst-mailster'); ?></label>
					<br>
					<label for="incl_orig_headers">
						<input type='checkbox' name='incl_orig_headers' id="incl_orig_headers" <?php if($options->incl_orig_headers) echo 'checked="checked"'; ?> value="1">
						<?php _e("Include original TO and CC header addressees (Not recommended)", 'wpmst-mailster'); ?>
					</label>
				</div>				
				<br>
				<?php 
				$checked = false;
				if($options->addressing_mode == 2) {
					$checked = true;
				}
				$this->mst_display_simple_radio_field("addressing_mode", 2, "use_cc", __("Use CC addressing, show all recipients", 'wpmst-mailster'), $checked); ?>				
				<br>
				<?php 
				$checked = false;
				if($options->addressing_mode == 0) {
					$checked = true;
				}
				$this->mst_display_simple_radio_field("addressing_mode", 0, "use_to", __("No BCC/CC addressing, send one mail per recipient", 'wpmst-mailster'), $checked); ?>
			</td>
		</tr>
		<?php
		
		$this->mst_display_select_field( __("'From' email address", 'wpmst-mailster'), 'mail_from_mode',
			array(
				0 => __("General setting (Mailster config)", 'wpmst-mailster'),
				1 => __("Sender address", 'wpmst-mailster'),
				2 => __("Mailing list address", 'wpmst-mailster')
			),
			$options->mail_from_mode
		);
		$this->mst_display_select_field( __("'From' name", 'wpmst-mailster'), 'name_from_mode',
			array(
				0 => __("General setting (Mailster config)", 'wpmst-mailster'),
				1 => __("Sender name", 'wpmst-mailster'),
				2 => __("Mailing list name", 'wpmst-mailster')
			),
			$options->name_from_mode
		);

		$fields = array();
		$fields[0] = new stdClass();
		$fields[0]->value = 0;
		$fields[0]->id = "replyToList";
		$fields[0]->text = __("Replies only to mailing list", 'wpmst-mailster');
		$fields[0]->title = __("Configure where replies to mailing list emails should be sent to", 'wpmst-mailster');
		
		$fields[1] = new stdClass();
		$fields[1]->value = 1;
		$fields[1]->id = "replyToSender";
		$fields[1]->text = __("Replies only to sender", 'wpmst-mailster');
		$fields[1]->title = "";

		$fields[2] = new stdClass();
		$fields[2]->value = 2;
		$fields[2]->id = "replyToSenderAndList";
		$fields[2]->text = __("Reply to sender (and optional mailing list with reply-to-all)", 'wpmst-mailster');
		$fields[2]->title = "";		
		$this->mst_display_multiple_radio_fields( __("Reply destination", 'wpmst-mailster'), 'reply_to_sender', $fields, $options->reply_to_sender); 

		$fields = array();
		$fields[0] = new stdClass();
		$fields[0]->value = 0;
		$fields[0]->id = "useNoBounceAddress";
		$fields[0]->text = __("No dedicated bounces address", 'wpmst-mailster');
		$fields[0]->title = __("Determines where automatic replies (e.g. out-of-office replies and delivery status notifications) should go to. Without a dedicated bounces address those emails go back to the list where they are not forwarded.", 'wpmst-mailster');
		
		$fields[1] = new stdClass();
		$fields[1]->value = 1;
		$fields[1]->id = "useBounceAddress";
		$fields[1]->text = __("Dedicated bounces address", 'wpmst-mailster');
		$fields[1]->title = "";

		$this->mst_display_multiple_radio_fields( __("Bounces destination", 'wpmst-mailster'), 'bounce_mode', $fields, $options->bounce_mode); 
		?>
		<tr class="form-field" >
			<td></td>
			<td>
				<div class="suboptions" id="bounceModeSettings_suboptions">
					<input value="<?php echo $options->bounce_mail; ?>" name="bounce_mail" class="regular-text" id="bounce_mail">
					<label for="bounce_mail"><?php _e("Bounces email", 'wpmst-mailster'); ?></label>
				</div>
			</td>
		</tr>
		<?php
		$this->mst_display_input_field( __("Max. send attempts", 'wpmst-mailster'), 'max_send_attempts', $options->max_send_attempts, null, false, true);

		$this->mst_display_select_field( __("Send reports", 'wpmst-mailster'), 'save_send_reports',
			array(
				0 => __("Do not save send report", 'wpmst-mailster'),
				7 => __("Save send reports for 7 days", 'wpmst-mailster'),
				30 => __("Save send reports for 30 days", 'wpmst-mailster')
			),
			$options->save_send_reports
		);
		?>
	</tbody>
</table>