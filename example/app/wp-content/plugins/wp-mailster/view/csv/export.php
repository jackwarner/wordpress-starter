<?php
	if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
		die( 'These are not the droids you are looking for.' );
	}
	$mstUtils = MstFactory::getUtils();
	if(isset($_POST['exportsource'])) {
		$exportSource = sanitize_text_field($_POST['exportsource']);
	} else {
		$exportSource = 'list';
	}
	if(isset($_POST['sourcelist'])) {
		$sourcelist = intval($_POST['sourcelist']);
	} else {
		$sourcelist = 0;
	}
	if(isset($_POST['sourcegroup'])) {
		$sourcegroup = intval($_POST['sourcegroup']);
	} else {
		$sourcegroup = 0;
	}
	if(isset($_POST['delimiter'])) {
		$delimiter = sanitize_text_field($_POST['delimiter']);
	} else {
		$delimiter = ";";
	}
	if(isset($_POST['dataorder'])) {
		$dataorder = sanitize_text_field($_POST['dataorder']);
	} else {
		$dataorder = "name_del_email";
	}

	$groupsModel = MstFactory::getGroupModel();
	$listsModel = MstFactory::getListsModel();
	$groupUsersModel = MstFactory::getGroupUsersModel();

	$groups = $groupsModel->getAllGroups();
	$mailingLists = $listsModel->getData();


	$log = MstFactory::getLogger();
	$downloadLink = "";
	//do the actual export
	if(isset($_POST['task'])) {

		$filename= 'file.csv';
		$recips = array();
		if($exportSource === 'group'){
			$filename = 'group'.$sourcegroup.'.csv';
			$groupModel = MstFactory::getGroupUsersModel();
			$recips = $groupModel->getData($sourcegroup);
		}elseif($exportSource === 'list'){
			$filename = 'list'.$sourcelist.'.csv';
			$recipUtils = MstFactory::getRecipients();
			$recips = $recipUtils->getRecipients($sourcelist);
		}

		$orderedRecips = array();

		foreach($recips AS $recip){
			if(strtolower($dataorder) == 'name_del_email'){
				$first = $recip->name;
				$second = $recip->email;
			}else{
				$first = $recip->email;
				$second = $recip->name;;
			}
			$orderedRecips[] = array($first, $second);
		}

		$downloadLink = outputCSVFileDownload($orderedRecips, $filename, $delimiter); //show it as a link!
	}

    function outputCSVLineToFile(&$vals, $key, $csvProps) {
        fputcsv($csvProps->filehandler, $vals, $csvProps->delimiter); // add parameters if you want
    }

	function outputCSVFileDownload($arr, $filename='file.csv', $delimiter=','){
		$url = plugin_dir_url( __FILE__);
		$outstream = fopen(plugin_dir_path(__FILE__).$filename, "w");
		$csvProps = new stdClass();
		$csvProps->filehandler = $outstream;
		$csvProps->delimiter = $delimiter;
		array_walk($arr, 'outputCSVLineToFile', $csvProps);
		fclose($outstream);
		//save to file
		//return file url
		return $url.$filename;
	}
?>

