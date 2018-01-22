<?php if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
	die( 'These are not the droids you are looking for.' );
}

$mstConfig = MstFactory::getConfig();
$altTextVars = $mstConfig->isUseAlternativeTextVars();

$txt_email = MstConsts::TEXT_VARIABLES_EMAIL;
$txt_name = MstConsts::TEXT_VARIABLES_NAME;
$txt_description = MstConsts::TEXT_VARIABLES_DESCRIPTION;
$txt_date = MstConsts::TEXT_VARIABLES_DATE;
$txt_list = MstConsts::TEXT_VARIABLES_LIST;
$txt_site = MstConsts::TEXT_VARIABLES_SITE;
$txt_unsub_url = MstConsts::TEXT_VARIABLES_UNSUBSCRIBE_URL;
$txt_example = __( 'By {name} ({email}) on {date}:', 'wpmst-mailster' );
$txt_post_email = MstConsts::TEXT_VARIABLES_POST_EMAIL;
$txt_admin_email = MstConsts::TEXT_VARIABLES_ADMIN_EMAIL;
$txt_recip_email = MstConsts::TEXT_VARIABLES_RECIPIENT_EMAIL;
$txt_recip_name = MstConsts::TEXT_VARIABLES_RECIPIENT_NAME;
if($altTextVars){
    $txt_email = MstConsts::TEXT_VARIABLES_EMAIL_ALT;
    $txt_name = MstConsts::TEXT_VARIABLES_NAME_ALT;
    $txt_description = MstConsts::TEXT_VARIABLES_DESCRIPTION_ALT;
    $txt_date = MstConsts::TEXT_VARIABLES_DATE_ALT;
    $txt_list = MstConsts::TEXT_VARIABLES_LIST_ALT;
    $txt_site = MstConsts::TEXT_VARIABLES_SITE_ALT;
    $txt_unsub_url = MstConsts::TEXT_VARIABLES_UNSUBSCRIBE_URL_ALT;
    $txt_example = __( 'By mailster_var_name (mailster_var_email) on mailster_var_date', 'wpmst-mailster' );
    $txt_post_email = MstConsts::TEXT_VARIABLES_POST_EMAIL_ALT;
    $txt_admin_email = MstConsts::TEXT_VARIABLES_ADMIN_EMAIL_ALT;
    $txt_recip_email = MstConsts::TEXT_VARIABLES_RECIPIENT_EMAIL_ALT;
    $txt_recip_name = MstConsts::TEXT_VARIABLES_RECIPIENT_NAME_ALT;
}
?>
<table class="form-table">
	<tbody>
    <tr>
        <td colspan="2"><?php echo __( 'You can use the text placeholders within the subject prefix, the mail content\'s header and mail content\'s footer.', 'wpmst-mailster' ); ?></td>
        <td rowspan="8">
            <div style="border:2px solid darkgrey;padding:10px;">
                <strong><?php echo __( 'Text placeholders' ) . ':'; ?></strong><br/>
                <pre style="display:inline"><?php echo $txt_email; ?></pre> - <?php echo __( 'Sender email address', 'wpmst-mailster' ); ?><br/>
                <pre style="display:inline"><?php echo $txt_name; ?></pre> - <?php echo __( 'Sender name', 'wpmst-mailster' ); ?><br/>
                <pre style="display:inline"><?php echo $txt_description; ?></pre> - <?php echo __( 'Sender description', 'wpmst-mailster' ); ?><br/>
                <pre style="display:inline"><?php echo $txt_date; ?></pre> - <?php echo __( 'Send time/date', 'wpmst-mailster' ); ?><br/>
                <pre style="display:inline"><?php echo $txt_list; ?></pre> - <?php echo __( 'Mailing list name', 'wpmst-mailster' ); ?><br/>
                <pre style="display:inline"><?php echo $txt_post_email; ?></pre> - <?php echo __( 'Mailing list email address', 'wpmst-mailster' ); ?><br/>
                <pre style="display:inline"><?php echo $txt_admin_email; ?></pre> - <?php echo __( 'Mailing list admin email', 'wpmst-mailster' ); ?><br/>
                <pre style="display:inline"><?php echo $txt_site; ?></pre> - <?php echo __( 'Site name', 'wpmst-mailster' ); ?><br/>
                <pre style="display:inline"><?php echo $txt_unsub_url; ?></pre> - <?php echo __( 'Unsubscribe URL', 'wpmst-mailster' ); ?><br/>
                <pre style="display:inline"><?php echo $txt_recip_email; ?></pre> - <?php echo __( 'Recipient email', 'wpmst-mailster' ); ?><br/>
                <pre style="display:inline"><?php echo $txt_recip_name; ?></pre> - <?php echo __( 'Recipient name', 'wpmst-mailster' ); ?><br/>
                <br/>
                <strong><?php echo __( 'Example', 'wpmst-mailster' ) . ':'; ?></strong><br/>
                <pre style="display:inline"><?php echo $txt_example; ?></pre><br/>
            </div>
        </td>
    </tr>
	<?php
	$this->mst_display_input_field( __("Subject prefix text", 'wpmst-mailster'), 'subject_prefix', $options->subject_prefix, null, false, false, __("Text is put in front of the subject of each mailing list email (insert separating whitespace if wanted). May also contain text variables.", 'wpmst-mailster') );
	$this->mst_display_truefalse_field( __("Clean up subject", 'wpmst-mailster'), 'clean_up_subject', $options->clean_up_subject, false, __("The subject of replies are automatically made consistent (e.g. 'Re: Original subject' instead of 'RE: AW: Re: Original subject')", 'wpmst-mailster') );
	$this->mst_display_select_field( __("Convert email format to", 'wpmst-mailster'), 'mail_format_conv',
		array(
			MstConsts::MAIL_FORMAT_CONVERT_NONE => __("No conversion", 'wpmst-mailster'),
            MstConsts::MAIL_FORMAT_CONVERT_HTML => __("HTML email", 'wpmst-mailster'),
            MstConsts::MAIL_FORMAT_CONVERT_PLAIN => __("Plain email", 'wpmst-mailster')
		),
		$options->mail_format_conv,
		null,
		false,
		__("The email format can be unified (advantage: header/footer always look the same) or it can be left unchanged", 'wpmst-mailster')
	);
	$this->mst_display_truefalse_field( __("Text version in html email", 'wpmst-mailster'), 'mail_format_altbody', $options->mail_format_altbody, false, __("It is a good idea to include a (plain) text version in HTML emails so that the readability is ensured", 'wpmst-mailster') );

    if(MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_D_FOOTER)){
	    $this->mst_display_truefalse_field( __('Disable "powered by Mailster" email footer', 'wpmst-mailster'), 'disable_mail_footer', $options->disable_mail_footer, false, __("Disable the automatic inserted 'powered by Mailster' footer", 'wpmst-mailster') , (MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_D_FOOTER) == false));
    }else{
        $this->mst_display_sometext( __('Disable "powered by Mailster" email footer', 'wpmst-mailster'), sprintf(__("Available in %s", 'wpmst-mailster'), MstFactory::getV()->getMinV4Ft(MstVersionMgmt::MST_FT_ID_D_FOOTER)), __('Disable "powered by Mailster" email footer', 'wpmst-mailster'));
    }
	?>
	<tr>
		<th scope="row">
			<label for="custom_header"><?php _e("Custom header :", 'wpmst-mailster'); ?></label>
		</th>
		<td>
			<a href="#" id="mst_hide_header" ><?php _e("Show custom header", 'wpmst-mailster'); ?></a>
		</td>
	</tr>
	<tr class="mst_custom_header">
		<th scope="row">
			<label for="custom_header_plain"><?php _e("Custom header (Text) :", 'wpmst-mailster'); ?></label>
		</th>
		<td>
			<textarea name="custom_header_plain" ><?php echo (isset($options->custom_header_plain) && $options->custom_header_plain!=''?$options->custom_header_plain:''); ?></textarea>
		</td>
	</tr>
	<tr class="mst_custom_header">
		<th scope="row">
			<label for="custom_header_html"><?php _e("Custom header (HTML) :", 'wpmst-mailster'); ?> </label>
		</th>
		<td>
			<?php
				$content = (isset($options->custom_header_html) && $options->custom_header_html!=''?$options->custom_header_html:'');
				wp_editor( $content,
					"customheaderhtml",
					array(
						"textarea_name" => "custom_header_html",
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
	<tr>
		<th scope="row">
			<label for="custom_footer"><?php _e("Custom footer :", 'wpmst-mailster'); ?></label>
		</th>
		<td>
			<a href="#" id="mst_hide_footer"><?php _e("Show custom footer", 'wpmst-mailster'); ?></a>
		</td>
	</tr>
	<tr class="mst_custom_footer">
		<th scope="row">
			<label for="custom_footer_plain"><?php _e("Custom footer (Text) :", 'wpmst-mailster'); ?></label>
		</th>
		<td>
			<textarea name="custom_footer_plain" ><?php echo (isset($options->custom_footer_plain) && $options->custom_footer_plain!=''?$options->custom_footer_plain:''); ?></textarea>
		</td>
	</tr>
	<tr class="mst_custom_footer">
		<th scope="row">
			<label for="custom_footer_html"><?php _e("Custom footer (HTML) :", 'wpmst-mailster'); ?></label>
		</th>
		<td>
			<?php
				$content = (isset($options->custom_footer_html) && $options->custom_footer_html!=''?$options->custom_footer_html:'');
				wp_editor( $content,
					"customfooterhtml",
					array(
						"textarea_name" => "custom_footer_html",
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