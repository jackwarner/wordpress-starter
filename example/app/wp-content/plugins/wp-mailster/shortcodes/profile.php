<?php
	$log = MstFactory::getLogger();
	$user = wp_get_current_user();
	if( !is_user_logged_in() ) {
		_e('You need to login to access this section');
	} else {
		$digestModel = MstFactory::getModel('digest');
		$subscrUtils = MstFactory::getSubscribeUtils();
		$lists = $subscrUtils->getMailingLists2RegisterAt(false, true);
        //$log->debug('profile.php lists to register at: '.print_r($lists, true));
		for($i=0, $n=count($lists); $i<$n; $i++){
			$lists[$i]->isSubscribed = $subscrUtils->isUserSubscribedToMailingList($user->ID, true, $lists[$i]->id);
			$lists[$i]->digest = false;
			if($lists[$i]->isSubscribed){
				if($digestModel->isUserDigestRecipientOfList($user->ID, 1, $lists[$i]->id)){
					$digests = $digestModel->getDigestsOfUser($user->ID, 1, $lists[$i]->id);
					if($digests && count($digests) > 0){
						for($j=0; $j<count($digests); $j++){
							if($digests[$j]->digest_freq > 0){
								$lists[$i]->digest = $digests[$j];
							}
						}
					}
				}
			}
		}

		$log->debug('MailsterViewProfile->display '.print_r($lists, true));
		$currUrl = get_permalink(); //'index.php?&option=com_mailster&view=profile';
		?>
<div class="mailster_profile contentpane<?php echo $page_sfx; ?>">
	<div class="mailster_profile_container<?php echo $page_sfx; ?>">
		<table class="mailster_profile_lists_table<?php echo $page_sfx; ?> mailster_std_table">
			<thead>
			<tr>
				<th class="mailster_profile_lists_header_name<?php echo $page_sfx; ?> mailster_profile_lists_header<?php echo $page_sfx; ?>"><?php _e( 'Mailing List', "wpmst-mailster"); ?></th>
				<th class="mailster_profile_lists_header_status<?php echo $page_sfx; ?> mailster_profile_lists_header<?php echo $page_sfx; ?>"><?php _e( 'Status'); ?></th>
				<?php if(MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_DIGEST)){ ?>
				<th class="mailster_profile_lists_header_digest<?php echo $page_sfx; ?> mailster_profile_lists_header<?php echo $page_sfx; ?>"><?php _e( 'Digest'); ?></th>
				<?php } ?>
				<th class="mailster_profile_lists_header_action<?php echo $page_sfx; ?> mailster_profile_lists_header<?php echo $page_sfx; ?>"><?php _e( 'Action'); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php
				for($i=0;$i<count($lists);$i++) {
					$list = $lists[$i];
					$backlink = base64_encode($currUrl);
					?>
					<tr>
						<td class="mailster_profile_lists_name<?php echo $page_sfx; ?>"><?php echo $list->name; ?></td>
						<td class="mailster_profile_lists_status<?php echo $page_sfx; ?>">
							<span class="mailster_profile_status_text_<?php echo $list->isSubscribed ? 'subscribed':'unsubscribed'; ?>">
							<?php
								if($list->isSubscribed) {
									_e( 'Subscribed', "wpmst-mailster" );
								} else {
									_e( 'Not Subscribed', "wpmst-mailster");
								}
							?>
							</span>
						</td>
						<?php
						if(MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_DIGEST)) { ?>
						<td class="mailster_profile_lists_digest<?php echo $page_sfx; ?>">
							<?php
							if($list->isSubscribed && $list->allow_digests) {
								//$digestLink = 'index.php?view=profile&task=digest&digestID='. ($list->digest ? $list->digest->id : '0').'&listID='. $list->id.'&bl='.$backlink;
								$digestLink = '?subpage=digest&amp;digestID='. ($list->digest ? $list->digest->id : '0').'&listID='. $list->id.'&bl='.$backlink;
							?>
								<a href="<?php echo $digestLink; ?>" title="<?php _e('Edit digest', "wpmst-mailster"); ?>">
								<?php if( $list->digest ) {
									echo $list->digest->digestFreqStr;
								} else {
									_e('No digest', "wpmst-mailster");
								} ?>
								</a>
							<?php } ?>
						</td>
						<?php } ?>
						<td class="mailster_profile_lists_action<?php echo $page_sfx; ?>">
						<?php
							$actionPossible = false;
							$actionTitle = '';
							$actionLink = '';
							if($list->isSubscribed) {
								$actionTitle = __( 'Unsubscribe', "wpmst-mailster");
								//$actionLink = 'index.php?view=profile&task=unsubscribe&listID='. $list->id.'&bl='.$backlink;
								$actionLink  = '?subpage=profile_unsubscribe&amp;listID='. $list->id.'&bl='.$backlink;

								if($list->allow_unsubscribe){
									$actionPossible = true;
								}
							} else {
								$actionTitle = __( 'subscribe', "wpmst-mailster");
								//$actionLink = 'index.php?view=profile&task=subscribe&listID='. $list->id.'&bl='.$backlink;
								$actionLink  = '?subpage=profile_subscribe&amp;listID='. $list->id.'&bl='.$backlink;
								if($list->allow_subscribe) {
									$actionPossible = true;
								}
							}
							if($actionPossible){
								?><a href="<?php echo $actionLink; ?>"><?php echo $actionTitle; ?></a>
							<?php } ?>
						</td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
	</div>
</div>
<?php } ?>