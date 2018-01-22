<?php if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
	die( 'These are not the droids you are looking for.' );
}
?>
<table class="form-table">
	<tbody>
		<?php

        if(MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_D_ARCHIVE)){
		    $this->mst_display_truefalse_field( __("Disable email archiving", 'wpmst-mailster'), 'archive_mode', $options->archive_mode, false, __("Email archiving disabled means that the content (body and attachments) of the emails are deleted after the email was forwarded to the mailing list. The subject will still be visible in the email archive.", 'wpmst-mailster') );
        }else{
            $this->mst_display_sometext( __('Disable email archiving', 'wpmst-mailster'), sprintf(__("Available in %s", 'wpmst-mailster'), MstFactory::getV()->getMinV4Ft(MstVersionMgmt::MST_FT_ID_D_ARCHIVE)), __('Email archiving disabled means that the content (body and attachments) of the emails are deleted after the email was forwarded to the mailing list. The subject will still be visible in the email archive.', 'wpmst-mailster'));
        }
        if(MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_EARCHIVE)){
		    $this->mst_display_truefalse_field( __("Archive to digest articles", 'wpmst-mailster'), 'archive2article', $options->archive2article, false, __("All messages of a mailing list can be copied daily into a WordPress post as a digest", 'wpmst-mailster') );
        }else{
            $this->mst_display_sometext( __('Archive to digest articles', 'wpmst-mailster'), sprintf(__("Available in %s", 'wpmst-mailster'), MstFactory::getV()->getMinV4Ft(MstVersionMgmt::MST_FT_ID_EARCHIVE)), __('All messages of a mailing list can be copied daily into a WordPress post as a digest', 'wpmst-mailster'));
        }
		?>
		<tr>
			<td colspan="2" >
				<div class="subchoices">
					<table>
						<?php
                        if(MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_EARCHIVE)){
                            $this->mst_display_select_field( __("Author", 'wpmst-mailster'), 'archive2article_author',
                                array(
                                    //users
                                ),
                                $options->archive2article_author,
                                false,
                                false,
                                __("Author under whose name the digest article is published", 'wpmst-mailster')
                            );
                        }else{
                            $this->mst_display_sometext( __('Author', 'wpmst-mailster'), sprintf(__("Available in %s", 'wpmst-mailster'), MstFactory::getV()->getMinV4Ft(MstVersionMgmt::MST_FT_ID_EARCHIVE)), __('Author under whose name the digest article is published', 'wpmst-mailster'));
                        }
                        if(MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_EARCHIVE)){
                            $this->mst_display_select_field( __("Category", 'wpmst-mailster'), 'archive2article_cat',
                                array(
                                    //categories
                                ),
                                $options->archive2article_cat,
                                false,
                                false,
                                __("Category under which the digest article is published", 'wpmst-mailster')
                            );
                        }else{
                            $this->mst_display_sometext( __('Category', 'wpmst-mailster'), sprintf(__("Available in %s", 'wpmst-mailster'), MstFactory::getV()->getMinV4Ft(MstVersionMgmt::MST_FT_ID_EARCHIVE)), __('Category under which the digest article is published', 'wpmst-mailster'));
                        }
                        if(MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_EARCHIVE)){
                            $this->mst_display_select_field( __("State", 'wpmst-mailster'), 'archive2article_state',
                                array(
                                    "1" => __("Published", 'wpmst-mailster'),
                                    "0" => __("Unpublished", 'wpmst-mailster'),
                                    "2" => __("Archived", 'wpmst-mailster')
                                ),
                                $options->archive2article_state,
                                false,
                                false,
                                __("State of the digest article", 'wpmst-mailster')
                            );
                        }else{
                            $this->mst_display_sometext( __('State', 'wpmst-mailster'), sprintf(__("Available in %s", 'wpmst-mailster'), MstFactory::getV()->getMinV4Ft(MstVersionMgmt::MST_FT_ID_EARCHIVE)), __('State of the digest article', 'wpmst-mailster'));
                        }
						?>
					</table>
				</div>
			</td>
		</tr>
		<?php
        if(MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_EARCHIVE)){
            $this->mst_display_select_field( __("Offline Archiving", 'wpmst-mailster'), 'archive_offline',
                array(
                    "0" => __("No offline archiving", 'wpmst-mailster'),
                    "30" => __("Archive messages older than 30 days", 'wpmst-mailster'),
                    "60" => __("Archive messages older than 60 days", 'wpmst-mailster'),
                    "90" => __("Archive messages older than 90 days", 'wpmst-mailster'),
                    "180" => __("Archive messages older than 180 days", 'wpmst-mailster'),
                    "365" => __("Archive messages older than 365 days", 'wpmst-mailster')
                ),
                $options->archive_offline,
                false,
                false,
                __("The messages in the email archive can be moved to an offline archive to clean up the live database. However, this makes the messages unavailable for access through the frontend archive.", 'wpmst-mailster')
            );
        }else{
            $this->mst_display_sometext( __('Offline Archiving', 'wpmst-mailster'), sprintf(__("Available in %s", 'wpmst-mailster'), MstFactory::getV()->getMinV4Ft(MstVersionMgmt::MST_FT_ID_EARCHIVE)), __('The messages in the email archive can be moved to an offline archive to clean up the live database. However, this makes the messages unavailable for access through the frontend archive.', 'wpmst-mailster'));
        }
		?>
	</tbody>
</table>