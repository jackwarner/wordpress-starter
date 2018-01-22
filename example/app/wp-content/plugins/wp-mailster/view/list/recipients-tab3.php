<?php if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
	die( 'These are not the droids you are looking for.' );
}
?>
<div class="mst_container">
	<div class="wrap">
		<h4>
			<?php _e("List Groups", 'wpmst-mailster');  ?>
			<a href="?page=mst_mailing_lists&amp;subpage=managegroups&amp;lid=<?php echo $lid; ?>" class="add-new-h2"><?php _e( "Edit List Groups", "wpmst-mailster" ); ?></a>
		</h4>			
			<table id="mst_table" class="wp-list-table widefat fixed striped posts">
				<thead>
					<tr>
						<td width="8%"><?php _e("Num", 'wpmst-mailster'); ?></td>
						<td><?php _e("Name", 'wpmst-mailster'); ?></td>
                        <td><?php _e("#Users in Group", 'wpmst-mailster'); ?></td>
					</tr>
				</thead>
			<tfoot>
				<tr>
					<td width="8%"><?php _e("Num", 'wpmst-mailster'); ?></td>
					<td><?php _e("Name", 'wpmst-mailster'); ?></td>
                    <td><?php _e("#Users in Group", 'wpmst-mailster'); ?></td>
				</tr>
			</tfoot>						
			<tbody id="the-list">
			<?php
			if( !empty( $listGroups ) ){
				$index = 1;
				foreach($listGroups as $listGroup){
					$gid = $listGroup->group_id;
					$current_group = new MailsterModelGroup($gid);
                    $groupUserCount = $current_group->getTotal();
					$groupData = $current_group->getFormData(); 
			?>
				<tr>
					<td class="post-title page-title column-title"><?php echo $index++; ?></td>
					<td><?php echo $groupData->name; ?></td>
                    <td><?php echo $groupUserCount; ?></td>
				</tr>
			<?php	
				}
			}
			?>
			</tbody>
		</table>
	</div>
</div>