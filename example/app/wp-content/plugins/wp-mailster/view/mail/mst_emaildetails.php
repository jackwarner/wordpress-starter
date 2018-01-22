<?php
if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
	die('These are not the droids you are looking for.');
}
$message = "";
$log = MstFactory::getLogger();
$mailUtils 	= MstFactory::getMailUtils();
$dateUtils 	= MstFactory::getDateUtils();
$mstUtils 	= MstFactory::getUtils();
$fileUtils	= MstFactory::getFileUtils();
//$mstUtils->addTabs();
//$mstUtils->addTips();

include_once $this->WPMST_PLUGIN_DIR."/models/MailsterModelMail.php";
$sid   = (isset($_GET['sid']) && $_GET['sid']!=''?intval($_GET['sid']):'');
if ( ! $sid ) {
	if ( isset( $_POST['sid'] ) ) {
		$sid = intval($_POST['sid']);
	}
}
$Email = null;
if($sid) {
	$Email = MstFactory::getMailModel();
	$Email->setId($sid);
} else {
	$Email = MstFactory::getMailModel();
}
$data = $Email->getFormData();

$sendEvents = MstFactory::getSendEvents();
$sendreport = $sendEvents->getSendEventsForMail($sid);

$attachUtils = MstFactory::getAttachmentsUtils();
$attachs = $attachUtils->getAttachmentsOfMail($sid);

if( isset( $_GET['action'] ) && $_GET['action'] == "removeRemainingQueueEntries" ) {
	$mailId = $data->id;
	$log->debug('removeRemainingQueueEntries() for mail ID '.$mailId);
	$mailQueue = MstFactory::getMailQueue();
	$noQueueEntriesBefore = $mailQueue->getNumberOfQueueEntriesForMail($mailId);
	$mailQueue->removeAllRecipientsOfMailFromQueue($mailId);
	$noQueueEntriesAfter = $mailQueue->getNumberOfQueueEntriesForMail($mailId);
	$message = sprintf(__('Removed %d queue entries', 'wpmst-mailster'), $noQueueEntriesBefore);
	$log->debug('removeRemainingQueueEntries() #Queue Entries before: '.$noQueueEntriesBefore.', after: '.$noQueueEntriesAfter);
}
if( isset( $_GET['action'] ) && $_GET['action'] == "resetErrorCount" ) {
	$mailId = $data->id;
	$log->debug('resetErrorCountContinueSending() for mail ID '.$mailId);
	$mailQueue = MstFactory::getMailQueue();
	$noQueueEntries = $mailQueue->getNumberOfQueueEntriesForMail($mailId);
	$mailQueue->resetMailAsUnblockedAndUnsent($mailId);
	$message = sprintf( __('Error status resetted. Continue mail sending to remaining %d recipients in queue.', 'wpmst-mailster'), $noQueueEntries);
}

