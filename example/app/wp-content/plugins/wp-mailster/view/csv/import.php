<?php
	if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
		die( 'These are not the droids you are looking for.' );
	}
	$users = null;
    $inputVarOffset = 40;
	if( isset($_POST['importtask']) ) {
		$importtask  = sanitize_text_field($_POST['importtask']);
	} else {
		$importtask = "";
	}
	if( isset($_POST['targetgroup']) ) {
		$targetgroup  = intval($_POST['targetgroup']);
	} else {
		$targetgroup = 0;
	}
	if( isset($_POST['targetlist']) ) {
		$targetlist  = intval($_POST['targetlist']);
	} else {
		$targetlist = 0;
	}
	if( isset($_POST['duplicateopt']) ) {
		$duplicateopt  = intval($_POST['duplicateopt']);
	} else {
		$duplicateopt = 0;
	}
	if( isset($_POST['newgroupname']) ) {
		$newgroupname  = sanitize_text_field($_POST['newgroupname']);
	} else {
		$newgroupname = "";
	}
	if( isset($_POST['importtarget']) ) {
		$importtarget  = sanitize_text_field($_POST['importtarget']);
	} else {
		$importtarget = "";
	}

	$listsModel = MstFactory::getListsModel();
	$groupsModel = MstFactory::getGroupModel();
	$groupUsersModel = MstFactory::getGroupUsersModel();
	$groups = $groupsModel->getAllGroups();
	$mailLists = $listsModel->getData();
	$mstUtils = MstFactory::getUtils();

	//** start step 1
	if(isset($_POST['task']) && $_POST['task'] == "startimport") {
		$log       = MstFactory::getLogger();
		$mailUtils = MstFactory::getMailUtils();
		//$mailer = JFactory::getMailer();
		if ( isset( $_POST['filepath'] ) ) {
			$filePath = sanitize_text_field( $_POST['filepath'] );
		} else {
			$filePath = "";
		}
		if ( isset( $_POST['delimiter'] ) ) {
			$delimiter = sanitize_text_field( $_POST['delimiter'] );
		} else {
			$delimiter = "";
		}
		if ( isset( $_POST['dataorder'] ) ) {
			$dataorder = sanitize_text_field( $_POST['dataorder'] );
		} else {
			$dataorder = "";
		}
		if ( isset( $_POST['importtask'] ) ) {
			$importtask = sanitize_text_field( $_POST['importtask'] );
		} else {
			$importtask = "";
		}
		if ( isset( $_POST['targetgroup'] ) ) {
			$targetgroup = sanitize_text_field( $_POST['targetgroup'] );
		} else {
			$targetgroup = "";
		}
		if ( isset( $_POST['duplicateopt'] ) ) {
			$duplicateopt = sanitize_text_field( $_POST['duplicateopt'] );
		} else {
			$duplicateopt = "";
		}
		if ( isset( $_POST['targetlist'] ) ) {
			$targetlist = sanitize_text_field( $_POST['targetlist'] );
		} else {
			$targetlist = "";
		}
		if ( isset( $_POST['newgroupname'] ) ) {
			$newGroupName = sanitize_text_field( $_POST['newgroupname'] );
		} else {
			$newGroupName = "";
		}
		if ( isset( $_POST['datasource'] ) ) {
			$dataSource = sanitize_text_field( $_POST['datasource'] );
		} else {
			$dataSource = "";
		}
		$uploaded = false;
		$log->debug( 'Starting CSV import...' );
		$log->debug( 'Data Source: ' . $dataSource );
		if ( $dataSource == 'local_file' ) {
			$log->debug( 'Source: Local file, we need to upload' );
			// local file, so we have to upload it first
			if ( ! function_exists( 'wp_handle_upload' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/file.php' );
			}
			$result = wpmst_parse_file_errors( $_FILES['filepath_local'] );

			if ( $result['error'] ) {
				$message = '<p>ERROR: ' . $result['error'] . '</p>';
			} else {
				//do the import
			}
			// Build the appropriate paths
			//$tmp_dest = plugin_dir_path(__FILE__) . "imports/" . $filePath['name'];
			//$tmp_src  = $filePath['tmp_name'];
			//$log->debug( 'Source File: ' . $tmp_src . ', Destination File: ' . $tmp_dest );
			// Parse uploaded file and save data to session
			$uploadedfile     = $_FILES['filepath_local'];
			$filePath = $_FILES["filepath_local"]["tmp_name"];
			$uploaded = true;
		} else {
			$log->debug( 'Source: Server file, NO need to upload' );
			// server file, should be uploaded
			$uploaded = true;
			$filePath = get_home_path() . "/" . $filePath; // source file for import
		}
		$log->debug( 'Filepath: ' . $filePath );
		$log->debug( 'Uploaded: ' . $uploaded );
		// Auto detect line endings, to deal with Mac line endings...
		$oldLineEndingSetting = ini_get( 'auto_detect_line_endings' );
		ini_set( 'auto_detect_line_endings', true );
		$message = "";
		if ( $uploaded == true ) {
			$filePointer = @fopen( $filePath, "r" );
			print_r($filePointer);
			if ( $filePointer ) {
				$log->debug( 'Got file handle of file ' . $filePath );
				$i     = 0;
				$log->debug( 'Working with delimiter: ' . $delimiter );
				while ( ( $data = fgetcsv( $filePointer, 500, $delimiter ) ) !== false ) {
					if ( ! is_null( $data ) ) {
						$log->debug( 'Data No ' . $i . ' #cols: ' . count( $data ) );
						if ( isset( $data[0] ) && isset( $data[1] ) ) {
							$firstEnc  = mb_detect_encoding( $data[0] . ' ', 'UTF-8, ISO-8859-1, ISO-8859-15' );
							$secondEnc = mb_detect_encoding( $data[1] . ' ', 'UTF-8, ISO-8859-1, ISO-8859-15' );
							if ( $firstEnc == 'UTF-8' ) {
								$first = utf8_decode( $data[0] );
								if ( substr( $first, 0, 1 ) == '?' ) {
									$first = substr( $first, 1 );
								}
							} else {
								$first = mb_convert_encoding( $data[0], 'UTF-8', $firstEnc );
							}
							if ( $secondEnc == 'UTF-8' ) {
								$second = utf8_decode( $data[1] );
							} else {
								$second = mb_convert_encoding( $data[1], 'UTF-8', $secondEnc );
							}
							$first  = htmlentities( $first );
							$second = htmlentities( $second );
							if ( strtolower( $dataorder ) == 'name_del_email' ) {
								$name  = $first;
								$email = $second;
							} else {
								$name  = $second;
								$email = $first;
							}
							if ( is_email( $email ) && $mailUtils->isValidEmail( $email ) ) {
								$users[ $i ]['name']  = $name;
								$users[ $i ]['email'] = $email;
								$i ++;
							} else {
								$log->debug( 'CSV file contains for user "' . $name . '" invalid email: ' . $email );
								$message .= 'CSV file contains for user "' . $name . '" invalid email: ' . $email. '<br>';
							}
						} else {
							$log->debug( 'Skipping incomplete line: ' . print_r( $data, true ) );
						}
					} else {
						$log->debug( 'Skipping invalid line' );
					}
				}
				$log->debug( 'Close file handle' );
				fclose( $filePointer );

                $userCount = count( $users );
                $maxInputVars = ini_get('max_input_vars');
                if($maxInputVars > 0 && $maxInputVars > $inputVarOffset){
                    $maxInputVars = $maxInputVars - $inputVarOffset; // we need some variables for the control handling...
                    $maxInputVars = floor($maxInputVars/2); // we need two (name + email) inputs per user entry
                }
                if($maxInputVars < $userCount){
                    $message .= sprintf(__('Your server only supports importing a maximum of %d entries at once!', "wpmst-mailster"), $maxInputVars ).'<br>';
                }

				$log->debug( 'File imported successfully, #data sets loaded: ' . $userCount.' (max input: '.$maxInputVars.')' );
				$message .= __("File Loaded Successfully", "wpmst-mailster");

				reviewimports();
			} else {
				$message .= __("File not found", "wpmst-mailster");
				$log->debug( 'Could NOT get file handle for file ' . $filePath );
				import();
			}
		} else {
			$message .= __("File not found", "wpmst-mailster");
			$log->debug( 'Could NOT upload file ' . $filePath );
			import();
		}
		ini_set( 'auto_detect_line_endings', $oldLineEndingSetting );
	} else if(isset($_POST['task']) && $_POST['task'] == "saveimport") {
		$message = saveimport();
	}

	$titleTxt = __( 'CSV Import', 'wpmst-mailster' );
	if($users){
		$titleTxt = $titleTxt . ' (' . __( 'step', 'wpmst-mailster' ) . ' 2/2)';
	}else{
		$titleTxt = $titleTxt . ' (' . __( 'step', 'wpmst-mailster' ) . ' 1/2)';
	}
	//** end step 1

	function import()
	{
		if(isset($_POST['importtarget'])) {
			$importtarget = $_POST['importtarget'];
		} else {
			$importtarget = "";
		}
		if (!session_id()) {
			session_start();
		}
		$_SESSION['importtarget'] = $importtarget;
		$groupModel = MstFactory::getGroupModel();
		$listsModel = MstFactory::getListsModel();
		$groupUsersModel = MstFactory::getGroupUsersModel();
	}

	function saveimport()
	{
		if( isset($_POST['importtask']) ) {
			$importtask  = sanitize_text_field($_POST['importtask']);
		} else {
			$importtask = "";
		}
		if( isset($_POST['targetgroup']) ) {
			$targetgroup  = intval($_POST['targetgroup']);
		} else {
			$targetgroup = 0;
		}
		if( isset($_POST['targetlist']) ) {
			$targetlist  = intval($_POST['targetlist']);
		} else {
			$targetlist = 0;
		}
		if( isset($_POST['usercount']) ) {
			$userCr  = intval($_POST['usercount']);
		} else {
			$userCr = 0;
		}
		if( isset($_POST['newgroupname']) ) {
			$newGroupName  = sanitize_text_field($_POST['newgroupname']);
		} else {
			$newGroupName = "";
		}
		if( isset($_POST['duplicateopt']) ) {
			$duplicateopt  = sanitize_text_field($_POST['duplicateopt']);
		} else {
			$duplicateopt = "";
		}
		$addedToList = false;
		$addedToGroup = false;

		/*if($importtask == 'importonly'){
			$fwdLink = 'index.php?option=com_mailster&view=users';
		}elseif($importtask == 'add2group'){
			$fwdLink = 'index.php?option=com_mailster&controller=groupusers&task=groupusers&groupID=';
			if($targetgroup != 0){
				$fwdLink = $fwdLink . $targetgroup;
			}
		}elseif($importtask == 'add2list'){
			$fwdLink = 'index.php?option=com_mailster&controller=listmembers&task=listmembers&listID=' . $targetlist;
		}*/

		$importedCr = 0;
		$message = "";
		for($i = 0; $i < $userCr; $i++){
			$name 		= $_POST['name'. $i] ;
			$email	 	= $_POST['email'. $i] ;

			if($email != ''){
				if( $name == "" ) {
					$message .= "<br>" . sprintf( __( "User with email %s, does not have a name.", "wpmst-mailster" ), $email );
				}

				$duplicate = true;
				$model	= MstFactory::getUserModel();
				if($duplicateopt == 'merge'){
					$user	= $model->isDuplicateEntry($email, true);
					if(!$user){
						$duplicate = false;
					}else{
						// we have a duplicate and load the existent user identifiers
						$userId = $user->id;
						$isCoreUser = $user->is_core_user;
					}
				}
				if(($duplicateopt == 'ignore') || ($duplicate == false)) {
					// Create a new Mailster User
					$user = new stdClass();
					$user->id		= 0;
					$user->name		= $name;
					$user->email	= $email;

					$user_options['name'] = sanitize_text_field($name);
					$user_options['email'] = sanitize_email($email);
					$success = $model->saveData($user_options);
					$userId = $model->getId();
					$isCoreUser = 0;
				}

				if($importtask == 'add2group'){
					if($targetgroup == 0){
						// Create a new Group
						$model = MstFactory::getGroupModel();
						$group_options[ 'name' ] = sanitize_text_field($newGroupName);
						$model->saveData( $group_options );
						$targetgroup = $model->getId();
						//$fwdLink = $fwdLink . $targetgroup;
					}
					// Insert User in Group
					$model = MstFactory::getGroupUsersModel();
					$groupUser = new stdClass();
					$groupUser->user_id			= $userId;
					$groupUser->group_id		= $targetgroup;
					$groupUser->is_core_user	= $isCoreUser;
					$success = $model->store($groupUser);
					$addedToGroup = true;
				}elseif($importtask == 'add2list'){
					// Insert User in Mailing List
					$list = new MailsterModelList($targetlist);
					$success = $list->addUserById( intval($userId), intval($isCoreUser) );
					$addedToList = true;
				}
				$importedCr++;
			}
		}
		$message .= '<br><br>'.sprintf(__("Successfully imported %d users.", "wpmst-mailster"), $importedCr).'<br>';
		if($addedToList){
			$list = new MailsterModelList($targetlist);
			$mstRecipients = MstFactory::getRecipients();
			$mstRecipients->recipientsUpdated($targetlist); // update cache state
			$message .= " " . __("New users were added to the list", "wpmst-mailster") . " <a href='?page=mst_mailing_lists&subpage=recipients&lid=" . $targetlist . "'>" . $list->_data[0]->name . "</a>";
		}
		if($addedToGroup){
			$mstRecipients 		= MstFactory::getRecipients();
			$groupUsersModel	= MstFactory::getGroupUsersModel();
			$listsToUpdRecips 	= $groupUsersModel->getListsWithGroup($targetgroup);

			for($k=0; $k < count($listsToUpdRecips); $k++)
			{
				$currList = &$listsToUpdRecips[$k];
				$mstRecipients->recipientsUpdated($currList->id); // update cache state
			}
			$group = new MailsterModelGroup($targetgroup);
			$message .= " " . __("New users were added to the group", "wpmst-mailster") . " <a href='?page=mst_groups&subpage=edit&sid=" . $targetgroup . "'>" . $group->_data[0]->name . "</a>";
		}
		return $message;
	}

?>
<div class="mst_container">
	<div class="wrap">
		<h2><?php echo $titleTxt; ?></h2>
        <?php if(isset($message) && $message != ''): ?>
        <div class="notice notice-info is-dismissible">
            <p><strong><?php echo $message; ?></strong></p>
        </div>
		<?php endif; ?>
		<div id="mst_list_members" class="mst_listing mst_list_members">
			<div class="wptl_container">
				<div class="wrap">

					<form enctype="multipart/form-data" method="post" name="adminForm" id="adminForm">
						<table class="adminform">
						<?php
							$task = 'saveimport';
							if(!$users){
								$task = 'startimport';
								?>
								<tr><th colspan="3" style="text-align:left;"><?php _e( 'Import Source' ); ?></th></tr>
								<tr>
									<td width="15px" style="text-align:right;">
										<input type="radio" name="datasource" value="local_file" id="local_file" checked="checked" />
									</td>
									<td width="100px" style="text-align:left;">
										<label for="filepath_local">
											<?php _e( 'Upload file (local)' ); ?>:
										</label>
									</td>
									<td width="200px">
										<input class="input_box" id="filepath_local" name="filepath_local" type="file" size="57" />
									</td>
								</tr>
								<tr>
									<td width="15px" style="text-align:right;">
										<input type="radio" name="datasource" value="server_file" id="server_file" />
									</td>
									<td width="100px" style="text-align:left;">
										<label for="filepath" title="<?php _e("Path relative to WordPress's base directory (e.g. /wp-content/uploads/importme.csv means that the file importme.csv is in WordPress's uploads folder)", "wpmst-mailster"); ?>">
											<?php _e( 'File Path (server)', "wpmst-mailster" ); ?>:
										</label>
									</td>
									<td width="200px" style="text-align:left;">
										<input class="inputbox" name="filepath" value="/wp-content/uploads/users.csv" size="30" maxlength="255" id="filepath" title="<?php _e("Path relative to WordPress's base directory (e.g. /wp-content/uploads/importme.csv means that the file importme.csv is in WordPress's uploads folder)", "wpmst-mailster"); ?>"/>
									</td>
								</tr>
								<tr>
									<td width="100px" style="text-align:right;" colspan="2">
										<label for="delimiter" title="<?php _e('Character/letter separating name and email column', 'wpmst-mailster' );?>">
											<?php _e( 'Delimiter', 'wpmst-mailster' ); ?>:
										</label>
									</td>
									<td width="200px">
										<input class="inputbox" name="delimiter" value=";" size="3" maxlength="5" id="delimiter" title="<?php _e('Character/letter separating name and email column', 'wpmst-mailster' );?>"/>
									</td>
								</tr>
								<tr>
									<td width="100px" style="text-align:right;" colspan="2">
										<label for="dataorder" title="<?php _e( 'Order of the columns (name, email) in the CSV file', 'wpmst-mailster' ); ?>">
											<?php _e( 'Data order in CSV file', 'wpmst-mailster' ); ?>:
										</label>
									</td>
									<td width="200px">
										<input type="radio" name="dataorder" value="name_del_email" id="name_del_email" checked="checked" />
										<label for="name_del_email"><?php _e( 'Name [delimiter] Email', 'wpmst-mailster' ); ?></label>
									</td>
								</tr>
								<tr>
									<td width="100px" colspan="2">&nbsp;</td>
									<td width="200px">
										<input type="radio" name="dataorder" value="email_del_name" id="email_del_name" />
										<label for="email_del_name"><?php _e( 'Email [delimiter] Name', 'wpmst-mailster' ); ?></label>
									</td>
								</tr>
								<?php
							} else {
								?>
								<tr><th><?php _e( 'Import Result' ); ?></th></tr>
								<tr>
									<td><?php
                                        $userCount = count( $users );
                                        $maxInputVars = ini_get('max_input_vars');
                                        if($maxInputVars > 0 && $maxInputVars > $inputVarOffset){
                                            $userCount = $maxInputVars - $inputVarOffset; // we need some variables for the control handling...
                                            $userCount = floor($userCount/2); // we need two (name + email) inputs per user entry
                                        }
                                        echo $userCount . ' ' . __( 'User data sets found', 'wpmst-mailster' ); ?></td>
								</tr>
								<?php
							}
						?>
						</table>
						<table>
							<tr>
								<th colspan="5" style="text-align:left;"><?php _e( 'Import Options', 'wpmst-mailster' ); ?></th>
							</tr>
							<tr>
								<td width="100px" style="text-align:right;" colspan="2">
									<label for="duplicateopt"  title="<?php _e( 'Decide how duplicate user data records are treated if they occur', 'wpmst-mailster' );?>">
										<?php _e( 'Options for duplicates', 'wpmst-mailster' ); ?>:
									</label>
								</td>
								<td width="200px" colspan="2">
									<input type="radio" name="duplicateopt" value="merge" id="merge" checked="checked" />
									<label for="merge"><?php _e( 'Merge user data (no duplicates)', 'wpmst-mailster' ); ?></label>
								</td>
								<td>&nbsp;</td>
								<td>&nbsp;</td>
							</tr>
							<tr>
								<td width="100px" style="text-align:right;" colspan="2">&nbsp;</td>
								<td width="200px" colspan="2">
									<input type="radio" name="duplicateopt" value="ignore" id="ignore" />
									<label for="ignore"><?php _e( 'Ignore (don\'t avoid duplicates)', 'wpmst-mailster' ); ?></label>
								</td>
								<td>&nbsp;</td>
								<td>&nbsp;</td>
							</tr>
							<tr>
								<td width="100px" style="text-align:right;" colspan="2">
									<label for="importtask"  title="<?php _e( 'Specify if the users should be directly added to a group or a mailing list (optional)', 'wpmst-mailster' );?>">
										<?php _e( 'Import users and...', 'wpmst-mailster' ); ?>:
									</label>
								</td>
								<td width="200px">
									<input type="radio" name="importtask" value="importonly" id="importonly" checked="checked" />
									<label for="importonly"><?php _e( ' Nothing else (import only)', 'wpmst-mailster'); ?></label>
								</td>
								<td>&nbsp;</td>
								<td>&nbsp;</td>
							</tr>
							<tr>
								<td width="100px" colspan="2">&nbsp;</td>
								<td>
									<input type="radio" name="importtask" value="add2group" id="add2group" />
									<label for="add2group"><?php _e( 'Add to group', 'wpmst-mailster' ); ?></label>
								</td>
								<td>
									<label for="targetgroup"><?php _e( 'Choose Group', 'wpmst-mailster' ); ?></label><br/>
									<select id="targetgroup" name="targetgroup" style="width:180px" disabled="disabled">
										<option value="0" selected="selected"><?php echo '< ' . __( 'New Group', 'wpmst-mailster' ) . ' >'; ?></option>
										<?php
											for($i=0, $n=count( $groups ); $i < $n; $i++) {
												$group = &$groups[$i];
												?>
												<option value="<?php echo $group->id; ?>"><?php echo $group->name; ?></option>
												<?php
											}
										?>
									</select>
								</td>
								<td><?php _e( 'New Group Name', 'wpmst-mailster' ); ?><br/><input type="text" name="newgroupname" value="<?php echo ($users ? $newgroupname : __( 'New Group', 'wpmst-mailster' )); ?>" id="newgroupname" disabled="disabled" /></td>
							</tr>
							<tr>
								<td width="100px" colspan="2">&nbsp;</td>
								<td width="200px">
									<input type="radio" name="importtask" value="add2list" id="add2list" />
									<?php _e( 'Add as recipients', 'wpmst-mailster' ); ?>
								</td>
								<td width="200px" rowspan="3">
									<label for="targetlist"><?php _e( 'Choose a mailing list', 'wpmst-mailster' ); ?></label><br/>
									<select id="targetlist" name="targetlist" style="width:180px" disabled="disabled">
										<?php
											for($i=0, $n=count( $mailLists ); $i < $n; $i++) {
												$mailingList = &$mailLists[$i];
												$selected = ($i==0 ? 'selected="selected"' : '');
												?>
												<option value="<?php echo $mailingList->id; ?>" <?php echo $selected; ?>><?php echo $mailingList->name; ?></option>
												<?php
											}
										?>
									</select>
								</td>
								<td>&nbsp;</td>
							</tr>
							<?php
							if(!$users){
								?>
							<tr>
								<td colspan="3">&nbsp;</td>
								<td width="100px" style="text-align:right;">
									<input type="submit" name="submitbutton" value="<?php _e( 'Import Now', 'wpmst-mailster' ); ?>" id="submitbutton" class="submitButton" />
								</td>
								<td>&nbsp;</td>
							</tr>
							<?php } else { ?>
							<tr><td colspan="5">&nbsp;</td></tr>
							<tr><td colspan="3">&nbsp;</td><td><input type="submit" value="<?php _e( 'Save New Users', 'wpmst-mailster' ); ?>" class="submitButton" style="background-color:green;" /></td><td>&nbsp;</td></tr>
							<tr><th colspan="3"><?php _e( 'Preview Users to Import', 'wpmst-mailster' ); ?></th><td>&nbsp;</td></tr>
							<tr><th>&nbsp;</th><th><?php _e( 'Name', 'wpmst-mailster' ); ?></th><th><?php _e( 'Email', 'wpmst-mailster' ); ?></th><th>&nbsp;</th></tr>
							<?php
								$n=count( $users );
                                $maxInputVars = ini_get('max_input_vars');
                                if($maxInputVars > 0 && $maxInputVars > $inputVarOffset){
                                    $maxInputVars = $maxInputVars - $inputVarOffset; // we need some variables for the control handling...
                                    $maxInputVars = floor($maxInputVars/2); // we need two (name + email) inputs per user entry
                                }else{
                                    $maxInputVars = ini_get('max_input_vars');
                                }
								if($n > 1) {
									for ( $i = 0; $i < $n; $i ++ ) {
										$user = $users[ $i ];
										?>
										<tr>
											<td style="text-align:right;"><?php echo( $i + 1 ); ?></td>
											<td><input name="name<?php echo $i; ?>" value="<?php echo $user['name'] ?>"
													   size="50" maxlength="255"/></td>
											<td><input name="email<?php echo $i; ?>"
													   value="<?php echo $user['email'] ?>" size="50" maxlength="255"/>
											</td>
											<td>&nbsp;</td>
										</tr>
										<?php
                                        if(($i+1) >= $maxInputVars){
                                            break; // do not continue further if too much input variables would be present
                                        }
									}
								}
							}
							?>
						</table>
						<input type="hidden" name="task" value="<?php echo $task; ?>" />
						<input type="hidden" name="usercount" value="<?php echo count( $users ); ?>" />
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
	<script type="text/javascript">
		var $j = jQuery.noConflict();
		$j(document).ready(
			function () {
				var usersImported = '<?php echo ($users ? 'true' : 'false'); ?>';
				if(usersImported == 'true'){
					var importTask = '<?php echo $importtask; ?>';
					var duplicateopt = '<?php echo $duplicateopt; ?>';
					var selectCase = false;
					if(importTask == 'add2group'){
						$j('#add2group').attr('checked', 'checked');
						var targetValue = '<?php echo $targetgroup; ?>';
						var targetElement = '#targetgroup';
						selectCase = true;
					}else if(importTask == 'add2list'){
						$j('#add2list').attr('checked', 'checked');
						var targetValue = '<?php echo $targetlist; ?>';
						var targetElement = '#targetlist';
						selectCase = true;
					}
					if(selectCase == true){
						$j(targetElement + ' option').each(function(){
							var value = $j(this).attr("value");
							if(value == targetValue)
							{
								$j(this).attr("selected", "selected");
							}
						});
					}
					if(duplicateopt == 'ignore'){
						$j('#ignore').attr('checked', 'checked');
					}else{
						$j('#merge').attr('checked', 'checked');
					}
				}else{
					var importTarget = '<?php echo ($importtarget ? $importtarget : ''); ?>';
					if(importTarget != ''){
						if(importTarget == 'add2group'){
							$j('#add2group').attr('checked', 'checked');
						}else if(importTarget == 'add2list'){
							$j('#add2list').attr('checked', 'checked');
						}else{
							$j('#importonly').attr('checked', 'checked');
						}
						toggleImportTarget(importTarget);
					}
				}
				toggleImportTarget(importTask);
				toggleImportSource('local_file');

				$j('#importonly').click(function () {
					if(this.checked == true){
						toggleImportTarget('importonly');
					}
				});

				$j('#add2group').click(function () {
					if(this.checked == true){
						toggleImportTarget('add2group');
					}
				});

				$j('#add2list').click(function () {
					if(this.checked == true){
						toggleImportTarget('add2list');
					}
				});

				$j('#targetgroup').click(function () {
					if(this.value == 0){
						$j('#newgroupname').removeAttr('disabled');
					}else{
						$j('#newgroupname').attr('disabled',  'disabled');
					}
				});

				$j('#local_file').click(function () {
					if(this.checked == true){
						toggleImportSource('local_file');
					}
				});

				$j('#server_file').click(function () {
					if(this.checked == true){
						toggleImportSource('server_file');
					}
				});

			});

		function toggleImportTarget(importTarget){
			if(importTarget == 'importonly'){
				$j('#newgroupname').attr('disabled',  'disabled');
				$j('#targetgroup').attr('disabled',  'disabled');
				$j('#targetlist').attr('disabled',  'disabled');
			}else if(importTarget == 'add2group'){
				if($j('#targetgroup').val() == 0){
					$j('#newgroupname').removeAttr('disabled');
				}else{
					$j('#newgroupname').attr('disabled',  'disabled');
				}
				$j('#targetgroup').removeAttr('disabled');
				$j('#targetlist').attr('disabled',  'disabled');
			}else if(importTarget == 'add2list'){
				$j('#newgroupname').attr('disabled',  'disabled');
				$j('#targetgroup').attr('disabled',  'disabled');
				$j('#targetlist').removeAttr('disabled');
			}
		}

		function toggleImportSource(importSource){
			if(importSource == 'local_file'){
				$j('#filepath_local').removeAttr('disabled');
				$j('#filepath').attr('disabled',  'disabled');
			}else if(importSource == 'server_file'){
				$j('#filepath_local').attr('disabled',  'disabled');
				$j('#filepath').removeAttr('disabled');
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

<?php

	function wpmst_parse_file_errors($file = ''){

		$result = array();
		$result['error'] = 0;

		if($file['error']){
			$result['error'] = "No file uploaded or there was an upload error!";
			return $result;
		}
		if(($file['size'] > wp_max_upload_size())){
			$result['error'] = 'Your file was ' . $file['size'] . ' bytes! It must not exceed ' . wp_max_upload_size() . ' bytes.';
		}
		return $result;
	}

	/**
	 * logic for cancel an action
	 */
	function cancel()
	{
		// TODO get Params to determine where to return to (e.g. users, groups, list....)
		//$this->setRedirect( 'index.php?option=com_mailster&view=groupusers' );
	}

	function reviewimports() {
		import();
	}