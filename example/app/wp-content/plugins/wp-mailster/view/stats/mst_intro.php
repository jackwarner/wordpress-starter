<?php
	if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
		die( 'These are not the droids you are looking for.' );
	}
require_once plugin_dir_path( __FILE__ )."../../models/MailsterModelMailster.php";
$Mailster = new MailsterModelMailster();
$data = $Mailster->getData();

//fix collation issues
$dbUtils 	= MstFactory::getDBUtils();
$dbUtils->checkAndFixDBCollations(false);

$active = '<span class="dashicons dashicons-yes"></span>';
$inactive = '<span class="dashicons dashicons-no"></span>';

?>
<div class="mst_container">
	<div class="wrap">
		<?php $mstImgPath =  plugins_url( '/../asset/images/', dirname(__FILE__) );
        $imageName = 'logo_01_free.png';  ?>
		<img src="<?php echo $mstImgPath.$imageName; ?>" alt="<?php _e( 'Mailster Logo', "wpmst-mailster" ); ?>" id="mainLogo"/>
		<div class="statistics">
			
			<div class="statistic">
				<h3><?php _e("General Stats", "wpmst-mailster"); ?></h3>
				<ul>
					<li>
						<?php echo $data->totalLists; ?> <?php _e("Mailing Lists", "wpmst-mailster"); ?>
					</li>
					<li>
						<?php echo $data->inactiveLists; ?> <?php _e("Inactive Lists", "wpmst-mailster"); ?>
					</li>
					<li>
						<?php echo $data->totalMails; ?> <?php _e("Mails", "wpmst-mailster"); ?>
					</li>
					<?php if($data->offlineMails) { ?>
					<li>
						<?php echo $data->offlineMails; ?> <?php _e("Offline mails", "wpmst-mailster"); ?>
					</li>
					<?php } ?>
					<li>
						<a href="?page=mst_queued">
							<?php echo $data->queuedMails; ?> <?php _e("Queued mails", "wpmst-mailster"); ?>
						</a>
					</li>
				</ul>
			</div>
			
			<div class="statistic">
				<?php
				$pluginUtils = MstFactory::getPluginUtils();
				$dateUtils = MstFactory::getDateUtils();
				$nextRetrieveRun 	= $dateUtils->getInTime( date("Y-m-d H:i:s", $pluginUtils->getNextMailCheckTime() ) );
				$nextSendRun 		= $dateUtils->getInTime( date("Y-m-d H:i:s", $pluginUtils->getNextMailSendTime() ) );
				$nextMaintenance 	= $dateUtils->getInTime( date("Y-m-d H:i:s", $pluginUtils->getNextMaintenanceTime() ) );
				?>
				<h3><?php _e("Plugin Activity", "wpmst-mailster"); ?> <a href="#" id="resetTimer"><?php _e("Reset", "wpmst-mailster"); ?></a> </h3>
				<ul>
					<li><?php _e("Next check", "wpmst-mailster"); ?> <?php echo $nextRetrieveRun; ?> </li>
					<li><?php _e("Next sending", "wpmst-mailster"); ?> <?php echo $nextSendRun; ?> </li>
					<li><?php _e("Next cleanup", "wpmst-mailster"); ?> <?php echo $nextMaintenance; ?> </li>
				</ul>
			</div>

			<div class="statistic">
				<h3><?php _e("Mailing lists", "wpmst-mailster"); ?> </h3>
				<ul class="lists">
				<?php
				$lists = &$data->lists;
				
				for( $i=0; $i < count( $lists ); $i++ )	{
					$list = &$lists[$i];
					$edit_nonce = wp_create_nonce( 'mst_deactivate_list' );										
					$editListLink = sprintf(
						'?page=mst_mailing_lists&subpage=edit&lid=%d&_wpnonce=%d',
						$list->id, 
						$edit_nonce
					);
					$editListMembersLink = sprintf( 
						'?page=mst_mailing_lists&subpage=recipients&lid=%d',
						$list->id
					);
					$mailArchiveLink = "?page=mst_archived&selectedlid=".$list->id;
					$editList = '<a href="' . $editListLink . '" title="' . __("Edit mailing list", "wpmst-mailster") . '" >' . __("Edit mailing list", "wpmst-mailster") . '</a>';
					$editMembers = '<a href="' . $editListMembersLink . '" title="' . __("Manage Recipients", "wpmst-mailster") . '" >' . __("Manage recipients", "wpmst-mailster") . '</a>';
					$mailArchive = '<a href="' . $mailArchiveLink . '" title="' . __("View email archive", "wpmst-mailster") . '" >' . __("View email archive", "wpmst-mailster") . '</a>';
					
				?>
					<li>
						<div class="listTitle">

							<h4>
								<a href="#" class="activeToggler" id="activeToggler<?php echo $list->id; ?>" title="<?php echo ($list->active == '1' ? _e("Curretnly active, click to deactivate", "wpmst-mailster") : _e("Curretnly inactive, click to activate", "wpmst-mailster")); ?>">
									<?php echo ($list->active == '1' ? $active : $inactive); ?>
								</a>
								<?php echo $list->name; ?>
							</h4>
							<span><?php echo $editList; ?></span>
							<span><?php echo $editMembers; ?></span>
							<span><?php echo $mailArchive; ?></span>
						</div>
						<ul>
							<li><?php echo $list->recipients; ?> <?php _e("Recipients", "wpmst-mailster"); ?></li>
							<li>
								<?php echo $list->totalMails; ?> <?php _e("Forwarded mails", "wpmst-mailster"); ?> (<?php echo $list->unsentMails; ?> <?php _e("unsent", "wpmst-mailster"); ?>, <?php echo $list->errorMails; ?> <?php _e("send errors", "wpmst-mailster"); ?>)
							</li>
							<li>
								<?php echo $list->blockedFilteredBounced; ?> <?php _e("Not forwarded mails", "wpmst-mailster"); ?> (<?php echo $list->blockedMails . ' ' . __("blocked", "wpmst-mailster"); ?>, <?php echo $list->filteredMails . ' ' .__("filtered", "wpmst-mailster"); ?>, <?php echo $list->bouncedMails . ' ' . __("bounced", "wpmst-mailster"); ?>)
							</li>
						</ul>
					</li>
				<?php } ?>
				</ul>
			</div> 


		</div>
        <div class="statistic shortcode-info">
            <h3><?php _e("Available Shortcodes:", "wpmst-mailster"); ?></h3>
            <?php
            /*  */
            ?>
            <p><span>[mst_profile]</span> <?php _e("Shows digest and subscription/unsubscription options to logged in users.", "wpmst-mailster"); ?></p>
        </div>
		<div class="statistic">
			<h3><?php _e("Tools", "wpmst-mailster"); ?></h3>
			<p><a href="?page=wpmst_mailster_intro&subpage=import"><?php _e("Import Users from CSV", "wpmst-mailster"); ?></a></p>
			<p><a href="?page=wpmst_mailster_intro&subpage=export"><?php _e("Export Users to CSV", "wpmst-mailster"); ?></a></p>
		</div>
        <div class="statistic product-info">
            <br/>
            <?php $showSystemDiagnosis = '?page=wpmst_mailster_intro&subpage=diagnosis'; ?>
            <span><?php  echo MstFactory::getV()->getProductName(true); ?></span><br/>
            <a href="<?php echo $showSystemDiagnosis; ?>" /><?php _e("Show System Diagnosis", "wpmst-mailster"); ?></a>
        </div>
	</div>
</div>