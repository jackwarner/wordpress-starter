<?php if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
	die( 'These are not the droids you are looking for.' );
} ?>
<div class="mst_container">
	<div class="wrap">
		<h4>
			<?php _e("All Recipients", 'wpmst-mailster');  ?>
		</h4>
        <?php echo (MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_REC) < 100000) ? sprintf(__('%s recipients'), count($allRecipients).'&nbsp;&#47;&nbsp;'. MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_REC)).'<br/>'.'<br/>' : ''; ?>
			<table id="mst_table" class="wp-list-table widefat fixed striped posts">
				<thead>
					<tr>
						<td width="8%"><?php _e("Num", 'wpmst-mailster'); ?></td>
						<td><?php _e("Name", 'wpmst-mailster'); ?></td>
						<td><?php _e("Email", 'wpmst-mailster'); ?></td>
					</tr>
				</thead>
			<tfoot>
				<tr>
					<td width="8%"><?php _e("Num", 'wpmst-mailster'); ?></td>
					<td><?php _e("Name", 'wpmst-mailster'); ?></td>
					<td><?php _e("Email", 'wpmst-mailster'); ?></td>
				</tr>
			</tfoot>						
			<tbody id="the-list">
			<?php
			if( !empty( $allRecipients ) ){
				$index = 1;
				foreach($allRecipients as $recipient) {
			?>
				<tr>
					<td class="post-title page-title column-title"><?php echo $index++; ?></td>
					<td><?php
                        if( !$recipient->name || trim($recipient->name) == "" ) {
                            $userName = __("(no name)", "wpmst-mailster");
                        } else {
                            $userName = $recipient->name;
                        }
                        echo $userName; ?></td>
					<td><?php echo $recipient->email; ?></td>
				</tr>
			<?php	
				}
			}
			?>
			</tbody>
		</table>
	</div>
</div>