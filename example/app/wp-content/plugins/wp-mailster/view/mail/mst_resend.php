<?php
if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
	die('These are not the droids you are looking for.');
}
$model = MstFactory::getMailModel ();
$listsModel = MstFactory::getListsModel();

$eid = $_GET['eid'];
$mails = array();
if( is_array( $eid) ) {
	foreach($eid as $emailid) {
		$mailModel = MstFactory::getMailModel();
		$mails[] = $mailModel->getData($emailid);
	}
} else {
	$mailModel = new MailsterModelMail();
	$mails[0] = $mailModel->getData($eid);
}
$lists = $listsModel->getData();

if(isset($_POST['mails'])) {
	$log = MstFactory::getLogger();
	$mstQueue = MstFactory::getMailQueue();
	$mListUtils = MstFactory::getMailingListUtils();
	$attachUtilts = MstFactory::getAttachmentsUtils();


	$mailIds = $_POST['mails'];
	$listIds = $_POST['targetLists'];

	if(!is_null($mailIds) && !is_null($listIds)){
		for($i=0; $i<count($mailIds); $i++){
			$mailId = intval($mailIds[$i]);
			$log->debug('Mail ' . $mailId . ' should be resend');
			for($j=0; $j<count($listIds); $j++){
				$model = MstFactory::getMailModel();
				$model->setId($mailId);
				$mail = $model->getData();	// need to get mail every loop as it gets modified below
				$listId = intval($listIds[$j]);
				$mList = $mListUtils->getMailingList($listId);
				$log->debug('Re-enqueue mail ' . $mailId . ' (from list ' . $mail->list_id . ') in mailing list: ' . $mList->id);

				if($mail->list_id == $listId){
					$log->debug('Email origins in the same list (id '.$mail->list_id.'), therefore set it as unblocked and unsent');
					$mstQueue->resetMailAsUnblockedAndUnsent($mailId);
					$log->debug('Add recipients to queue for reset email...');
					$mstQueue->enqueueMail($mail, $mList);
				}else{
					$log->debug('Email origins in a different list (id '.$mail->list_id.'), thus create a copy and enqueue it as a new email...');
					$mailCopyId = $mstQueue->saveAndEnqueueMail($mail, $mList);

					if($mail->has_attachments > 0){
						$log->debug('Email contains attachments, therefore copy attachments table entries...');
						$attachUtilts->copyAttachments2Mail($mailId, $mailCopyId);
					}
				}

				// ####### SAVE SEND EVENT  ########
				$sendEvents = MstFactory::getSendEvents();
				$recipCount = $mstQueue->getNumberOfQueueEntriesForMail($mailId);
				$sendEvents->mailResend($mailId, $recipCount, $mail->list_id, $listId, get_current_user_id());
				// #################################

			}
		}
	}
	?>
	<div class="message">
		<?php sprintf(__('Sent %d emails to %d mailing lists', 'wpmst-mailster'), count($mailIds), count($listIds)); ?>
	</div>
	<?php
}

?>
<form enctype="multipart/form-data" method="post" name="adminForm" id="adminForm">
	<table class="adminform">
		<tr>
			<th><?php _e( 'Emails to resend', "wpmst-mailster" ); echo ' (' . count( $mails ) . ')'; ?></th>
			<th><?php _e( 'Choose target mailing lists', 'wpmst-mailster' ); ?></th>
		</tr>
		<tr>
			<td width="310px" style="vertical-align:top;">
				<table width="300px">
					<?php
					for($i=0, $n=count( $mails ); $i < $n; $i++) {
						$mail = &$mails[$i];
						?>
						<tr>
							<td><?php echo ($i+1); ?></td>
							<td>
								<input type="hidden" name="mails[]" value="<?php echo $mail->id; ?>" />
								<?php echo $mail->subject; ?>
							</td>
						</tr>
						<?php
					}
					?>
				</table>
			</td>
			<td width="210px" style="vertical-align:top;">
				<select id="targetLists" name="targetLists[]" multiple size="10" style="width:200px">
					<?php
					for($i=0, $n=count( $lists ); $i < $n; $i++) {
						$list = &$lists[$i];
						?>
						<option value="<?php echo $list->id; ?>"><?php echo $list->name; ?></option>
						<?php
					}
					?>
				</select>
			</td>
			<td width="100px" style="vertical-align:top;">
				<input type="submit" value="<?php _e( 'Resend', 'wpmst-mailster' ); ?>" class="submitButton"
				       title="<?php _e( 'Resend emails to recipients of the selected mailing lists', 'wpmst-mailster' ); ?>" />
			</td>
			<td>&nbsp;</td>
		</tr>
	</table>
</form>