<?php if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
	die( 'These are not the droids you are looking for.' );
}
?>
<div class="mst_container">
	<div class="wrap">
		<h4>
			<?php _e("List Users", 'wpmst-mailster');  ?>
			<a href="?page=mst_mailing_lists&amp;subpage=managemembers&amp;lid=<?php echo $lid; ?>" class="add-new-h2"><?php _e( "Edit List Members", "wpmst-mailster" ); ?></a>
		</h4>
		<table id="mst_table" class="wp-list-table widefat fixed striped posts">
			<thead>
			<tr>
				<td width="8%"><?php _e("Num", 'wpmst-mailster'); ?></td>
				<td><?php _e("Name", 'wpmst-mailster'); ?></td>
				<td><?php _e("Email", 'wpmst-mailster'); ?></td>
                <td><?php _e("User Type", 'wpmst-mailster'); ?></td>
			</tr>
			</thead>
			<tfoot>
			<tr>
				<td width="8%"><?php _e("Num", 'wpmst-mailster'); ?></td>
				<td><?php _e("Name", 'wpmst-mailster'); ?></td>
				<td><?php _e("Email", 'wpmst-mailster'); ?></td>
                <td><?php _e("User Type", 'wpmst-mailster'); ?></td>
			</tr>
			</tfoot>
			<tbody id="the-list">
			<?php
			if( !empty( $listUsers ) ){
				$index = 1;

				foreach($listUsers as $single_tm){
					$id = $single_tm->user_id;
					$isCore = $single_tm->is_core_user;
					$User = new MailsterModelUser($id);
					$userData = $User->getUserData($id, $isCore);
                    if( !$userData->name || trim($userData->name) == "" ) {
                        $userName = __("(no name)", "wpmst-mailster");
                    } else {
                        $userName = $userData->name;
                    }
                    $editNonce = wp_create_nonce( 'mst_edit_user' );
                    $link = sprintf(
                        '<a href="?page=mst_users_add&amp;user_action=%s&amp;sid=%s&amp;core=%d&amp;_wpnonce=%s" title="'.__('Edit User', 'wpmst-mailster').'">%s</a>',
                        'edit',
                        $id,
                        $isCore,
                        $editNonce,
                        $userName
                    );
					?>
					<tr>
						<td class="post-title page-title column-title"><?php echo $index++; ?></td>
						<td><?php echo $link; ?></td>
						<td><?php echo $userData->email; ?></td>
                        <td><?php echo ($isCore === true || $isCore == 1) ? 'WordPress' : 'WP Mailster'; ?></td>
					</tr>
					<?php
				}
			}
			?>
			</tbody>
		</table>
	</div>
</div>