<?php
	if(isset($_GET['listID'])) {
		$listId = $_GET['listID'];
	} else {
		$listId = 0;
	}
	if(isset($_GET['digestID'])) {
		$digestId = $_GET['digestID'];
	} else {
		$digestId = 0;
	}
	if(isset($_GET['digest_freq'])) {
		$digestFreq = $_GET['digest_freq'];
	} else {
		$digestFreq = 0;
	}
	if( isset($_GET['bl']) ) {
		$backLink = base64_decode($_GET['bl']);
	} else {
		$backLink = null;
	}
	$log = MstFactory::getLogger();
	$digestModel = MstFactory::getDigestModel();
	$listUtils = MstFactory::getMailingListUtils();
	$mList = $listUtils->getMailingList($listId);
	$subscrUtils = MstFactory::getSubscribeUtils();
	$convUtils = MstFactory::getConverterUtils();

	$user = wp_get_current_user();
	if( !is_user_logged_in() ){
		_e("You need to login to access this section", "wpmst-mailster");
	} else {

		if(isset($_GET['submitted'])) {
			$log->debug('saveDigest CALLED');
			if($mList){

				if($mList->allow_digests){
					if($subscrUtils->isUserSubscribedToMailingList($user->ID, 1, $listId)){
						if($digestId > 0){
							$digest = $convUtils->object2Array($digestModel->getDigest($digestId));
						}else{
							$digest = array();
							$digest['id'] = $digestId;
							$digest['list_id'] = $listId;
							$digest['user_id'] = $user->ID;
							$digest['is_core_user'] = 1;
						}
						$digest['digest_freq'] = $digestFreq;
						$log->debug('Digest: '.print_r($digest, true));
						$digestModel->store($digest);
					}
				}
			}
		}

		$digest = false;
		if($digestId > 0){
			$digest = $digestModel->getDigest($digestId);
		} else {
			$digests = $digestModel->getDigestsOfUser($user->ID, true, $listId);
			if($digests && count($digests)>0){
				$digest = $digests[0];
			}
		}

		$digestFreq = 0;
		if($digest){
			$digestFreq = $digest->digest_freq;
		}
		$lists = array();
		$lists['digest_freq'] = $digestModel->getDigestChoiceHtml($digestFreq);
		?>
<form name="digestForm" method="get">
	<input type="hidden" name="bl" value="<?php echo $backLink; ?>" />
	<input type="hidden" name="listID" value="<?php echo $mList->id; ?>" />
	<input type="hidden" name="digestID" value="<?php echo $digest ? $digest->id : 0; ?>" />
	<input type="hidden" name="subpage" value="digest" />
	<input type="hidden" name="submitted" value="1" />
	<input type="hidden" name="bl" value="<?php echo $_GET['bl']; ?>" />

	<div class="mailster_profile contentpane<?php echo $page_sfx; ?>">
		<div class="mailster_profile_container<?php echo $page_sfx; ?>">
			<table class="mailster_profile_table<?php echo $page_sfx; ?> mailster_std_table">
				<tbody>
				<tr>
					<th width="50px">
						<label for="list_id">
							<?php _e( 'Mailing List', "wpmst-mailster" ); ?>:
						</label>
					</th>
					<td width="100px">
						<?php echo $mList->name; ?>
					</td>
				</tr>
				<tr>
					<th width="50px">
						<label for="digest_freq">
							<?php _e( 'Digest frequency', "wpmst-mailster" ); ?>:
						</label>
					</th>
					<td width="100px">
						<?php echo $lists['digest_freq']; ?>
					</td>
				</tr>
				<tr>
					<th width="50px">
						<label for="last_send_date">
							<?php _e( 'Digetst Last Sending', "wpmst-mailster" ); ?>:
						</label>
					</th>
					<td width="100px">
						<?php echo $digest ? $digest->last_send_date : '-'; ?>
					</td>
				</tr>
				<tr>
					<td colspan="2" style="text-align:right">
						<input type="submit" id="saveDigest" name="saveDigest" value="<?php _e('Save', "wpmst-mailster"); ?>" />
					</td>
				</tr>
				</tbody>
			</table>
		</div>
	</div>
</form>
<?php } ?>