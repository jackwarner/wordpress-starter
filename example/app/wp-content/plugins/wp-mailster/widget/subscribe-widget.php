<?php
	if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
		die( 'These are not the droids you are looking for.' );
	}

// register widget
function register_MstSubscribe_Widget() {
	register_widget( 'MstSubscribe_Widget' );
}
add_action( 'widgets_init', 'register_MstSubscribe_Widget' );
/**
 * Adds MstSubscribe_Widget widget.
 */
class MstSubscribe_Widget extends WP_Widget {
	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		parent::__construct(
			'mstSubscribe_widget', // Base ID
			__('Mailster Subscribe/Unsubscribe', 'wpmst-mailster'), // Name
			array( 'description' => __( 'Display a form for subscribing to (or unsubscribing from) Mailster mailing lists', 'wpmst-mailster' ), ) // Args
		);
	}
	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
        $log = MstFactory::getLogger();
		$title = apply_filters( 'Mailing List Subscription', $instance['title'] );
		echo $args['before_widget'];
		if ( ! empty( $title ) ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		$subscrUtils = MstFactory::getSubscriberPlugin();

		$settings = array();

		if(isset($instance['subscriber_type'])) {
			$subscriberType = $instance['subscriber_type'];
		} else {
			$subscriberType = 1;
		}
		if(isset($instance['design_choice'])) {
			$designChoice = $instance['design_choice'];
		} else {
			$designChoice = 0;
		}
		if(isset($instance['prefix_class'])) {
			$cssPrefix = $instance['prefix_class'];
		} else {
			$cssPrefix = "";
		}
		if(isset($instance['list_label'])) {
			$listLabel = $instance['list_label'];
		} else {
			$listLabel = "";
		}
		if(isset($instance['list_choice'])) {
			$listChoice = $instance['list_choice'];
		} else {
			$listChoice = 0;
		}
		if(isset($instance['captcha'])) {
			$captcha = $instance['captcha'];
		} else {
			$captcha = 0;
		}
		if(isset($instance['hide_list_name'])) {
			$hideListName = $instance['hide_list_name'];
		} else {
			$hideListName = 0;
		}
		$digestChoice =  get_option('digest_choice', 0);
		$suggestUserData = get_option('suggest_user_data', 1);
		$enforceUserData = get_option('enforce_user_data', 0);
		$digestChoiceLabel =  get_option('digest_choice_label', __( 'Digest Choice' ));

		if($subscriberType == 1){
			$headerTxt = $instance['title'];
			if(isset($instance['hide_subscriber_name'])) {
				$hideSubscriberName = $instance['hide_subscriber_name'];
			} else {
				$hideSubscriberName = "";
			}
			if(isset($instance['subscription_group_choice'])) {
				$subscribeAdd2Group = $instance['subscription_group_choice'];
			} else {
				$subscribeAdd2Group = "";
			}
			if(isset($instance['subscriber_name'])) {
				$nameLabel = $instance['subscriber_name'];
			} else {
				$nameLabel = "";
			}
			if(isset($instance['subscriber_email'])) {
				$emailLabel = $instance['subscriber_email'];
			} else {
				$emailLabel = "";
			}
			if(isset($instance['subscribe_button'])) {
				$buttonTxt = $instance['subscribe_button'];
			} else {
				$buttonTxt = "";
			}
			if(isset($instance['subscribe_thank_msg'])) {
				$submitTxt = $instance['subscribe_thank_msg'];
			} else {
				$submitTxt = "";
			}
			if(isset($instance['subscribe_confirm_email_msg'])) {
				$submitConfirmTxt = $instance['subscribe_confirm_email_msg'];
			} else {
				$submitConfirmTxt = "";
			}
			if(isset($instance['subscribe_error_msg'])) {
				$errorTxt = $instance['subscribe_error_msg'];
			} else {
				$errorTxt = "";
			}
			if(isset($instance['subscriber_smart_hide'])) {
				$smartHide = $instance['subscriber_smart_hide'];
			} else {
				$smartHide = "";
			}

		} else {
			$headerTxt = $instance['title'];
			$hideSubscriberName = 0;
			$subscribeAdd2Group = 0;
			$nameLabel = '';
			if(isset($instance['unsubscriber_email'])) {
				$emailLabel = $instance['unsubscriber_email'];
			} else {
				$emailLabel = "";
			}
			if(isset($instance['unsubscribe_button'])) {
				$buttonTxt = $instance['unsubscribe_button'];
			} else {
				$buttonTxt = "";
			}
			if(isset($instance['unsubscribe_thank_msg'])) {
				$submitTxt = $instance['unsubscribe_thank_msg'];
			} else {
				$submitTxt = "";
			}
			if(isset($instance['unsubscribe_confirm_email_msg'])) {
				$submitConfirmTxt = $instance['unsubscribe_confirm_email_msg'];
			} else {
				$submitConfirmTxt = "";
			}
			if(isset($instance['unsubscribe_error_msg'])) {
				$errorTxt = $instance['unsubscribe_error_msg'];
			} else {
				$errorTxt = "";
			}
			if(isset($instance['unsubscriber_smart_hide'])) {
				$smartHide = $instance['unsubscriber_smart_hide'];
			} else {
				$smartHide = "";
			}
		}

		if($listChoice == 0){
			$settings['allLists'] = true;
			$settings['listIdSpecified'] = false;
			$settings['listNameSpecified'] = false;
			$settings['listId'] = 0;
		}else{
			$settings['allLists'] = false;
			$settings['listIdSpecified'] = true;
			$settings['listNameSpecified'] = false;
			$settings['listId'] = $listChoice;
		}

		if($captcha){
			// if captcha = 1 then take recaptcha (backward compatibility)
			$settings['captcha'] = (($captcha == 1) ? MstConsts::CAPTCHA_ID_MATH :  $captcha);
		}else{
			$settings['captcha'] = false;
		}

		$settings['hideListName'] = ($hideListName == 1);
		$settings['hideNameField'] = ($hideSubscriberName == 1);
		$settings['smartHide'] = ($smartHide == 1);
		$settings['suggestUserData'] = ($suggestUserData == 1);
		$settings['enforceUserData'] = ($enforceUserData == 1);
		$settings['subscribeAdd2Group'] = $subscribeAdd2Group;
		$settings['nameLabel'] 			= $nameLabel;
		$settings['emailLabel'] 		= $emailLabel;
		$settings['digestChoiceLabel'] 	= $digestChoiceLabel;
		$settings['listLabel'] 			= $listLabel;
		$settings['buttonTxt'] 			= $buttonTxt;
		$settings['submitTxt'] 			= $submitTxt;
		$settings['submitConfirmTxt'] 	= $submitConfirmTxt;
		$settings['errorTxt'] 			= $errorTxt;
		$settings['headerTxt'] 			= $headerTxt;
		$settings['cssPrefix'] 			= $cssPrefix;
		$settings['designChoice'] 		= $designChoice;
		$settings['digestChoice'] 		= $digestChoice;

		if(!MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_DIGEST)){
			$settings['digestChoice'] = false;
		}

        $formIdentifier = sha1(($subscriberType == 1 ? 'subscribe' : 'unsubscribe').rand(0, 999999));

        $formSessionInfo = new stdClass();
        $formSessionInfo->id = $formIdentifier;
        $formSessionInfo->type = ($subscriberType == 1 ? 'subscribe' : 'unsubscribe');
        $formSessionInfo->origin = 'plugin';

        $formSessionInfo->submitTxt = $submitTxt;
        $formSessionInfo->submitConfirmTxt = $submitConfirmTxt;
        $formSessionInfo->errorTxt = $errorTxt;
        $formSessionInfo->captcha = $settings['captcha'];

        $subscribeFormsInSession = array_key_exists('wpmst_subscribe_forms', $_SESSION) ? $_SESSION['wpmst_subscribe_forms'] : array();
        $subscribeFormsInSession[] = $formSessionInfo;
        $_SESSION['wpmst_subscribe_forms'] = $subscribeFormsInSession;
        //$log->debug('subscribe-widget SESSION after adding formId: '.print_r($_SESSION['wpmst_subscribe_forms'], true));

		if($subscriberType == 1){
			$subscrUtils->getSubscriberHtml($settings, $formIdentifier);
		}else{
			$subscrUtils->getUnsubscriberHtml($settings, $formIdentifier);
		}

		echo $args['after_widget'];
	}

	/**
	 * @param array $instance
	 *
	 * @return void echoes the form that generates the widget
	 */
	public function form( $instance ) {
        $log = MstFactory::getLogger();
        $log->debug('subscribe-widget instance: '.print_r($instance, true));
		?>
		<p>
			<?php //title
			if ( isset( $instance[ 'title' ] ) ) {
				$title = $instance[ 'title' ];
			} else {
				$title = __( 'Mailster Mailing List Subscription', 'wpmst-mailster' );
			}
			?>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>">
				<?php _e( 'Title:', 'wpmst-mailster' ); ?>
			</label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<?php //subsriber type
			if ( isset( $instance[ 'subscriber_type' ] ) ) {
				$selectedValue = $instance[ 'subscriber_type' ];
			} else {
				$selectedValue = 1;
			}
			?>
			<label for="<?php echo $this->get_field_id( 'subscriber_type' ); ?>">
				<?php _e("Form choice", "wpmst-mailster"); ?>
			</label>
			<select name="<?php echo $this->get_field_name( 'subscriber_type' ); ?>" id="<?php echo $this->get_field_id( 'subscriber_type' ); ?>" onchange="toggleSubscriberType(this.id, '<?php echo $this->get_field_id('mst-subscribeForm'); ?>', '<?php echo $this->get_field_id('mst-unsubscribeForm'); ?>');">
				<?php $value = 1; ?>
				<option value="<?php echo $value; ?>" <?php if( $value == $selectedValue ) { echo "selected='selected'"; } ?> >
					<?php _e('Subscribe', "wpmst-mailster"); ?>
				</option>
				<?php $value = 2; ?>
				<option value="<?php echo $value; ?>" <?php if( $value == $selectedValue ) { echo "selected='selected'"; } ?> >
					<?php _e('Unsubscribe', "wpmst-mailster"); ?>
				</option>
			</select>
		</p>
		<div class="mst-subscribeForm" id="<?php echo $this->get_field_id('mst-subscribeForm'); ?>">
			<p>
				<?php //list choice
				$MailingListUtils = MstFactory::getMailingListUtils();
				if ( isset( $instance[ 'list_choice' ] ) ) {
					$selectedValue = $instance[ 'list_choice' ];
				} else {
					$selectedValue = '';
				}
				?>
				<label for="<?php echo $this->get_field_id( 'list_choice' ); ?>">
					<?php _e("List choice", "wpmst-mailster"); ?>
				</label>
				<?php
				$lists = $MailingListUtils->getAllLists();

				?>
				<select name="<?php echo $this->get_field_name( 'list_choice' ); ?>" id="<?php echo $this->get_field_id( 'list_choice' ); ?>">
					<?php
					foreach($lists as $list) {
						$value =$list->value;
						$name = $list->list_choice;
						?>
						<option value="<?php echo $value; ?>" <?php if( $value == $selectedValue ) { echo "selected='selected'"; } ?> >
							<?php echo $name; ?>
						</option>
						<?php
					} ?>
				</select>
			</p>
			<p>
				<?php //list choice
				$groupModel = MstFactory::getGroupModel();
				$groups = $groupModel->getAllGroupsForm();
				if ( isset( $instance[ 'subscription_group_choice' ] ) ) {
					$selectedValue = $instance[ 'subscription_group_choice' ];
				} else {
					$selectedValue = 0;
				}
				?>
				<label for="<?php echo $this->get_field_id( 'subscription_group_choice' ); ?>">
					<?php _e("Add to group on subscription", "wpmst-mailster"); ?>
				</label>
				<select name="<?php echo $this->get_field_name( 'subscription_group_choice' ); ?>" id="<?php echo $this->get_field_id( 'subscription_group_choice' ); ?>">
					<?php
					foreach($groups as $group) {
						$value = $group->value;
						$name = $group->list_choice;
						?>
						<option value="<?php echo $value; ?>" <?php if( $value == $selectedValue ) { echo "selected='selected'"; } ?> >
							<?php echo $name; ?>
						</option>
						<?php
					} ?>
				</select>
			</p>
			<p>
				<?php //subsriber type
				if ( isset( $instance[ 'captcha' ] ) ) {
					$selectedValue = $instance[ 'captcha' ];
				} else {
					$selectedValue = 0;
				}
				?>
				<label for="<?php echo $this->get_field_id( 'captcha' ); ?>">
					<?php _e("Captcha protection active", "wpmst-mailster"); ?>
				</label>
				<select name="<?php echo $this->get_field_name( 'captcha' ); ?>" id="<?php echo $this->get_field_id( 'captcha' ); ?>">
					<?php $value = 0; ?>
					<option value="<?php echo $value; ?>" <?php if( $value === $selectedValue ) { echo "selected='selected'"; } ?> >
						<?php _e('No', "wpmst-mailster"); ?>
					</option>
					<?php $value = MstConsts::CAPTCHA_ID_RECAPTCHA_V2; ?>
					<option value="<?php echo $value; ?>" <?php if( $value === $selectedValue ) { echo "selected='selected'"; } ?> >
						<?php _e('Recaptcha', "wpmst-mailster"); ?>
					</option>
					<?php $value = MstConsts::CAPTCHA_ID_MATH; ?>
					<option value="<?php echo $value; ?>" <?php if( $value === $selectedValue ) { echo "selected='selected'"; } ?> >
						<?php _e('Math captcha', "wpmst-mailster"); ?>
					</option>
				</select>
			</p>
			<p>
				<?php //design choice
				if ( isset( $instance[ 'design_choice' ] ) ) {
					$selectedValue = $instance[ 'design_choice' ];
				} else {
					$selectedValue = "";
				}
				?>
				<label for="<?php echo $this->get_field_id( 'design_choice' ); ?>">
					<?php _e("Design Choice", "wpmst-mailster"); ?>
				</label>
				<select name="<?php echo $this->get_field_name( 'design_choice' ); ?>" id="<?php echo $this->get_field_id( 'design_choice' ); ?>">
					<?php $value = ""; ?>
					<option value="<?php echo $value; ?>" <?php if( $value == $selectedValue ) { echo "selected='selected'"; } ?> >
						<?php _e('No design', "wpmst-mailster"); ?>
					</option>
					<?php $value = "black"; ?>
					<option value="<?php echo $value; ?>" <?php if( $value == $selectedValue ) { echo "selected='selected'"; } ?> >
						<?php _e('Black', "wpmst-mailster"); ?>
					</option>
					<?php $value = "blue"; ?>
					<option value="<?php echo $value; ?>" <?php if( $value == $selectedValue ) { echo "selected='selected'"; } ?> >
						<?php _e('Blue', "wpmst-mailster"); ?>
					</option>
					<?php $value = "red"; ?>
					<option value="<?php echo $value; ?>" <?php if( $value == $selectedValue ) { echo "selected='selected'"; } ?> >
						<?php _e('Red', "wpmst-mailster"); ?>
					</option>
					<?php $value = "white"; ?>
					<option value="<?php echo $value; ?>" <?php if( $value == $selectedValue ) { echo "selected='selected'"; } ?> >
						<?php _e('White', "wpmst-mailster"); ?>
					</option>
				</select>
			</p>
			<p>
				<?php //prefix class
				if ( isset( $instance[ 'prefix_class' ] ) ) {
					$value = $instance[ 'prefix_class' ];
				} else {
					$value = "mailster_subscriber_";
				}
				?>
				<label for="<?php echo $this->get_field_id( 'prefix_class' ); ?>">
					<?php _e( 'Prefix class', 'wpmst-mailster' ); ?>
				</label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'prefix_class' ); ?>" name="<?php echo $this->get_field_name( 'prefix_class' ); ?>" type="text" value="<?php echo esc_attr( $value ); ?>">
			</p>
			<p>
				<?php //list label
				if ( isset( $instance[ 'list_label' ] ) ) {
					$value = $instance[ 'list_label' ];
				} else {
					$value = __( 'Newsletter', "wpmst-mailster");
				}
				?>
				<label for="<?php echo $this->get_field_id( 'list_label' ); ?>">
					<?php _e( 'Mailing list label', 'wpmst-mailster' ); ?>
				</label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'list_label' ); ?>" name="<?php echo $this->get_field_name( 'list_label' ); ?>" type="text" value="<?php echo esc_attr( $value ); ?>">
			</p>
			<p>
				<?php //hide list name
				if ( isset( $instance[ 'hide_list_name' ] ) ) {
					$selectedValue = $instance[ 'hide_list_name' ];
				} else {
					$selectedValue = 0;
				}
				?>
				<label for="<?php echo $this->get_field_id( 'hide_list_name' ); ?>">
					<?php _e( 'Hide list', 'wpmst-mailster' ); ?>
				</label>
				<select name="<?php echo $this->get_field_name( 'hide_list_name' ); ?>" id="<?php echo $this->get_field_id( 'hide_list_name' ); ?>">
					<?php $value=0; ?>
					<option value="<?php echo $value; ?>" <?php if( $value == $selectedValue ) { echo "selected='selected'"; } ?> >
						<?php _e("No", "wpmst-mailster"); ?>
					</option>
					<?php $value=1; ?>
					<option value="<?php echo $value; ?>" <?php if( $value == $selectedValue ) { echo "selected='selected'"; } ?> >
						<?php _e("Yes", "wpmst-mailster"); ?>
					</option>
				</select>
			</p>
			<p>
				<?php //smart hide
				if ( isset( $instance[ 'subscriber_smart_hide' ] ) ) {
					$selectedValue = $instance[ 'subscriber_smart_hide' ];
				} else {
					$selectedValue = 0;
				}
				?>
				<label for="<?php echo $this->get_field_id( 'subscriber_smart_hide' ); ?>">
					<?php _e( 'Smart Hide', 'wpmst-mailster' ); ?>
				</label>
				<select name="<?php echo $this->get_field_name( 'subscriber_smart_hide' ); ?>" id="<?php echo $this->get_field_id( 'subscriber_smart_hide' ); ?>">
					<?php $value=0; ?>
					<option value="<?php echo $value; ?>" <?php if( $value == $selectedValue ) { echo "selected='selected'"; } ?> >
						<?php _e("No", "wpmst-mailster"); ?>
					</option>
					<?php $value=1; ?>
					<option value="<?php echo $value; ?>" <?php if( $value == $selectedValue ) { echo "selected='selected'"; } ?> >
						<?php _e("Yes", "wpmst-mailster"); ?>
					</option>
				</select>
			</p>
			<p>
				<?php //smart hide
				if ( isset( $instance[ 'hide_subscriber_name' ] ) ) {
					$selectedValue = $instance[ 'hide_subscriber_name' ];
				} else {
					$selectedValue = 0;
				}
				?>
				<label for="<?php echo $this->get_field_id( 'hide_subscriber_name' ); ?>">
					<?php _e( 'Hide Subscriber Name', 'wpmst-mailster' ); ?>
				</label>
				<select name="<?php echo $this->get_field_name( 'hide_subscriber_name' ); ?>" id="<?php echo $this->get_field_id( 'hide_subscriber_name' ); ?>">
					<?php $value=0; ?>
					<option value="<?php echo $value; ?>" <?php if( $value == $selectedValue ) { echo "selected='selected'"; } ?> >
						<?php _e("No", "wpmst-mailster"); ?>
					</option>
					<?php $value=1; ?>
					<option value="<?php echo $value; ?>" <?php if( $value == $selectedValue ) { echo "selected='selected'"; } ?> >
						<?php _e("Yes", "wpmst-mailster"); ?>
					</option>
				</select>
			</p>
			<p>
				<?php //Name field
				if ( isset( $instance[ 'subscriber_name' ] ) ) {
					$value = $instance[ 'subscriber_name' ];
				} else {
					$value = __( 'Name', "wpmst-mailster");
				}
				?>
				<label for="<?php echo $this->get_field_id( 'subscriber_name' ); ?>">
					<?php _e( 'Name field label', 'wpmst-mailster' ); ?>
				</label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'subscriber_name' ); ?>" name="<?php echo $this->get_field_name( 'subscriber_name' ); ?>" type="text" value="<?php echo esc_attr( $value ); ?>">
			</p>
			<p>
				<?php //Name field
				if ( isset( $instance[ 'subscriber_email' ] ) ) {
					$value = $instance[ 'subscriber_email' ];
				} else {
					$value = __( 'Email', "wpmst-mailster");
				}
				?>
				<label for="<?php echo $this->get_field_id( 'subscriber_email' ); ?>">
					<?php _e( 'Email field label', 'wpmst-mailster' ); ?>
				</label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'subscriber_email' ); ?>" name="<?php echo $this->get_field_name( 'subscriber_email' ); ?>" type="text" value="<?php echo esc_attr( $value ); ?>">
			</p>
			<p>
				<?php //Name field
				if ( isset( $instance[ 'subscribe_button' ] ) ) {
					$value = $instance[ 'subscribe_button' ];
				} else {
					$value = __( 'Subscribe', "wpmst-mailster");
				}
				?>
				<label for="<?php echo $this->get_field_id( 'subscribe_button' ); ?>">
					<?php _e( 'Subscribe field label', 'wpmst-mailster' ); ?>
				</label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'subscribe_button' ); ?>" name="<?php echo $this->get_field_name( 'subscribe_button' ); ?>" type="text" value="<?php echo esc_attr( $value ); ?>">
			</p>
			<p>
				<?php //Name field
				if ( isset( $instance[ 'subscribe_thank_msg' ] ) ) {
					$value = $instance[ 'subscribe_thank_msg' ];
				} else {
					$value = __( 'Thank you for subscribing', "wpmst-mailster");
				}
				?>
				<label for="<?php echo $this->get_field_id( 'subscribe_thank_msg' ); ?>">
					<?php _e( 'Subscription OK text', 'wpmst-mailster' ); ?>
				</label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'subscribe_thank_msg' ); ?>" name="<?php echo $this->get_field_name( 'subscribe_thank_msg' ); ?>" type="text" value="<?php echo esc_attr( $value ); ?>">
			</p>
			<p>
				<?php //Name field
				if ( isset( $instance[ 'subscribe_confirm_email_msg' ] ) ) {
					$value = $instance[ 'subscribe_confirm_email_msg' ];
				} else {
					$value = __( 'Thank you for your subscription. An email was sent to confirm your subscription. Please follow the instructions in the email.', "wpmst-mailster");
				}
				?>
				<label for="<?php echo $this->get_field_id( 'subscribe_confirm_email_msg' ); ?>">
					<?php _e( 'Subscription confirmation sent text', 'wpmst-mailster' ); ?>
				</label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'subscribe_confirm_email_msg' ); ?>" name="<?php echo $this->get_field_name( 'subscribe_confirm_email_msg' ); ?>" type="text" value="<?php echo esc_attr( $value ); ?>">
			</p>
			<p>
				<?php //Name field
				if ( isset( $instance[ 'subscribe_error_msg' ] ) ) {
					$value = $instance[ 'subscribe_error_msg' ];
				} else {
					$value =  __( 'Subscription error occured. Please try again.', "wpmst-mailster" );
				}
				?>
				<label for="<?php echo $this->get_field_id( 'subscribe_error_msg' ); ?>">
					<?php _e( 'Subscription error text', 'wpmst-mailster' ); ?>
				</label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'subscribe_error_msg' ); ?>" name="<?php echo $this->get_field_name( 'subscribe_error_msg' ); ?>" type="text" value="<?php echo esc_attr( $value ); ?>">
			</p>
		</div>
		<div class="mst-unsubscribeForm" id="<?php echo $this->get_field_id('mst-unsubscribeForm'); ?>">
			<p>
				<?php //smart hide
				if ( isset( $instance[ 'unsubscriber_smart_hide' ] ) ) {
					$selectedValue = $instance[ 'unsubscriber_smart_hide' ];
				} else {
					$selectedValue = 0;
				}
				?>
				<label for="<?php echo $this->get_field_id( 'unsubscriber_smart_hide' ); ?>">
					<?php _e( 'Smart Hide', 'wpmst-mailster' ); ?>
				</label>
				<select name="<?php echo $this->get_field_name( 'unsubscriber_smart_hide' ); ?>" id="<?php echo $this->get_field_id( 'unsubscriber_smart_hide' ); ?>">
					<?php $value=0; ?>
					<option value="<?php echo $value; ?>" <?php if( $value == $selectedValue ) { echo "selected='selected'"; } ?> >
						<?php _e("No", "wpmst-mailster"); ?>
					</option>
					<?php $value=1; ?>
					<option value="<?php echo $value; ?>" <?php if( $value == $selectedValue ) { echo "selected='selected'"; } ?> >
						<?php _e("Yes", "wpmst-mailster"); ?>
					</option>
				</select>
			</p>
			<p>
				<?php //Name field
				if ( isset( $instance[ 'unsubscriber_email' ] ) ) {
					$value = $instance[ 'unsubscriber_email' ];
				} else {
					$value = __( 'Email', "wpmst-mailster");
				}
				?>
				<label for="<?php echo $this->get_field_id( 'unsubscriber_email' ); ?>">
					<?php _e( 'Email field label', 'wpmst-mailster' ); ?>
				</label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'unsubscriber_email' ); ?>" name="<?php echo $this->get_field_name( 'unsubscriber_email' ); ?>" type="text" value="<?php echo esc_attr( $value ); ?>">
			</p>
			<p>
				<?php //Name field
				if ( isset( $instance[ 'unsubscribe_button' ] ) ) {
					$value = $instance[ 'unsubscribe_button' ];
				} else {
					$value = __( 'Unsubscribe', "wpmst-mailster" );
				}
				?>
				<label for="<?php echo $this->get_field_id( 'unsubscribe_button' ); ?>">
					<?php _e( 'Subscribe field label', 'wpmst-mailster' ); ?>
				</label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'unsubscribe_button' ); ?>" name="<?php echo $this->get_field_name( 'unsubscribe_button' ); ?>" type="text" value="<?php echo esc_attr( $value ); ?>">
			</p>
			<p>
				<?php //Name field
				if ( isset( $instance[ 'unsubscribe_thank_msg' ] ) ) {
					$value = $instance[ 'unsubscribe_thank_msg' ];
				} else {
					$value = __( 'Sorry that you decided to unsubscribe. Hope to see you again in the future!', "wpmst-mailster" );
				}
				?>
				<label for="<?php echo $this->get_field_id( 'unsubscribe_thank_msg' ); ?>">
					<?php _e( 'Unsubscription OK text', 'wpmst-mailster' ); ?>
				</label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'unsubscribe_thank_msg' ); ?>" name="<?php echo $this->get_field_name( 'unsubscribe_thank_msg' ); ?>" type="text" value="<?php echo esc_attr( $value ); ?>">
			</p>
			<p>
				<?php //Name field
				if ( isset( $instance[ 'unsubscribe_confirm_email_msg' ] ) ) {
					$value = $instance[ 'unsubscribe_confirm_email_msg' ];
				} else {
					$value = __( 'An email was sent to you to confirm that you unsubscribed. Please follow the instructions in the email.', "wpmst-mailster" );
				}
				?>
				<label for="<?php echo $this->get_field_id( 'unsubscribe_confirm_email_msg' ); ?>">
					<?php _e( 'Unsubscription confirmation sent text', 'wpmst-mailster' ); ?>
				</label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'unsubscribe_confirm_email_msg' ); ?>" name="<?php echo $this->get_field_name( 'unsubscribe_confirm_email_msg' ); ?>" type="text" value="<?php echo esc_attr( $value ); ?>">
			</p>
			<p>
				<?php //Name field
				if ( isset( $instance[ 'unsubscribe_error_msg' ] ) ) {
					$value = $instance[ 'unsubscribe_error_msg' ];
				} else {
					$value =  __( 'Unsubscription error occured. Please try again.', "wpmst-mailster" );
				}
				?>
				<label for="<?php echo $this->get_field_id( 'unsubscribe_error_msg' ); ?>">
					<?php _e( 'Unsubscription error text', 'wpmst-mailster' ); ?>
				</label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'unsubscribe_error_msg' ); ?>" name="<?php echo $this->get_field_name( 'unsubscribe_error_msg' ); ?>" type="text" value="<?php echo esc_attr( $value ); ?>">
			</p>
		</div>
		<script type="text/javascript">
			function showSubscription(subscribeId, unsubscribeId) {
				jQuery("#"+subscribeId).css("display", "block");
				jQuery("#"+unsubscribeId).css("display", "none");
			}
			function showUnsubscription(subscribeId, unsubscribeId) {
                jQuery("#"+subscribeId).css("display", "none");
                jQuery("#"+unsubscribeId).css("display", "block");
			}
            function toggleSubscriberType(subscribeSettingId, subscribePartId, unsubscribePartId){
                console.log(subscribeSettingId);
                console.log(subscribePartId);
                console.log(unsubscribePartId);
                if(jQuery('#'+subscribeSettingId).val() == 1) {
                    jQuery("#"+subscribePartId).css("display", "block");
                    jQuery("#"+unsubscribePartId).css("display", "none");
                } else {
                    jQuery("#"+subscribePartId).css("display", "none");
                    jQuery("#"+unsubscribePartId).css("display", "block");
                }
            }
            toggleSubscriberType('<?php echo $this->get_field_id( 'subscriber_type' ); ?>', '<?php echo $this->get_field_id('mst-subscribeForm'); ?>', '<?php echo $this->get_field_id('mst-unsubscribeForm'); ?>');
		</script>
		<?php 
	}
	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['subscriber_type'] = ( ! empty( $new_instance['subscriber_type'] ) ) ? strip_tags( $new_instance['subscriber_type'] ) : 1;
		$instance['list_choice'] = ( ! empty( $new_instance['list_choice'] ) ) ? strip_tags( $new_instance['list_choice'] ) : 0;
		$instance['subscription_group_choice'] = ( ! empty( $new_instance['subscription_group_choice'] ) ) ? strip_tags( $new_instance['subscription_group_choice'] ) : 0;
		$instance['captcha'] = ( ! empty( $new_instance['captcha'] ) ) ? strip_tags( $new_instance['captcha'] ) : 0;
		$instance['design_choice'] = ( ! empty( $new_instance['design_choice'] ) ) ? strip_tags( $new_instance['design_choice'] ) : 0;
		$instance['prefix_class'] = ( ! empty( $new_instance['prefix_class'] ) ) ? strip_tags( $new_instance['prefix_class'] ) : 'mailster_subscriber_';
		$instance['list_label'] = ( ! empty( $new_instance['list_label'] ) ) ? strip_tags( $new_instance['list_label'] ) : __( 'Newsletter', "wpmst-mailster");
		$instance['hide_list_name'] = ( ! empty( $new_instance['hide_list_name'] ) ) ? strip_tags( $new_instance['hide_list_name'] ) : 0;
		$instance['subscriber_smart_hide'] = ( ! empty( $new_instance['subscriber_smart_hide'] ) ) ? strip_tags( $new_instance['subscriber_smart_hide'] ) : 0;
		$instance['hide_subscriber_name'] = ( ! empty( $new_instance['hide_subscriber_name'] ) ) ? strip_tags( $new_instance['hide_subscriber_name'] ) : 0;
		$instance['subscriber_name'] = ( ! empty( $new_instance['subscriber_name'] ) ) ? strip_tags( $new_instance['subscriber_name'] ) : __( 'Name', "wpmst-mailster");
		$instance['subscriber_email'] = ( ! empty( $new_instance['subscriber_email'] ) ) ? strip_tags( $new_instance['subscriber_email'] ) : __( 'Email', "wpmst-mailster");
		$instance['subscribe_button'] = ( ! empty( $new_instance['subscribe_button'] ) ) ? strip_tags( $new_instance['subscribe_button'] ) : __( 'Subscribe', "wpmst-mailster");
		$instance['unsubscribe_button'] = ( ! empty( $new_instance['unsubscribe_button'] ) ) ? strip_tags( $new_instance['unsubscribe_button'] ) : __( 'Unsubscribe', "wpmst-mailster");
		$instance['subscribe_thank_msg'] = ( ! empty( $new_instance['subscribe_thank_msg'] ) ) ? strip_tags( $new_instance['subscribe_thank_msg'] ) : __( 'Thank you for subscribing', "wpmst-mailster");
		$instance['subscribe_confirm_email_msg'] = ( ! empty( $new_instance['subscribe_confirm_email_msg'] ) ) ? strip_tags( $new_instance['subscribe_confirm_email_msg'] ) : __( 'Thank you for your subscription. An email was sent to confirm your subscription. Please follow the instructions in the email.', "wpmst-mailster");
		$instance['subscribe_confirm_email_msg'] = ( ! empty( $new_instance['subscribe_confirm_email_msg'] ) ) ? strip_tags( $new_instance['subscribe_confirm_email_msg'] ) : __( 'Subscription error occured. Please try again.', "wpmst-mailster" );
		$instance['subscribe_error_msg'] = ( ! empty( $new_instance['subscribe_error_msg'] ) ) ? strip_tags( $new_instance['subscribe_error_msg'] ) : __( 'Subscription error occured. Please try again.', "wpmst-mailster" );

		$instance['unsubscriber_smart_hide'] = ( ! empty( $new_instance['unsubscriber_smart_hide'] ) ) ? strip_tags( $new_instance['unsubscriber_smart_hide'] ) : 0;
		$instance['unsubscriber_email'] = ( ! empty( $new_instance['unsubscriber_email'] ) ) ? strip_tags( $new_instance['unsubscriber_email'] ) : __( 'Email', "wpmst-mailster");
		$instance['unsubscribe_button'] = ( ! empty( $new_instance['unsubscribe_button'] ) ) ? strip_tags( $new_instance['unsubscribe_button'] ) : __( 'Unsubscribe', "wpmst-mailster");
		$instance['unsubscribe_thank_msg'] = ( ! empty( $new_instance['unsubscribe_thank_msg'] ) ) ? strip_tags( $new_instance['unsubscribe_thank_msg'] ) : __( 'Sorry that you decided to unsubscribe. Hope to see you again in the future!', "wpmst-mailster" );
		$instance['unsubscribe_confirm_email_msg'] = ( ! empty( $new_instance['unsubscribe_confirm_email_msg'] ) ) ? strip_tags( $new_instance['unsubscribe_confirm_email_msg'] ) : __( 'An email was sent to you to confirm that you unsubscribed. Please follow the instructions in the email.', "wpmst-mailster" );
		$instance['unsubscribe_error_msg'] = ( ! empty( $new_instance['unsubscribe_error_msg'] ) ) ? strip_tags( $new_instance['unsubscribe_error_msg'] ) : __( 'Unsubscription error occured. Please try again.', "wpmst-mailster" );

		return $instance;
	}
} // class Prices_Watch