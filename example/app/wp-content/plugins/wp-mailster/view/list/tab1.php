<?php if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
	die( 'These are not the droids you are looking for.' );
}
?>
<table class="form-table">
	<tbody>
	<?php
	$this->mst_display_input_field( __("Mailing list name", 'wpmst-mailster'), 'name', $options->name, null, true, false,  __("Choose a unique and presentable name - the name of the mailing list is also used in the frontend.", 'wpmst-mailster'));
	$this->mst_display_input_field( __("Mailing list address", 'wpmst-mailster'), 'list_mail', $options->list_mail, null, true, false, __("Email address used for sending emails to the mailing list. Belongs to the mailbox settings defined in the next tab.", 'wpmst-mailster'));
	$this->mst_display_input_field( __("Mailing list admin email", 'wpmst-mailster'), 'admin_mail', $options->admin_mail, null, true, false, __("The mailing list administrator is responsible for managing the mailing list. Please provide an existing email address so that the mailing list administrator can get important notifications.", 'wpmst-mailster'));
	$this->mst_display_truefalse_field( __("Active", 'wpmst-mailster'), 'active', $options->active, false, __("Determines whether or not the mailing list retrieves the emails in the mailing list's inbox and forwards them to the recipients.", 'wpmst-mailster'));

    if(MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_FARCHIVE)){
        $this->mst_display_select_field( __("Allowed to access emails in frontend", 'wpmst-mailster'), 'front_archive_access',
            array(
                0 => __("All users", 'wpmst-mailster'),
                1 => __("Logged-in users", 'wpmst-mailster'),
                2 => __("Logged-in subscribers (of the mailing lists)", 'wpmst-mailster'),
                3 => __("Nobody", 'wpmst-mailster')
            ),
            $options->front_archive_access,
            false,
            false,
            __("User authorized to access the content of the mailing list emails in the frontend email archives", 'wpmst-mailster')
        );
    }else{
        $this->mst_display_sometext( __('Allowed to access emails in frontend', 'wpmst-mailster'), sprintf(__("Available in %s", 'wpmst-mailster'), MstFactory::getV()->getMinV4Ft(MstVersionMgmt::MST_FT_ID_FARCHIVE)), __('User authorized to access the content of the mailing list emails in the frontend email archives', 'wpmst-mailster'));
    }
    ?>
	</tbody>
</table>