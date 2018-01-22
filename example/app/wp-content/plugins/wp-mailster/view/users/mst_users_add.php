<?php
	if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
		die( 'These are not the droids you are looking for.' );
	}
require_once $this->WPMST_PLUGIN_DIR."/models/MailsterModelUser.php";
require_once $this->WPMST_PLUGIN_DIR."/models/MailsterModelList.php";
require_once $this->WPMST_PLUGIN_DIR."/models/MailsterModelGroup.php";

$is_core_user = (isset($_GET['core']) && $_GET['core']!='')?intval($_GET['core']):0;
$sid   = (isset($_GET['sid']) && $_GET['sid']!='')?intval($_GET['sid']):'';

if ( ! $sid ) {
	if ( isset( $_POST['sid'] ) ) {
		$sid = intval($_POST['sid']);
	}
}
if ( ! $is_core_user ) {
	if ( isset( $_POST['core'] ) ) {
		$is_core_user = intval($_POST['core']);
	}
}

$title  = __("New Mailster User", 'wpmst-mailster');
$button = __('Add Mailster User', 'wpmst-mailster');
$action = 'add';
$message = "";
$options = array();

$User = null;
if($sid) {
	$User = new MailsterModelUser($sid, $is_core_user);
} else {
	$User = new MailsterModelUser();
}
if( isset( $_POST['user_action'] ) ) { //if form is submitted	
	if ( isset( $_POST['add_user'] ) ) {
		if( $_POST['sid'] ) {
			$user_options['id'] = intval($_POST['sid']);
		}
		if ( ! $is_core_user ) { //save user data only for Mailster users
			$user_options['name'] = sanitize_text_field($_POST['user_name']);
			$user_options['email'] = sanitize_email($_POST['email']);
			$user_options['notes'] = sanitize_text_field($_POST['notes']);
			$success = $User->saveData($user_options, sanitize_text_field($_POST['add_user']));
			$sid = $User->getId();
		}

		if( isset( $_POST[ 'is_group_member' ] ) ) {
			foreach( $_POST['is_group_member'] as $groupid => $groupmember ) {
				if($groupmember) {
					$User->addToGroup(intval($groupid));
				} else {
					$User->removeFromGroup(intval($groupid));
				}
			}
		}
		if(isset($_POST['is_list_member'])) {
			foreach( $_POST['is_list_member'] as $listid => $listmember ) {
				if($listmember) {
					$User->addToList(intval($listid));
				} else {
					$User->removeFromList(intval($listid));
				}
			}
		}
	}
}	
$values = null;
if($sid) {
	$title = __("Edit Mailster User", 'wpmst-mailster');
	$button = __('Update Mailster User', 'wpmst-mailster');
	$action = 'edit'; 
}
$options = $User->getFormData();

$List = new MailsterModelList();
$lists = $List->getAll();
$Group = new MailsterModelGroup();
$groups = $Group->getAll();

?>
<div class="mst_container">
	<div class="wrap">
		<h2><?php echo $title; ?></h2>
		<?php echo (isset($message) && $message!=''?$message:'');?>
		<form action="" method="post">
			<?php wp_nonce_field( 'add-user_'.$sid ); ?>
			<table class="form-table">
				<tbody>
					<?php
					$this->mst_display_hidden_field("sid", $sid);
					$this->mst_display_hidden_field("core", $is_core_user);
					$this->mst_display_hidden_field("add_user", $action);
					if( ! $is_core_user ) {
						$this->mst_display_input_field( __("Name", 'wpmst-mailster'), 'user_name', $options->name, null, false, false, null );
						$this->mst_display_input_field( __("Email", 'wpmst-mailster'), 'email', $options->email, null, false, false, null );
						$this->mst_display_input_field( __("Description", 'wpmst-mailster'), 'notes', $options->notes, null, false, false, null );
					} else {
                        $userData = MstFactory::getUserModel()->getUserData($sid, $is_core_user);
                    ?>
                        <h3><?php echo $userData->name.' ('.$userData->email.')' ?> <a href="<?php echo get_edit_user_link( $sid ); ?>"><?php _e("Edit WordPress User", "wpsmt-mailster"); ?></a></h3>

					<?php } ?>
				</tbody>
			</table>
			<input type="submit" class="button-primary" name="user_action" value="<?php echo $button; ?>">
		
			<?php if( $sid ) { ?>
			<h4><?php _e("User member of Groups", 'wpmst-mailster'); ?></h4>
			<table>
				<tr>
					<th><?php _e("Group Name", 'wpmst-mailster'); ?></th>
					<th><?php _e("Group Member", 'wpmst-mailster'); ?></th>
				</tr>
				<?php
				$i=0;
				foreach($groups as $group) { ?>
				<tr>
					<td>
						<?php echo $group->name; ?>
					</td>
					<td>
					<?php
					$checked = false;
					if( $User->isUserInGroup($sid, $is_core_user, $group->id) ) {
						$checked = true;
					}
					$this->mst_display_simple_radio_field('is_group_member[' . $group->id . ']', 1, 'is_group_member'.$i.'1', __("Yes", 'wpmst-mailster'), $checked, false);
					$this->mst_display_simple_radio_field('is_group_member[' . $group->id . ']', 0, 'is_group_member'.$i.'0', __("No", 'wpmst-mailster'), !$checked, false);
					$i++;	
					?>
					</td>
				</tr>
					<?php
				}
				?>
			</table>

			<h4><?php _e("User member of Lists", 'wpmst-mailster'); ?></h4>
			<table>
				<tr>
					<th><?php _e("List Name", 'wpmst-mailster'); ?></th>
					<th><?php _e("List Member", 'wpmst-mailster'); ?></th>
				</tr>
				<?php
				$i=0;
				foreach($lists as $list) { ?>
				<tr>
					<td>
						<?php echo $list->name; ?>
					</td>
					<td>
					<?php
					$checked = false;
					if( $User->isUserInList($sid, $is_core_user, $list->id) ) {
						$checked = true;
					}
					$this->mst_display_simple_radio_field('is_list_member[' . $list->id . ']', 1, 'is_list_member'.$i.'1', __("Yes", 'wpmst-mailster'), $checked, false);
					$this->mst_display_simple_radio_field('is_list_member[' . $list->id . ']', 0, 'is_list_member'.$i.'0', __("No", 'wpmst-mailster'), !$checked, false);
					$i++;	
					?>
					</td>
				</tr>
					<?php
				}
				?>
			</table>
			<?php } ?>
			

		</form>
	</div>
</div>