<?php
	if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
		die( 'These are not the droids you are looking for.' );
	}
$uid   = (isset($_GET['uid']) && $_GET['uid']!=''?intval($_GET['uid']):'');
$lid   = (isset($_GET['lid']) && $_GET['lid']!=''?intval($_GET['lid']):'');


$addUser = sanitize_text_field($_POST['add_user']);
if(isset($addUser) && $addUser=='add') {
	$message = $this->wpmst_view_message("updated", __("New user added successfully.", 'wpmst-mailster'));
}elseif(isset($addUser) && $addUser =='edit') {
	$message = $this->wpmst_view_message("updated", __("User updated successfully.", 'wpmst-mailster'));
}

$title  = __("New Mailing List", 'wpmst-mailster');
$button = __('Add Mailing List', 'wpmst-mailster');
$action = 'add';
$message = "";
$options = array();

include_once $this->WPMST_PLUGIN_DIR."/models/MailsterModelList.php";
include_once $this->WPMST_PLUGIN_DIR."/models/MailsterModelServer.php";

$List = new MailsterModelList();
$ServerIn = new MailsterModelServer();
$ServerOut = new MailsterModelServer();
$listAction = sanitize_text_field($_POST['list_action']);
if( isset( $listAction ) ) { //if form is submitted
	
	if ( isset( $_POST['add_list'] ) ) {
		$addList = sanitize_text_field($_POST['add_list']);
		$options['name'] = sanitize_text_field($_POST['name']);
		$options['list_mail'] = sanitize_email($_POST['list_mail']);
		$options['admin_mail'] = sanitize_email($_POST['admin_mail']);
		$options['active'] = sanitize_text_field($_POST['active']);
		$options['mail_content'] = sanitize_text_field($_POST['mail_content']); //serialize($_POST['mail_content']);
		$options['custom_header_html']  = wp_kses_post($_POST['custom_header_html']);
		$options['custom_footer_html']  = wp_kses_post($_POST['custom_footer_html']);

		$options['mail_in_user'] = sanitize_text_field($_POST['mail_in_user']);
		$options['mail_in_pw'] = sanitize_text_field($_POST['mail_in_pw']);
		$options['mail_out_user'] = sanitize_text_field($_POST['mail_out_user']);
		$options['mail_out_pw'] = sanitize_text_field($_POST['mail_out_pw']);
		$options['use_cms_mailer'] = sanitize_text_field($_POST['use_cms_mailer']);
		$options['server_out_id'] = intval($_POST['server_out_id']);
		$options['server_in_id'] = intval($_POST['server_in_id']);

		$server_options_in['name'] = sanitize_text_field($_POST['server_name_in']);
		$server_options_in['server_type'] = sanitize_text_field($_POST['server_type_in']);
		$server_options_in['server_host'] = sanitize_text_field($_POST['server_host_in']);
		$server_options_in['server_port'] = intval($_POST['server_port_in']);
		$server_options_in['protocol'] = sanitize_text_field($_POST['protocol_in']);
		$server_options_in['secure_protocol'] = sanitize_text_field($_POST['secure_protocol_in']);
		$server_options_in['secure_authentication'] = sanitize_text_field($_POST['secure_authentication_in']);
		$server_options_in['connection_parameter'] = sanitize_text_field($_POST['connection_parameter_in']);
		$server_options_in['id'] = intval($_POST['server_in_id']);

		$server_options_out['name'] = sanitize_text_field($_POST['server_name_out']);
		$server_options_out['server_type'] = sanitize_text_field($_POST['server_type_out']);
		$server_options_out['server_host'] = sanitize_text_field($_POST['server_host_out']);
		$server_options_out['server_port'] = intval($_POST['server_port_out']);
		$server_options_out['protocol'] = sanitize_text_field($_POST['protocol_out']);
		$server_options_out['secure_protocol'] = sanitize_text_field($_POST['secure_protocol_out']);
		$server_options_out['secure_authentication'] = sanitize_text_field($_POST['secure_authentication_out']);
		$server_options_out['connection_parameter'] = sanitize_text_field($_POST['connection_parameter_out']);
		$server_options_out['id'] = intval($_POST['server_out_id']);


        $addWhitespace = false;
        if(substr($_POST['subject_prefix'], -1) == ' '){ // last character of subject prefix a space?
            $addWhitespace = true;
        }

        if(function_exists('sanitize_textarea_field')){
            $options['subject_prefix'] = sanitize_textarea_field($_POST['subject_prefix']); // sanitize but preserve white spaces
            $options['custom_header_plain'] = sanitize_textarea_field($_POST['custom_header_plain']);
            $options['custom_footer_plain'] = sanitize_textarea_field($_POST['custom_footer_plain']);
        }else{
            $options['subject_prefix'] = sanitize_text_field($_POST['subject_prefix']);
            $options['custom_header_plain'] = sanitize_text_field($_POST['custom_header_plain']);
            $options['custom_footer_plain'] = sanitize_text_field($_POST['custom_footer_plain']);
        }

        $options['subject_prefix'] = $options['subject_prefix'] . ($addWhitespace ? ' ' : ''); // re-add whitespace at the end that got lost during sanitization

		$options['clean_up_subject'] = intval($_POST['clean_up_subject']);
		$options['mail_format_conv'] = intval($_POST['mail_format_conv']);
		$options['mail_format_altbody'] = intval($_POST['mail_format_altbody']);
		
		
		//save or update data
		$saved = $List->saveData($options, $addList);
		if ( $saved == null ) { //unsuccessfull save
			$message = $this->wpmst_view_message("updated", __("Some problem occured, data not saved. Please try again", 'wpmst-mailster'));			
		} else { 
			$message = $this->wpmst_view_message("updated", __("Mailing list saved successfully.", 'wpmst-mailster'));
			$lid = $saved;
		}

		//save server settings
		$List->saveServer($server_options_in, $options['server_in_id'], false);
		$List->saveServer($server_options_out, $options['server_out_id'], true);
		
	}
}	
$values = null;
if($lid) {
	$title = __("Edit Mailing List", 'wpmst-mailster');
	$button = __('Update Mailing List', 'wpmst-mailster');
	$action = 'edit'; 
//preload values
	$List->setId($lid);	

}
$options = $List->getFormData();

