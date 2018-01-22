<?php
	if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
		die('These are not the droids you are looking for.');
	}
	/**
	 * @copyright (C) 2016 - 2017 Holger Brandt IT Solutions
	 * @license GNU/GPL, see license.txt
	 * WP Mailster is free software; you can redistribute it and/or
	 * modify it under the terms of the GNU General Public License 2
	 * as published by the Free Software Foundation.
	 *
	 * WP Mailster is distributed in the hope that it will be useful,
	 * but WITHOUT ANY WARRANTY; without even the implied warranty of
	 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	 * GNU General Public License for more details.
	 *
	 * You should have received a copy of the GNU General Public License
	 * along with WP Mailster; if not, write to the Free Software
	 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA
	 * or see http://www.gnu.org/licenses/.
	 */

	$log		= MstFactory::getLogger();
	$mstUtils 	= MstFactory::getUtils();
	$mstConfig	= MstFactory::getConfig();
	$env 		= MstFactory::getEnvironment();
	$dbUtils 	= MstFactory::getDBUtils();
	$fileUtils 	= MstFactory::getFileUtils();
	global $wpdb;
	$appName = MstFactory::getV()->getProductName();
	global $wp_version;
	require_once plugin_dir_path( __FILE__ )."../../models/MailsterModelMailster.php";
	$Mailster = new MailsterModelMailster();
	$data = $Mailster->getData();