<div class="mst_container">
	<div class="wrap">
		<h2><?php _e("Export users", "wpmst-mailster"); ?></h2>
		<?php echo (isset($message) && $message!=''?$message:'');?>

		<div id="mst_list_members" class="mst_listing mst_list_members">
			<div class="wptl_container">
				<div class="wrap">
					<form enctype="multipart/form-data" method="post" name="adminForm" id="adminForm">
						<table class="adminform">
							<tr><th colspan="2"><?php _e( 'Export Options', 'wpmst-mailster' ); ?></th><th></th><th>&nbsp;</th></tr>
							<tr>
								<td width="100px" style="text-align:right;" colspan="2">
									<label for="delimiter" title="<?php _e( 'Character/letter separating name and email column', 'wpmst-mailster' );?>">
										<?php _e( 'Delimiter', 'wpmst-mailster' ); ?>:
									</label>
								</td>
								<td width="200px">
									<input class="inputbox" name="delimiter" value="<?php echo $delimiter; ?>" size="3" maxlength="5" id="delimiter" title="<?php _e( 'Character/letter separating name and email column', 'wpmst-mailster');?>"/>
								</td>
								<td>&nbsp;</td>
							</tr>
							<tr>
								<td width="100px" style="text-align:right;" colspan="2">
									<label for="dataorder" title="<?php _e( 'Order of the columns (name, email) in the CSV file', 'wpmst-mailster' );?>">
										<?php _e( 'Data order in CSV File', 'wpmst-mailster' ); ?>:
									</label>
								</td>
								<td width="200px">
									<input type="radio" name="dataorder" value="name_del_email" id="name_del_email" <?php if($dataorder == "name_del_email") echo 'checked="checked"'; ?> />
									<label for="name_del_email"><?php _e( 'Name [delimiter] Email', 'wpmst-mailster' ); ?></label>
								</td>
								<td>&nbsp;</td>
							</tr>
							<tr>
								<td width="100px" colspan="2">&nbsp;</td>
								<td width="200px">
									<input type="radio" name="dataorder" value="email_del_name" id="email_del_name" <?php if($dataorder == "email_del_name") echo 'checked="checked"'; ?> />
									<label for="email_del_name"><?php _e( 'Email [delimiter] Name', 'wpmst-mailster' ); ?></label>
								</td>
								<td>&nbsp;</td>
							</tr>
							<tr><th colspan="2"><?php _e( 'Export source', 'wpmst-mailster' ); ?></th><th></th><th>&nbsp;</th></tr>
							<tr>
								<td width="100px" style="text-align:right;" colspan="2">
									<label for="exportsource">
										<?php _e( 'Export users of', 'wpmst-mailster' ); ?>:
									</label>
								</td>
								<td width="200px">&nbsp;</td>
								<td>&nbsp;</td>
							</tr>
							<tr>
								<td width="100px" colspan="2">&nbsp;</td>
								<td width="200px">
									<input type="radio" name="exportsource" value="list" id="list"  <?php echo $exportSource == 'list' ? 'selected="selected"' : ''; ?>/>
									<?php _e( 'Mailing list', 'wpmst-mailster' ); ?>
								</td>
								<td width="200px" rowspan="3"><?php _e( 'Choose a mailing list', 'wpmst-mailster' ); ?><br/>
									<select id="sourcelist" name="sourcelist" style="width:180px" disabled="disabled">
										<?php
											for($i=0, $n=count( $mailingLists ); $i < $n; $i++) {
												$mailingList = &$mailingLists[$i];
												//autoselect the first list if no list is selected
												if($i==0) {
													if ( $sourcelist == 0 ) {
														$sourcelist = $mailingList->id;
													}
												}
												$selected = ($mailingList->id == $sourcelist ? 'selected="selected"' : '');
												?>
												<option value="<?php echo $mailingList->id; ?>" <?php echo $selected; ?>><?php echo $mailingList->name; ?></option>
												<?php
											}
										?>
									</select>
								</td>
								<td>&nbsp;</td>
							</tr>
							<tr><td width="100px" colspan="2">&nbsp;</td></tr>
							<tr><td width="100px" colspan="2">&nbsp;</td></tr>
							<tr><td width="100px" colspan="2">&nbsp;</td></tr>
							<tr>
								<td width="100px" colspan="2">&nbsp;</td>
								<td width="200px">
									<input type="radio" name="exportsource" value="group" id="group" <?php echo $exportSource == 'group' ? 'selected="selected"' : ''; ?> />
									<?php _e( 'Group', 'wpmst-mailster' ); ?>
								</td>
								<td width="200px" rowspan="3"><?php _e( 'Choose a group', 'wpmst-mailster' ); ?><br/>
									<select id="sourcegroup" name="sourcegroup" style="width:180px" disabled="disabled">
										<?php
											for($i=0, $n=count( $groups ); $i < $n; $i++) {
												$group = &$groups[$i];
												$selected = ($group->id == $sourcegroup ? 'selected="selected"' : '');
												?>
												<option value="<?php echo $group->id; ?>" <?php echo $selected; ?>><?php echo $group->name; ?></option>
												<?php
											}
										?>
									</select>
								</td>
								<td>&nbsp;</td>
							</tr>
							<tr><td colspan="2">&nbsp;</td></tr>
							<tr><td colspan="2">&nbsp;</td></tr>
							<tr><td colspan="2">&nbsp;</td></tr>
							<tr>
								<td colspan="3">&nbsp;</td>
								<td width="100px" style="text-align:right;">
									<input type="submit" name="submitbutton" value="<?php _e( 'Export now', 'wpmst-mailster' ); ?>" id="submitbutton" class="submitButton" />
								</td>
								<td>&nbsp;</td>
							</tr>
						</table>
						<input type="hidden" name="task" value="startexport" />
					</form>
					<?php if ($downloadLink != "" ) { ?>
						<a href="<?php echo $downloadLink; ?>"><?php _e("Download exported file by clicking here", "wpmst-mailster"); ?></a>
					<?php } ?>
				</div>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">
	var $j = jQuery.noConflict();
	var exportSource;
	$j(document).ready(
		function () {
			exportSource = '<?php echo $exportSource; ?>';
			if(exportSource == ''){
				exportSource = 'list';
			}
			if(exportSource == 'group'){
				$j('#group').attr('checked', 'checked');
			}else if(exportSource == 'list'){
				$j('#list').attr('checked', 'checked');
			}
			$j('input[name=exportsource]').change(function(){
				exportSource = $j('input[name=exportsource]:checked').val();
				toggleExportSource();
			});
			toggleExportSource();
		});

	function toggleExportSource(){
		if(exportSource == 'list'){
			$j('#sourcegroup').attr('disabled',  'disabled');
			$j('#sourcelist').removeAttr('disabled');
		}
		if(exportSource == 'group'){
			$j('#sourcelist').attr('disabled',  'disabled');
			$j('#sourcegroup').removeAttr('disabled');
		}
	}

	function submitbutton(task)
	{
		var form = document.adminForm;
		if (task == 'cancel') // check we aren't cancelling
		{	// no need to validate, we are cancelling
			submitform( task );
			return;
		}else{
			submitform( task );
		}
	}
</script>