<?php
	if ( preg_match( '#' . basename( __FILE__ ) . '#', $_SERVER['PHP_SELF'] ) ) {
		die( 'These are not the droids you are looking for.' );
	}

	class mst_frontend_mailing_list extends wpmst_mailster {
        public static function mst_shortcode_not_available_mst_mailing_lists( $atts, $content = "" ) {
            return sprintf(__( "Shortcode %s not available in product edition %s", 'wpmst-mailster' ), '[mst_mailing_lists]', MstFactory::getV()->getProductName());
        }
        public static function mst_shortcode_not_available_mst_emails( $atts, $content = "" ) {
            return sprintf(__( "Shortcode %s not available in product edition %s", 'wpmst-mailster' ), '[mst_emails]', MstFactory::getV()->getProductName());
        }

		public static function mst_mailing_lists_frontend( $atts, $content = "" ) {
            $log = MstFactory::getLogger();
            $log->debug('Shortcode mst_mailing_lists_frontend atts: '.print_r($atts, true));
			ob_start();
            if ( isset( $_GET['subpage'] ) && $_GET['subpage'] == 'mailstermails' ) {
				include "mails.php";
			} else if ( isset( $_GET['subpage'] ) && $_GET['subpage'] == 'mailstermail' ) {
				include "mail.php";
			} else if ( isset( $_GET['subpage'] ) && $_GET['subpage'] == 'mailsterthreads' ) {
				include "threads.php";
			} else if ( isset( $_GET['subpage'] ) && $_GET['subpage'] == 'mailsterthread' ) {
				include "thread.php";
			} else {
				if( isset( $_GET['subpage'] ) && $_GET['subpage'] == 'subscribe' ) {
					//subscribe the user
					$log = MstFactory::getLogger();
					$subscrUtils = MstFactory::getSubscribeUtils();
					$user = wp_get_current_user();
					$listId = intval($_GET['listID']);
                    $userObj = MstFactory::getUserModel()->getUserData($user->ID, true);
                    $userName = (property_exists($userObj, 'name') && $userObj->name && !empty($userObj->name) && (strlen(trim($userObj->name))>0)) ? $userObj->name : $user->display_name;
					$success = $subscrUtils->subscribeUser($userName, $user->user_email, $listId, 0); // subscribing user...
					$subscrUtils->sendWelcomeOrGoodbyeSubscriberMsg($userName, $user->user_email, $listId, MstConsts::SUB_TYPE_SUBSCRIBE);
					if($success == false){
						$mstRecipients = MstFactory::getRecipients();
						$cr = $mstRecipients->getTotalRecipientsCount($listId);
						if($cr >= MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_REC)){
							$log->debug('Too many recipients!');
							_e( 'Cannot subscribe', "wpmst-mailster" );
							_e( 'Too many recipients (Product limit)', "wpmst-mailster" );
						}
					}else{
						// ####### TRIGGER NEW EVENT #######
						$mstEvents = MstFactory::getEvents();
						$mstEvents->userSubscribedOnWebsite($userName, $user->user_email, $listId);
						// #################################
						_e( 'Subscription successful', "wpmst-mailster" );
					}
				} else if( isset( $_GET['subpage'] ) && $_GET['subpage'] == 'unsubscribe' ) {
					//unsubscibe the user
					$subscrUtils = MstFactory::getSubscribeUtils();
					$user = wp_get_current_user();
					$listId = intval($_GET['listID']);
					$success = $subscrUtils->unsubscribeUser($user->user_email, $listId); // unsubscribing user...
					$tmpUser = $subscrUtils->getUserByEmail($user->user_email);
					$subscrUtils->sendWelcomeOrGoodbyeSubscriberMsg($tmpUser['name'], $user->user_email, $listId, MstConsts::SUB_TYPE_UNSUBSCRIBE);
					// ####### TRIGGER NEW EVENT #######
					$mstEvents = MstFactory::getEvents();
					$mstEvents->userUnsubscribedOnWebsite($user->user_email, $listId);
					if($success) {
						_e( 'Unsubscription successful', "wpmst-mailster" );
					} else {
						_e( 'There was a problem unsubscribing you from this list', "wpmst-mailster" );
					}
				}

				global $wpdb;
				$uid = ( isset( $_REQUEST['uid'] ) && $_REQUEST['uid'] != '' ? intval( $_REQUEST['uid'] ) : '' );
				$lid = ( isset( $_REQUEST['lid'] ) && $_REQUEST['lid'] != '' ? intval( $_REQUEST['lid'] ) : '' );
				$mailster_list = $wpdb->get_results( "SELECT id,name,admin_mail,active, allow_subscribe, allow_unsubscribe FROM " . $wpdb->prefix . "mailster_lists WHERE front_archive_access != 3" );
				MstFactory::getAuthorization();
				?>
				<div class="mst_container">
					<div class="wrap">
						<h4><?php _e( "Mailing Lists", 'wpmst-mailster' ); ?></h4>
						<form>
							<table id="mst_table" class="wp-list-table widefat fixed striped posts">
								<thead>
								<tr>
									<td><?php _e( "Name", 'wpmst-mailster' ); ?></td>
									<td><?php _e( "Mailing list email", 'wpmst-mailster' ); ?></td>
									<td><?php _e( "Emails", 'wpmst-mailster' ); ?></td>
									<td></td>
								</tr>
								</thead>
								<tfoot>
								<tr>
									<td><?php _e( "Name", 'wpmst-mailster' ); ?></td>
									<td><?php _e( "Mailing list email", 'wpmst-mailster' ); ?></td>
									<td><?php _e( "Emails", 'wpmst-mailster' ); ?></td>
									<td></td>
								</tr>
								</tfoot>
								<tbody id="the-list">
								<?php
									if ( ! empty( $mailster_list ) ) {
										foreach ( $mailster_list as $single_tm ) {
											if(MstAuthorization::userHasAccess($single_tm->id)) {
												$id = $single_tm->id;
												?>
												<tr>
													<td><?php echo $single_tm->name; ?></td>
													<td><?php echo $single_tm->admin_mail; ?></td>
													<td>
														<a href="?subpage=mailstermails&lid=<?php echo $id; ?>"><?php _e( "View emails", 'wpmst-mailster' ); ?></a><br/>
														<a href="?subpage=mailsterthreads&list_choice=<?php echo $id; ?>"><?php _e( "View emails in threads", 'wpmst-mailster' ); ?></a>
													</td>
													<td>

														<?php
														$actionPossible = false;
														$actionTitle = '';
														$actionLink = '';
														$backlink ="";
														$listUtils = MstFactory::getMailingListUtils();
														$list = $listUtils->getMailingList($id);

														if($listUtils->isSubscribed($id)){
															$actionTitle = __( 'Unsubscribe', "wpmst-mailster");
															$actionLink =  '?subpage=unsubscribe&listID='. $id.'&bl='.$backlink ;
															if($single_tm->allow_unsubscribe){
																$actionPossible = true;
															}
														}else{
															$actionTitle = __( 'Subscribe', "wpmst-mailster");
															$actionLink = '?subpage=subscribe&listID='. $id.'&bl='.$backlink ;
															if($single_tm->allow_subscribe){
																$actionPossible = true;
															}
														}
														if($actionPossible):
															?><a href="<?php echo $actionLink; ?>"><?php echo $actionTitle; ?></a>
														<?php endif;?>


													</td>
												</tr>
												<?php
													}
												}
											} else {
											?>
										<tr>
											<td align="center"
												colspan="5"><?php _e( "No record found", 'wpmst-mailster' ); ?>!
											</td>
										</tr>
										<?php } ?>
								</tbody>
							</table>
						</form>
						<input type='hidden' id='ajax_url' name='ajax_url'
							   value="<?php echo admin_url( 'admin-ajax.php' ) ?>">
						<button class="btn btn-primary" id="printclick"
								onclick="mst_print_Function()"><?php _e( "Print this page", 'wpmst-mailster' ); ?></button>
					</div>
				</div>
				<script type='application/javascript'>
					jQuery(document).ready(function ($) {
						$('#mst_table').DataTable({
							responsive: true,
							aaSorting: [[0, 'asc']],
							aoColumnDefs: [
								{"aTargets": [2], "bSortable": false},
								{"aTargets": [3], "bSortable": false},
								{"aTargets": [4], "bSortable": false}
							]
						});
					});
					function mst_print_Function() {
						window.print();
					}
				</script>
				<?php
			}
			return ob_get_clean();
		}

		public static function mst_emails_frontend( $atts, $content = "" ) {
            $log = MstFactory::getLogger();
            $log->debug('Shortcode mst_emails_frontend atts: '.print_r($atts, true));
			ob_start();
            if(is_array($atts) && array_key_exists('lid', $atts)){
			    $listId = (int)$atts['lid'];
            }else{
                $listId = 0;
            }
            if(is_array($atts) && array_key_exists('order', $atts)){
                $orderParam = strtolower(trim($atts['order']));
                switch($orderParam){
                    case 'asc':
                        $msgOrder = 'asc';
                        break;
                    case 'desc':
                        $msgOrder = 'rfirst'; // recent first
                        break;
                    case 'oldfirst':
                        $msgOrder = 'asc';
                        break;
                    case 'newfirst':
                        $msgOrder = 'rfirst';
                        break;
                    case 'oldestfirst':
                        $msgOrder = 'asc';
                        break;
                    case 'newestfirst':
                        $msgOrder = 'rfirst';
                        break;
                    case 'old':
                        $msgOrder = 'asc';
                        break;
                    case 'new':
                        $msgOrder = 'rfirst';
                        break;
                    case 'rfirst':
                        $msgOrder = 'rfirst';
                        break;
                    default:
                        $msgOrder = 'rfirst';
                }
            }else{
                $msgOrder = 'rfirst';
            }
			if ( isset( $_GET['subpage'] ) && $_GET['subpage'] == 'mailstermail' ) {
				include "mail.php";
			} else {
				include "mails.php";
			}
			return ob_get_clean();
		}

		public static function mst_profile() {
			ob_start();
			if ( isset( $_GET['subpage'] ) && $_GET['subpage'] == 'profile_subscribe' ) {
				include "profile_subscribe.php";
                include "profile.php";
			} else if ( isset( $_GET['subpage'] ) && $_GET['subpage'] == 'profile_unsubscribe' ) {
				include "profile_unsubscribe.php";
				include "profile.php";
			} else if ( isset( $_GET['subpage'] ) && $_GET['subpage'] == 'digest' ) {
				include "profile_digest.php";
				include "profile.php";
			} else {
				include "profile.php";
			}
			return ob_get_clean();
		}
	}