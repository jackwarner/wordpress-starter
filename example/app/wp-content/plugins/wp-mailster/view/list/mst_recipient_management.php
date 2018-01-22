<?php
	if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
		die( 'These are not the droids you are looking for.' );
	}
global $wpdb;
$lid   = (isset($_GET['lid']) && $_GET['lid']!=''?intval($_GET['lid']):'');


include_once $this->WPMST_PLUGIN_DIR."/models/MailsterModelList.php";
include_once $this->WPMST_PLUGIN_DIR."/models/MailsterModelUser.php";
include_once $this->WPMST_PLUGIN_DIR."/models/MailsterModelGroup.php";

$list = null;
if($lid) {
	$list = new MailsterModelList($lid);
} else {
	$list = new MailsterModelList();
}
$User = new MailsterModelUser();
$Group = new MailsterModelGroup();

$all_members  =array();

//list members
$listUsers = $list->getAllListMembers();
if( ! empty( $listUsers ) ) {
	foreach ( $listUsers as $luval ) {
	    $all_members[] = array( $luval->user_id, $luval->is_core_user );
	}		
}

//list groups	
$listGroups = $list->getAllListGroups();
if( ! empty( $listGroups ) ) {
	foreach( $listGroups as $listGroup ) {
		$group_id = $listGroup->group_id;
		$group = new MailsterModelGroup($group_id);
		$groupUsers = $group->getAllUsers();
		foreach( $groupUsers as $groupUser ) {
			$all_members[] = array( $groupUser->user_id, $groupUser->is_core_user );
		}
	}
}

$recips = MstFactory::getRecipients();
$allRecipients = $recips->getRecipients($lid);

$listData = $list->getData();
?>
<div class="mst_container">
	<div class="wrap">
		<h2><?php echo $listData[0]->name . " - " . __("Mailing List Recipient Management", 'wpmst-mailster'); ?></h2>
		<?php echo (isset($message) && $message!=''?$message:'');?>
		<div id="tabs">
			<ul>
				<li><a href="#recipient_management"><?php _e("All Recipients", 'wpmst-mailster'); ?></a></li>
				<li><a href="#list_members"><?php _e("List Members", 'wpmst-mailster'); ?></a></li>
				<li><a href="#list_groups"><?php _e("List Groups", 'wpmst-mailster'); ?></a></li>
			</ul>
			
			<div id="recipient_management" class="mst_listing recipient_management">
				<?php require_once($this->WPMST_PLUGIN_DIR.'view/list/recipients-tab1.php'); ?>				
			</div>
			<div id="list_members">
				<?php require_once($this->WPMST_PLUGIN_DIR.'view/list/recipients-tab2.php'); ?>				
			</div>
			<div id="list_groups">
				<?php require_once($this->WPMST_PLUGIN_DIR.'view/list/recipients-tab3.php'); ?>				
			</div>
		</div>
		<a href="?page=mst_mailing_lists"><?php _e("back", "wpmst-mailster"); ?></a>
	</div>
</div>
<script>
jQuery(document).ready(function($) {
	$('#mst_table').DataTable({
		responsive: true,
		aaSorting: [[0, 'asc']]
	});
});
</script>
<script>
jQuery(document).ready(function($) {
	$( "#tabs" ).tabs();
});
</script>