?>
	<script language="javascript" type="text/javascript">
		var $j = jQuery.noConflict();
		$j(document).ready(function(){
			prepareTabs();
			prepareTips();
		});
	</script>
	<h2><?php _e('Email Details', 'wpmst-mailster'); ?></h2>
	<?php echo (isset($message) && $message!=''?$message:'');?>
	<p>
		<?php
		$mailQueue = MstFactory::getMailQueue();
		$noQueueEntries = $mailQueue->getNumberOfQueueEntriesForMail($data->id);
		if($noQueueEntries) {
			$mailsExceededSendAttempts = $mailQueue->getPendingMailsThatExceededMaxSendAttempts($data->list_id);
			foreach($mailsExceededSendAttempts AS $mailExeededSendAttempts){
			if($mailExeededSendAttempts->id == $data->id) { ?>
				<a href="?page=mst_queued&subpage=details&action=resetErrorCount&sid=<?php echo $data->id; ?>"><?php _e('Reset send errors, continue sending', 'wpmst-mailster'); ?></a>
			<?php }
			} ?>
			<a href="?page=mst_queued&subpage=details&action=removeRemainingQueueEntries&sid=<?php echo $data->id; ?>"><?php _e('Remove remaining queue entries', 'wpmst-mailster'); ?></a>
		<?php } ?>
	</p>
	<table cellspacing="0" cellpadding="0" border="0" width="100%" class="form-table">
		<tr>
			<td style="width:150px;text-align:right;"><label><?php _e( 'Sender address', 'wpmst-mailster' ); ?>:</label></td>
			<td style="width:500px;"><?php echo $data->from_name . '&nbsp; &lt;' . $data->from_email . '&gt;'; ?></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td style="text-align:right;"><label><?php _e( 'Subject', 'wpmst-mailster' ); ?>:</label></td>
			<td style=""><strong><?php echo $data->subject; ?></strong></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td style="text-align:right;"><label><?php _e( 'Date', 'wpmst-mailster' ); ?>:</label></td>
			<td style=""><?php echo $dateUtils->formatDateAsConfigured($data->receive_timestamp) . ' (' . __( 'Forward completed', 'wpmst-mailster' ).': ' . ($data->fwd_completed == '1' ? __( 'Yes', 'wpmst-mailster' ) . ' - ' . $dateUtils->formatDateAsConfigured($data->fwd_completed_timestamp) : __( 'No', 'wpmst-mailster' )) . ')'; ?></td>
			<td>&nbsp;</td>
		</tr>
		<?php
		if($data->size_in_bytes > 0){
			?>
			<tr>
				<td style="text-align:right;"><label><?php _e( 'Size', 'wpmst-mailster' ); ?>:</label></td>
				<td style=""><?php echo $fileUtils->getFileSizeStringForSizeInBytes($data->size_in_bytes); ?></td>
				<td>&nbsp;</td>
			</tr>
			<?php
		}
		?>
		<?php
		if($data->fwd_errors > 0){
			?>
			<tr>
				<td style="text-align:right;"><label><?php _e( 'Forward errors', 'wpmst-mailster' ); ?>:</label></td>
				<td style="color:red;"><?php _e( 'Yes', 'wpmst-mailster' ); echo ' (' . $data->fwd_errors . ')';?></td>
				<td>&nbsp;</td>
			</tr>
			<?php
		}
		$mailQueue = MstFactory::getMailQueue();
		$noQueueEntries = $mailQueue->getNumberOfQueueEntriesForMail($sid);
		$queueEntriesLink = "?page=mst_queued&mailId=".$data->id;
		?>
		<tr>
			<td style="text-align:right;"><label><?php _e( 'Mail Queue Entries', 'wpmst-mailster' ); ?>:</label></td>
			<td><?php echo ($noQueueEntries ? '<a href="'.$queueEntriesLink.'" target="_blank">'. __( 'Yes', 'wpmst-mailster' ) . ' (' . $noQueueEntries . ')'.'</a>' : __( 'No', 'wpmst-mailster' )); ?></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td style="text-align:right;vertical-align:top;"><label><?php _e( 'Original Header', 'wpmst-mailster' ); ?>:</label></td>
			<td style="padding:0;"><table><?php
					if(!is_null($data->orig_to_recips) && (strlen($data->orig_to_recips) > 0)){
						?><tr><th><?php _e( 'TO', 'wpmst-mailster' ); ?></th><td><?php
							$toRecips = $mstUtils->jsonDecode($data->orig_to_recips);
							foreach($toRecips As $k=>$toRecip){
								if($k > 0){	echo ', '; }
								echo htmlentities((strlen($toRecip->name)>0? $toRecip->name.' ' : ''). '<'.$toRecip->email.'>');
							}
							?></td></tr><?php
					}
					if(!is_null($data->orig_cc_recips) && (strlen($data->orig_cc_recips) > 0)){
						?><tr><th><?php _e( 'CC', 'wpmst-mailster' ); ?></th><td><?php
							$ccRecips = $mstUtils->jsonDecode($data->orig_cc_recips);
							foreach($ccRecips As $k=>$ccRecip){
								if($k > 0){	echo ', '; }
								echo htmlentities((strlen($ccRecip->name)>0? $ccRecip->name.' ' : ''). '<'.$ccRecip->email.'>');
							}
							?></td></tr><?php
					}
					?></table></td>
			<td>&nbsp;</td>
		</tr>
		<?php
		if($data->has_attachments == '1'){
			?>
			<tr>
				<td style="text-align:right;"><label><?php _e( 'Attachments', 'wpmst-mailster' ); ?>:</label></td>
				<td style="">
					<?php
					$attachStr = '';
					for($i=0; $i < count($attachs); $i++){
						$attach = &$attachs[$i];
						if($attach->disposition == MstConsts::DISPOSITION_TYPE_ATTACH){
                            $dwlLink = admin_url('admin.php?mst_download=attachment&id='.(int)$attach->id);
							$attachStr = $attachStr . '<a href="' . $dwlLink . '" >' . rawurldecode($attach->filename) . '</a><br/>';
						}
					}
					echo $attachStr;
					?>
				</td>
				<td>&nbsp;</td>
			</tr>
			<?php
		}
		?>
		<tr>
			<td style="text-align:right;"><label>&nbsp;</label></td>
			<td style="width:100%;">
				<div id="tabs">
					<ul>
						<li>
							<a href="#first" ><?php _e( 'Body', 'wpmst-mailster' ); ?></a></li>
						<?php
						if($data->has_send_report){ // has email a send report attached?
							?>
							<li><a href="#second" ><?php _e( 'Send report', 'wpmst-mailster' ); ?></a></li>
							<?php
						}else{
							$title = __( 'No send report stored for this email', 'wpmst-mailster' );
							?>
							<li style="color:darkgrey; padding:0 15px;" title="<?php echo $title; ?>" ><?php _e( 'Send report', 'wpmst-mailster' ); ?></li>
							<?php
						}
						?>
					</ul>
					<div style="display: none; padding-left:20px; padding-top:5px;" id="first" class="tabDiv">
						<div style="width:99%; border:1px solid darkgrey; background-color:white; color:black;">
							<?php
							if(is_null($data->html) || strlen(trim($data->html))<1){
								$body =  nl2br($data->body);
								$content = $body;
							}else{
								$content = $data->html;
								$content = $mailUtils->replaceContentIdsWithAttachments($content, $attachs);
							}
							echo $content;
							?>
						</div>
					</div>
					<div style="display: none; padding-left:20px; padding-top:5px;" id="second" class="tabDiv">
						<div style="width:1000px; border:1px solid darkgrey;">
							<table>
								<tr>
									<th width="130px"><?php _e( 'Date', 'wpmst-mailster' ); ?></th>
									<th width="20px">&nbsp;</th>
									<th width="200px"><?php _e( 'Event', 'wpmst-mailster' ); ?></th>
									<th width="450px"><?php _e( 'Description', 'wpmst-mailster' ); ?></th>
									<th width="200px"></th>
								</tr>
								<?php

								foreach($sendreport AS $event){
									?>
									<tr>
										<td width="130px"><?php echo $dateUtils->formatDate($event->event_time); ?></td>
										<td width="20px"><img src="<?php echo $event->imgPath; ?>" /></td>
										<td width="200px"><?php echo $event->name; ?></td>
										<td width="450px"><?php echo $event->desc; ?></td>
										<td width="200px"><?php
											if($event->recips != null){
												if(property_exists($event->recips, 'to') && count($event->recips->to) > 0){
													echo '<img src="'.$event->recips->toImg.'" title="' . $event->recips->toStr . '" class="hTipWI" style="margin:3px"/> ';
												}
												if(property_exists($event->recips, 'cc') && count($event->recips->cc) > 0){
													echo '<img src="'.$event->recips->ccImg.'" title="' . $event->recips->ccStr . '" class="hTipWI" style="margin:3px"/> ';
												}
												if(property_exists($event->recips, 'bcc') && count($event->recips->bcc) > 0){
													echo '<img src="'.$event->recips->bccImg.'" title="' . $event->recips->bccStr . '" class="hTipWI" style="margin:3px"/> ';
												}
											}
											?></td>
									</tr>
									<?php
								}
								?>
							</table>
						</div>
					</div>
				</div>
			</td>
			<td>&nbsp;</td>
		</tr>
	</table>