//load servers 
if ( $lid ) {
	$ServerIn->setId($options->server_in_id);
	$ServerOut->setId($options->server_out_id);
}

$serverInOptions = $ServerIn->getFormData();
$serverOutOptions = $ServerOut->getFormData();

?>
<script>
jQuery(document).ready(function($) {
  $("#mst_hide_footer").toggle(function() {
    $(this).text('Show Custom Footer');
  }, function() {
    $(this).text('Hide Custom Footer');
  }).click(function(){
      $(".mst_custom_footer").toggle();
  });
});
jQuery(document).ready(function($) {
  $("#mst_hide_header").toggle(function() {
    $(this).text('Show Custom Header');
  }, function() {
    $(this).text('Hide Custom Header');
  }).click(function(){
      $(".mst_custom_header").toggle();
  });
});
</script>	
<div class="mst_container">
<div class="wrap">
<h2><?php echo $title; ?></h2>
<?php echo (isset($message) && $message!=''?$message:'');?>
<form action="" method="post">
	<div id="tabs">
		<ul>
			<li><a href="#mst_general"><?php _e("General", 'wpmst-mailster'); ?></a></li>
			<li><a href="#mailbox_settings"><?php _e("Mailbox settings", 'wpmst-mailster'); ?></a></li>
			<li><a href="#sender_settings"><?php _e("Sender settings", 'wpmst-mailster'); ?></a></li>
			<li><a href="#mst_mailing_content"><?php _e("Mail content", 'wpmst-mailster'); ?></a></li>
			<li><a href="#list_behaviour"><?php _e("List behaviour", 'wpmst-mailster'); ?></a></li>
			<li><a href="#tabs-6"><?php _e("Sending behaviour", 'wpmst-mailster'); ?></a></li>
			<li><a href="#tabs-7"><?php _e("Subscribing", 'wpmst-mailster'); ?></a></li>
			<li><a href="#tabs-8"><?php _e("Archiving", 'wpmst-mailster'); ?></a></li>
			<li><a href="#tabs-9"><?php _e("Notifications", 'wpmst-mailster'); ?></a></li>
			<li><a href="#tabs-10"><?php _e("Tools", 'wpmst-mailster'); ?></a></li>
		</ul>
		
		<div id="mst_general" class="mst_listing mst_general">
			<table class="form-table">
				<tbody>
				<?php
				$this->mst_display_input_field( __("Mailing list name", 'wpmst-mailster'), 'name', $options->name, null, true);
				$this->mst_display_input_field( __("Mailing list address", 'wpmst-mailster'), 'list_mail', $options->list_mail, null, false);
				$this->mst_display_input_field( __("Mailing list admin email", 'wpmst-mailster'), 'admin_mail', $options->admin_mail, null, false);
				$this->mst_display_truefalse_field( __("Active", 'wpmst-mailster'), 'active', $options->active);
				?>
				</tbody>
			</table>
		</div>
		
		<!-- in server -->
		<div id="mailbox_settings">
			<table class="form-table">
				<tbody>
				<?php
				
				$this->mst_display_input_field( __("Name", 'wpmst-mailster'), 'server_name_in', $serverInOptions->name, null, false);
				$this->mst_display_input_field( __("Host/Server", 'wpmst-mailster'), 'server_host_in', $serverInOptions->server_host, null, false);
				$this->mst_display_input_field( __("User/Login", 'wpmst-mailster'), 'mail_in_user', $options->mail_in_user, null, false);
				$this->mst_display_password_field( __("Password", 'wpmst-mailster'), 'mail_in_pw', $options->mail_in_pw, null, false);
				$this->mst_display_input_field( __("Port", 'wpmst-mailster'), 'server_port_in', $serverInOptions->server_port, null, false);
				$this->mst_display_select_field( __("Protocol", 'wpmst-mailster'), 'protocol_in',
					array(
						"POP3" => __("POP3", 'wpmst-mailster'),
						"IMAP" => __("IMAP", 'wpmst-mailster'),
						"NNTP" => __("NNTP", 'wpmst-mailster')
					),
					$serverInOptions->protocol
				);
				$this->mst_display_select_field( __("Secure setting", 'wpmst-mailster'), 'secure_protocol_in',
					array(
						"None" => __("None", 'wpmst-mailster'),
						"SSL" => __("SSL", 'wpmst-mailster'),
						"TLS" => __("TLS", 'wpmst-mailster')
					),
					$serverInOptions->secure_protocol
				);
				$this->mst_display_truefalse_field( __("Use secure authentication", 'wpmst-mailster'), 'secure_authentication_in', $serverInOptions->secure_authentication);
				$this->mst_display_input_field( __("Special Parameters", 'wpmst-mailster'), 'connection_parameter_in', $serverInOptions->connection_parameter, null);
				?>
				<tr class="form-field">
					<td colspan="2">
						<a href="#" id="inboxConnectionCheck"><span class="dashicons dashicons-update donotshowlink"></span><span class="donotshowlink">&nbsp;</span><?php _e("Test connection", 'wpmst-mailster'); ?></a>
					</td>
				</tr>
				</tbody>
			</table>
		</div>

		<!-- out server -->
		<div id="sender_settings">
			<table class="form-table">
				<tbody>
					<?php 
					
					$this->mst_display_input_field( __("Name", 'wpmst-mailster'), 'server_name_out', $serverOutOptions->name, null, false);
					$this->mst_display_truefalse_field( __("Use WordPress mailer", 'wpmst-mailster'), 'use_cms_mailer', $options->use_cms_mailer);
					$this->mst_display_input_field( __("Host/Server", 'wpmst-mailster'), 'server_host_out', $serverOutOptions->server_host, null, false);
					$this->mst_display_input_field( __("User/Login", 'wpmst-mailster'), 'mail_out_user', $options->mail_out_user, null, false);
					$this->mst_display_password_field( __("Password", 'wpmst-mailster'), 'mail_out_pw', $options->mail_out_pw, null, false);
					$this->mst_display_input_field( __("Port", 'wpmst-mailster'), 'server_port_out', $serverOutOptions->server_port, null, false);
					$this->mst_display_select_field( __("Secure setting", 'wpmst-mailster'), 'secure_protocol_out',
						array(
							"none" => __("None", 'wpmst-mailster'),
							"ssl" => __("SSL", 'wpmst-mailster'),
							"tls" => __("TLS", 'wpmst-mailster')
						),
						$serverOutOptions->secure_protocol
					);
					$this->mst_display_truefalse_field( __("Use secure authentication", 'wpmst-mailster'), 'secure_authentication_out', $serverOutOptions->secure_authentication);
					?>
				</tbody>
			</table>
		</div>

		<div id="mst_mailing_content" class="mst_listing mst_mailing_content">
			<table class="form-table">
				<tbody>
					<?php 
					$this->mst_display_input_field( __("Subject prefix text", 'wpmst-mailster'), 'subject_prefix', $options->subject_prefix, null);
					$this->mst_display_truefalse_field( __("Clean up subject", 'wpmst-mailster'), 'clean_up_subject', $options->clean_up_subject);
					$this->mst_display_select_field( __("Convert email format to", 'wpmst-mailster'), 'mail_format_conv',
						array(
							"0" => __("No conversion", 'wpmst-mailster'),
							"1" => __("HTML email", 'wpmst-mailster'),
							"2" => __("Plain email", 'wpmst-mailster')
						),
						$options->mail_format_conv
					);
					$this->mst_display_truefalse_field( __("Text version in html email", 'wpmst-mailster'), 'mail_format_altbody', $options->mail_format_altbody);
					
					?>
					<tr class="form-field">
						<th scope="row">
							<label for="custom_header"><?php _e("Custom header :", 'wpmst-mailster'); ?></label>
						</th>
						<td>
							<a href="#" id="mst_hide_header" ><?php _e("Hide custom header", 'wpmst-mailster'); ?></a>
						</td>
					</tr>
					<tr class="form-field mst_custom_header">
						<th scope="row">
							<label for="custom_header_plain"><?php _e("Custom header (Text) :", 'wpmst-mailster'); ?></label>
						</th>
						<td>
							<textarea name="custom_header_plain" ><?php echo (isset($custom_header_plain) && $custom_header_plain!=''?$custom_header_plain:''); ?></textarea>
						</td>	
					</tr>	
					<tr class="form-field mst_custom_header">
						<th scope="row">
							<label for="custom_header_html"><?php _e("Custom header (HTML) :", 'wpmst-mailster'); ?> </label>
						</th>
						<td>
							<?php
								$content = (isset($options->custom_header_html) && $options->custom_header_html!=''?$options->custom_header_html:'');
								wp_editor( $content,
									"custom_header_html",
									array(
										"wpautop"=>false,
										'tinymce' => array(
											'theme_advanced_buttons1' => 'formatselect,|,bold,italic,underline,|,' .
											                             'bullist,blockquote,|,justifyleft,justifycenter' .
											                             ',justifyright,justifyfull,|,link,unlink,|' .
											                             ',spellchecker,wp_fullscreen,wp_adv'
										)
									)
								);
							?>
						</td>
					</tr>
					<tr class="form-field">
						<th scope="row">
							<label for="custom_footer"><?php _e("Custom footer :", 'wpmst-mailster'); ?></label>
						</th>
					    <td>
					    	<a href="#" id="mst_hide_footer"><?php _e("Hide custom header", 'wpmst-mailster'); ?></a>
					    </td>
					</tr>
					<tr class="form-field mst_custom_footer">
					    <th scope="row">
					    	<label for="custom_footer_plain"><?php _e("Custom footer (Text) :", 'wpmst-mailster'); ?></label>
					    </th>
					    <td>
					    	<textarea name="custom_footer_plain" ><?php echo (isset($custom_footer_plain) && $custom_footer_plain!=''?$custom_footer_plain:''); ?></textarea>
					    </td>
					</tr>	
					<tr class="form-field mst_custom_footer">
					    <th scope="row">
					    	<label for="custom_footer_html"><?php _e("Custom footer (HTML) :", 'wpmst-mailster'); ?></label>
					    </th>
					    <td>
						    <?php
							    $content = (isset($options->custom_footer_html) && $options->custom_footer_html!=''?$options->custom_footer_html:'');
							    wp_editor( $content,
								    "custom_footer_html",
								    array(
									    "wpautop"=>false,
									    'tinymce' => array(
										    'theme_advanced_buttons1' => 'formatselect,|,bold,italic,underline,|,' .
										                                 'bullist,blockquote,|,justifyleft,justifycenter' .
										                                 ',justifyright,justifyfull,|,link,unlink,|' .
										                                 ',spellchecker,wp_fullscreen,wp_adv'
									    )
								    )
							    );
						    ?>
					    </td>
					</tr>	
				</tbody>
			</table>
		</div>

		<!-- list behaviour -->
		<div id="list_behaviour">
			<table class="form-table">
				<tbody>
					<?php
					$this->mst_display_truefalse_field( __("Mail copy to sender", 'wpmst-mailster'), 'copy_to_sender', $options->copy_to_sender);					
					$this->mst_display_input_field( __("Email Size Limit", 'wpmst-mailster'), 'mail_size_limit', $options->mail_size_limit, null);
					$this->mst_display_truefalse_field( __("Email content filtering", 'wpmst-mailster'), 'filter_mails', $options->filter_mails);	
					$this->mst_display_truefalse_field( __("Allow bulk messages (not recommended)", 'wpmst-mailster'), 'allow_bulk_precedence', $options->allow_bulk_precedence);
					?>
					<tr class="form-field">
						<th scope="row">
							<label for="sending_allowed"><?php _e("Allowed to send/post", 'wpmst-mailster'); ?></label>
						</th>
						<td>
							<label for="sending_public1">
								<input id="sending_public1" type="radio" name="sending_public" value="1" title="<?php _e('You can either allow everybody to post (every email is forwarded to the mailing list) or you can limit the right to send to certain persons or groups of persons', 'wpmst-mailster'); ?>" <?php if($options->sending_public != 0) { echo 'checked="checked"'; } ?> >
								<?php _e("Everybody (Public)", 'wpmst-mailster'); ?>
							</label>
							<br>
							<label for="sending_public0">
								<input id="sending_public0" type="radio" name="sending_public" value="0" title="<?php _e('You can either allow everybody to post (every email is forwarded to the mailing list) or you can limit the right to send to certain persons or groups of persons', 'wpmst-mailster'); ?>" <?php if($options->sending_public == 0) { echo 'checked="checked"'; } ?> >
								<?php _e("Restricted, allowed senders", 'wpmst-mailster'); ?>
							</label>
							<br>
							<ul class="subchoices">
								<li>
									<label for="sending_recipients">
										<input type='checkbox' name='sending_recipients' id="sending_recipients" <?php if($options->sending_recipients) echo 'checked="checked"' ?> value="1">
										<?php _e("All recipients", 'wpmst-mailster'); ?>
									</label>
								</li>
								<li>
									<label for="sending_admin">
										<input type='checkbox' name='sending_admin' id="sending_admin" <?php if($options->sending_admin) echo 'checked="checked"'; ?> value="1">
										<?php _e("List administrator", 'wpmst-mailster'); ?>
									</label>
								</li>
								<li>
									<label for="sending_group">
										<input type='checkbox' name='sending_group' id="sending_group" <?php if($options->sending_group) echo 'checked="checked"' ?> value="1">
										<?php _e("Group", 'wpmst-mailster'); ?>
									</label>
									
									<select name="sending_group_id" id="sending_group_id" >
										<?php 
										include_once $this->WPMST_PLUGIN_DIR."/classes/mst_groups.php";
										$Groups = new Mst_groups();
                                        // TODO FIX IMPLEMENT mst_get_lists
										$groups = $Groups->mst_get_lists();
										foreach ( $groups as $group ) { ?>
										<option value="<?php echo $group['id']; ?>" <?php echo ($options->sending_group_id == $group['id']?'checked="checked"':''); ?>><?php echo $group['name']; ?></option>
										<?php } ?>
									</select>  
								</li>
							</ul>							
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		
		<div id="tabs-6">
			<p><?php _e("Content Goes here...", 'wpmst-mailster'); ?></p>
		</div>
		
		<div id="tabs-7">
			<p><?php _e("Content Goes here...", 'wpmst-mailster'); ?></p>
		</div>
		
		<div id="tabs-8">
			<p><?php _e("Content Goes here...", 'wpmst-mailster'); ?></p>
		</div>
		
		<div id="tabs-9">
			<p><?php _e("Content Goes here...", 'wpmst-mailster'); ?></p>
		</div>
		
		<div id="tabs-10">
			<p><?php _e("Content Goes here...", 'wpmst-mailster'); ?></p>
		</div>

	</div>
	<?php
	$this->mst_display_hidden_field('server_in_id', $options->server_in_id);
	$this->mst_display_hidden_field('server_out_id', $options->server_out_id);
	?>
    <input type="hidden" name="add_list" value="<?php echo $action; ?>" /> 
	<input type="submit" class="button-primary" name="list_action" value="<?php echo $button; ?>">
</form>		
</div>
</div>
<script>
jQuery(document).ready(function($) {
		$( "#tabs" ).tabs();
	  });
</script>