?>
	<table class="adminform">
		<tr><th><?php _e( 'System Properties', 'wpmst-mailster' ); ?></th><th></th><th>&nbsp;</th></tr>
		<tr>
			<td width="150px" style="text-align:right;"><?php echo $appName; ?>:</td>
			<td width="450px"><?php echo MstFactory::getV()->getProductVersion(true); ?></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td width="150px" style="text-align:right;"><?php _e( 'Date', 'wpmst-mailster' ); ?>:</td>
			<td width="450px"><?php echo MstFactory::getV()->getProductDate(); ?></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td width="150px" style="text-align:right;"><?php _e( 'Operating System',  'wpmst-mailster' ); ?>:</td>
			<td width="450px"><?php echo php_uname(); ?></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td width="150px" style="text-align:right;"><?php _e( 'PHP version', 'wpmst-mailster' ); ?>:</td>
			<td width="450px"><?php echo phpversion(); ?> </td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td width="150px" style="text-align:right;"><?php _e( 'Max. Execution Time', 'wpmst-mailster' ); ?>:</td>
			<td width="450px"><?php echo ini_get('max_execution_time'); ?></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td width="150px" style="text-align:right;"><?php _e( 'Memory Limit', 'wpmst-mailster' ); ?>:</td>
			<td width="450px"><?php echo ini_get('memory_limit'); ?> (<?php echo $fileUtils->getFileSizeStringForSizeInBytes(memory_get_peak_usage(true)); ?>)</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td width="150px" style="text-align:right;"><?php echo 'parse_ini_file'; ?>:</td>
			<td width="450px"><?php echo function_exists('parse_ini_file') ? 'OK' : 'ERROR - parse_ini_file DISABLED!'; ?> </td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td width="150px" style="text-align:right;"><?php echo 'PHP Disabled Functions'; ?>:</td>
			<td width="450px"><?php
					$disabled_functions = ini_get('disable_functions');
					if ($disabled_functions!='')
					{
						$arr = explode(',', $disabled_functions);
						sort($arr);
						echo implode(', ', $arr);
					}else{
						_e('None', 'wpmst-mailster');
					}
				?> </td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td width="150px" style="text-align:right;"><?php _e( 'Database version', 'wpmst-mailster' ); ?>:</td>
			<td width="450px"><?php echo $wpdb->db_version(); ?> </td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td width="150px" style="text-align:right;"></td>
			<td width="450px">
				<?php
					function mstCheck_imap_mime_header_decode(){
						$convUtils = MstFactory::getConverterUtils();
						$sub = 'Umlaut =?ISO-8859-15?Q?=FCberall_Test?=';
						$exp = $convUtils->imapUtf8('Umlaut überall Test');
						$subConv = $convUtils->getStringAsNativeUtf8($sub);
						if(($subConv) !== ($exp)){
							echo 'imap_mime_header_decode (ERROR)<br/>';
							echo 'Expected: ' . htmlentities($exp) . '<br/>';
							echo 'Actual (imap_mime_header_decode): ' . htmlentities($subConv) . '<br/><br/>';
							echo '<pre>'.print_r(imap_mime_header_decode($sub), true).'</pre><br/>';
							echo 'imapUtf8 ('.($convUtils->imapUtf8($sub) === ($exp) ? 'OK' : 'ERROR').')<br/>';
							echo 'Actual (imapUtf8): ' . htmlentities($convUtils->imapUtf8($sub)). '<br/>';
						}else{
							echo 'imap_mime_header_decode (Test OK)';
						}
					}
					mstCheck_imap_mime_header_decode();
				?>
				<br/>
			</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td width="150px" style="text-align:right;"><?php _e( 'WordPress version', 'wpmst-mailster' ); ?>:</td>
			<td width="450px"><?php echo $wp_version; ?> </td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td width="150px" style="text-align:right;"><?php _e( 'Site Url', 'wpmst-mailster' ); ?>:</td>
			<td width="450px"><?php echo get_site_url(); ?></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td width="150px" style="text-align:right;"><?php _e( 'IMAP', 'wpmst-mailster' ); ?>:</td>
			<td width="450px"><?php echo $env->imapExtensionInstalled() ? __( 'Loaded', 'wpmst-mailster' ) : __( 'No', 'wpmst-mailster' ); ?></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td width="150px" style="text-align:right;">&nbsp;</td>
			<td width="450px"><?php echo __( 'Open mailbox timeout', 'wpmst-mailster' ) . ': ' . $mstConfig->getMailboxOpenTimeout() . ' (' . __( 'Global setting', 'wpmst-mailster' ) . ': ' .imap_timeout(IMAP_OPENTIMEOUT) . ')'; ?></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td width="150px" style="text-align:right;">&nbsp;</td>
			<td width="450px"><pre><?php echo $env->getImapVersion(); ?></pre></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td width="150px" style="text-align:right;"><?php _e( 'OpenSSL', 'wpmst-mailster' ); ?>:</td>
			<td width="450px"><?php echo $env->openSSLExtensionInstalled() ? __( 'Loaded', 'wpmst-mailster' ) : __( 'No', 'wpmst-mailster' ); ?></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td width="150px" style="text-align:right;">&nbsp;</td>
			<td width="450px"><pre><?php echo $env->getOpenSSLVersion(); ?></pre></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td width="150px" style="text-align:right;vertical-align:top;"><?php _e( 'Statistics', 'wpmst-mailster' ); ?>:</td>
			<td width="450px">
				<table cellspacing="10">
					<tr>
						<th colspan="5"><?php _e( 'General stats', 'wpmst-mailster' ); ?>: </th>
					</tr>
					<tr>
						<td><?php _e( 'Last email sent', 'wpmst-mailster' ); ?>: </td>
						<td><?php echo $mstConfig->getLastMailSentAt(); ?></td>
						<td colspan="3">&nbsp;</td>
					</tr>
					<tr>
						<td><?php echo __( 'Last hour mails sent in', 'wpmst-mailster' ) . ' ('. __('day', 'wpmst-mailster').'): '; ?></td>
						<td><?php echo $mstConfig->getLastHourMailSentIn() . ' ('.$mstConfig->getLastDayMailSentIn().')'; ?></td>
						<td colspan="3">&nbsp;</td>
					</tr>
					<tr>
						<td><?php echo _e( 'Number of mails sent in last hour', 'wpmst-mailster' ) . ' ('. __('day', 'wpmst-mailster').'): '; ?></td>
						<td><?php echo $mstConfig->getNrOfMailsSentInLastHour(). ' ('.$mstConfig->getNrOfMailsSentInLastDay().')'; ?></td>
						<td colspan="3">&nbsp;</td>
					</tr>
					<tr>
						<td colspan="5">&nbsp;</td>
					</tr>
					<tr>
						<th colspan="5"><?php _e( 'Mailing lists', 'wpmst-mailster' ); ?>: </th>
					</tr>
					<tr>
						<td><?php _e( 'Name', 'wpmst-mailster' ); ?></td>
						<td><?php _e( 'Last check', 'wpmst-mailster' ); ?></td>
						<td><?php _e( 'Last email retrieved', 'wpmst-mailster' ); ?></td>
						<td><?php _e( 'Last email sent', 'wpmst-mailster' ); ?></td>
						<td><?php _e( 'ID', 'wpmst-mailster' ); ?></td>
					</tr>
					<?php
						$lists = &$data->lists;
						for( $i=0; $i < count( $lists ); $i++ )	{
							$mList = &$lists[$i];
							?>
							<tr>
								<td><?php echo $mList->name; ?></td>
								<td><?php echo $mList->last_check; ?></td>
								<td><?php echo $mList->last_mail_retrieved; ?></td>
								<td><?php echo $mList->last_mail_sent; ?></td>
								<td><?php echo $mList->id; ?></td>
							</tr>
							<?php
						}
					?>
				</table>
			</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td width="150px" style="text-align:right;vertical-align:top;"><?php _e( 'Logging', 'wpmst-mailster' ); ?>:</td>
			<td width="450px">
				<table>
					<tr>
						<td><?php _e( 'Logging enabled', 'wpmst-mailster' ); ?>: </td>
						<td><?php echo ($log->isLoggingActive() ? __( 'yes', 'wpmst-mailster' ) : __( 'no', 'wpmst-mailster' )); ?></td>
					</tr>
					<tr>
						<td><?php _e( 'Logging possible', 'wpmst-mailster' ); ?>: </td>
						<td><?php echo ($log->loggingPossible() ? __( 'yes', 'wpmst-mailster' ) : __( 'no', 'wpmst-mailster' )); ?></td>
					</tr>
					<tr>
						<td><?php _e( 'Force logging', 'wpmst-mailster' ); ?>: </td>
						<td><?php echo ($log->isLoggingForced() ?  __( 'yes', 'wpmst-mailster' ) : __( 'no', 'wpmst-mailster' )); ?></td>
					</tr>
					<tr>
						<td><?php _e( 'Logging Level', 'wpmst-mailster' ); ?>: </td>
						<td><?php echo get_option('logging_level'); ?></td>
					</tr>
					<tr>
						<td><?php _e( 'Log destination', 'wpmst-mailster' ); ?>: </td>
						<td><?php echo ( ($log->isLog2Database() && $log->isLog2File() )? __( 'Database and file', 'wpmst-mailster' ) : ($log->isLog2Database() ? __( 'Database', 'wpmst-mailster' ) : ($log->isLog2File() ? __( 'File', 'wpmst-mailster' ) : __( 'Error', 'wpmst-mailster' )))); ?></td>
					</tr>
					<tr>
						<td style="vertical-align:top;"><?php _e( 'Log file size', 'wpmst-mailster' ); ?>: </td>
						<td><?php echo $fileUtils->getFileSizeOfFile($log->getLogFile()) . ' ('.$log->getLogFile().')'; ?><br/>
							<a href="<?php echo admin_url('admin.php?mst_download=mailster.log'); ?>" target="_blank"><?php _e( 'Download', 'wpmst-mailster' );?></a><br/>
							<a href="#" id="deleteLog"><?php _e( 'Delete log file', 'wpmst-mailster' );?></a>
						</td>
					</tr>
					<tr>
						<td><?php _e( 'Log database entries', 'wpmst-mailster' ); ?>: </td>
						<td><?php echo $log->getLogDatabaseEntriesCount(); ?></td>
					</tr>
				</table>
			</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td width="150px" style="text-align:right;"><?php _e( 'Connection Check', 'wpmst-mailster' ); ?>:</td>
			<td width="450px">
				<pre><?php
						require_once('mst_inbox_test.php');
					?>
				</pre>
			</td>
			<td>&nbsp;</td>
		</tr>
	</table>