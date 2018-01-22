<?php
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

if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
	die('These are not the droids you are looking for.');
}
	
	
class MstSubscriberPlugin
{
	public function __construct() {
	}

    protected function filterMailingListsForSmartHide($lists, $formType, $settings){
        $subscrUtils  = MstFactory::getSubscribeUtils();
        return $subscrUtils->filterMailingListsForSmartHide($lists, $formType, $settings);
    }

    protected function isSmartHideActive($formType, $settings){
        $subscrUtils  = MstFactory::getSubscribeUtils();
        return $subscrUtils->isSmartHideActive($formType, $settings);
    }
	
	public function getSubscriberHtml($res, $formIdentifier)
	{
		$log = MstFactory::getLogger();
		$mstUtils = MstFactory::getUtils();
		$listUtils = MstFactory::getMailingListUtils();
		$subscrUtils = MstFactory::getSubscribeUtils();

		wp_register_style( 'subscriber_style', plugins_url( '/../asset/css/subscriber.css', dirname(__FILE__) ));
		wp_enqueue_style('subscriber_style');
		
		$noName = __( 'Please provide a name', "wpmst-mailster" );
		$noEmail = __( 'Please provide your email address', "wpmst-mailster" );
		$invalidEmail = __( 'Invalid email address', "wpmst-mailster" );
		$noListChosen = __( 'You have no mailing list chosen', "wpmst-mailster" );	
		$tooMuchRecipients = __( 'Too many recipients (Product limit)', "wpmst-mailster" );
        $registrationInactive = __( 'Registration currently not possible', "wpmst-mailster" );
		$registrationOnlyForRegisteredUsers = __( 'Subscribing not allowed for unregistered users. Please login first.', "wpmst-mailster" );
		$captchaCodeWrong = __( 'The captcha code you entered was wrong, please try again', "wpmst-mailster" );
		
		$errors = 0;
		$myError = "";
		$replaceTxt = "";
		
		$allLists = $res['allLists'];
		$listIdSpecified = $res['listIdSpecified'];
		$listId = $res['listId'];    	
    	$allLists = $res['allLists'];
		$listNameSpecified = $res['listNameSpecified'];
		if($listNameSpecified){
			$listName = $res['listName'];
		}
    	$submitTxt = $res['submitTxt'];
    	$headerTxt = $res['headerTxt'];
    	$buttonTxt = $res['buttonTxt'];
    	$listLabel = $res['listLabel'];
    	$nameLabel = $res['nameLabel'];
    	$digestChoiceLabel = $res['digestChoiceLabel'];
    	$emailLabel = $res['emailLabel'];
    	$hideNameField = $res['hideNameField'];
    	$hideListName = $res['hideListName'];
		$captchaType = $res['captcha'];
		$smartHide = $res['smartHide'];
		$cssPrefix = $res['cssPrefix'];
		$designChoice = $res['designChoice'];
		$digestChoice = $res['digestChoice'];
    	$submitConfirmTxt = $res['submitConfirmTxt'];
    	$add2Group = $res['subscribeAdd2Group'];
    	$suggestUserData = $res['suggestUserData'];
    	$enforceUserData = $res['enforceUserData'];
    	
    	if($designChoice !== ''){
    		$cssPrefix = MstConsts::SUBSCR_CSS_DEFAULT . $designChoice . '_';
    	}

		if($subscrUtils->isUserLoggedIn() && ($suggestUserData || $enforceUserData)){
			$user =  wp_get_current_user();
			$name = trim($user->name);
			$email = trim($user->email);			
		}else{
			$email = "";
			$name = "";
		}			


        if($allLists){
            $log->debug('subscribe-module: All lists');
            $lists = $subscrUtils->getMailingLists2RegisterAt(!$subscrUtils->isUserLoggedIn());
            $log->debug('subscribe-module: lists: '.print_r($lists, true));
            $lists = $subscrUtils->filterMailingListsForSmartHide($lists, 'subscribe', $res);
            $log->debug('subscribe-module: filtered lists: '.print_r($lists, true));
            $dropDown = $subscrUtils->getDropdownFromLists($lists, $cssPrefix);
            $log->debug('subscribe-module: Dropdown: '.$dropDown);
        }else{
            $log->debug('subscribe-module: Single list');
            $list = null;
            if($listIdSpecified){
                $list = $listUtils->getMailingList($listId);
            }else if($listNameSpecified){
                $list = $listUtils->getMailingListByName($listName);
            }
        }

        $smartHideActive = $subscrUtils->isSmartHideActive('subscribe', $res);

        if(!$smartHideActive){  // check whether whole form does not need to be shown
            ?>

            <div class="<?php echo $cssPrefix; ?>container subscribeUnsubscribeContainer">
                <form action="" method="post" id="subscr<?php echo $formIdentifier; ?>" class="wpmst-subscribe-form"  data-wpmstformid="<?php echo $formIdentifier; ?>">
                    <input type="hidden" name="action" value="wpmst_subscribe_plugin" />
                    <input type="hidden" name="_ajax_nonce" value="<?php echo wp_create_nonce( 'wpmst_subscribe_plugin_nonce' ); ?>" />
                    <input type="hidden" name="<?php echo MstConsts::SUBSCR_POST_IDENTIFIER; ?>" value="<?php echo $formIdentifier; ?>" />
                    <h4 class="<?php echo $cssPrefix; ?>header"><?php echo $headerTxt; ?></h4>
                    <p>
                        <?php

                        if($allLists){  ?>
                            <label class="<?php echo $cssPrefix; ?>listLabel" for="<?php echo MstConsts::SUBSCR_ID_FIELD; ?>"><span><?php echo $listLabel; ?></span></label>
                            <?php echo $dropDown;
                        }else{
                            if(!is_null($list)){ ?>
                                <input type="hidden" name="<?php echo MstConsts::SUBSCR_ID_FIELD; ?>" value="<?php echo $list->id; ?>" /><?php
                                if($hideListName == false){ ?>
                                    <label class="<?php echo $cssPrefix; ?>listLabel" for="<?php echo MstConsts::SUBSCR_ID_FIELD; ?>"><span><?php echo $listLabel; ?></span></label>
                                    <span class="<?php echo $cssPrefix; ?>listName"><?php echo $list->name; ?></span><?php
                                }
                            }else{ ?>
                                <label class="<?php echo $cssPrefix; ?>listLabel" for="<?php echo MstConsts::SUBSCR_ID_FIELD; ?>"><span><?php echo $listLabel; ?></span></label>
                                <span class="<?php echo $cssPrefix; ?>listName <?php echo $cssPrefix; ?>errorMessage <?php echo $cssPrefix; ?>message"><?php echo __('Unknown list', 'wpmst-mailster'); ?></span><?php
                                $errors++;
                            }
                        } ?>
                    </p><?php
                    if($allLists || (!is_null($list) && $list->allow_subscribe == '1')){
                        if($allLists || ($list->public_registration == '1') || ($subscrUtils->isUserLoggedIn())){
                            if($hideNameField == false){
                                if($subscrUtils->isUserLoggedIn() && $enforceUserData){
                                    $readOnly = 'readonly="readonly"';
                                    $readOnlyClass = $cssPrefix.'readyOnlyInput';
                                }else{
                                    $readOnly = '';
                                    $readOnlyClass = '';
                                }?>
                                <p>
                                <label class="<?php echo $cssPrefix; ?>nameLabel" for="<?php echo MstConsts::SUBSCR_NAME_FIELD; ?>"><span><?php echo $nameLabel; ?></span></label>
                                <input class="<?php echo $cssPrefix; ?>subscriberNameInput <?php echo $readOnlyClass; ?>" type="text" name="<?php echo MstConsts::SUBSCR_NAME_FIELD; ?>" value="<?php echo $name; ?>" <?php echo $readOnly; ?> /><?php
                            } ?>
                            </p>
                            <?php
                            if($subscrUtils->isUserLoggedIn() && $enforceUserData){
                                $readOnly = 'readonly="readonly"';
                                $readOnlyClass = $cssPrefix.'readyOnlyInput';
                            }else{
                                $readOnly = '';
                                $readOnlyClass = '';
                            } ?>
                            <p>
                                <label class="<?php echo $cssPrefix; ?>emailLabel" for="<?php echo MstConsts::SUBSCR_EMAIL_FIELD; ?>"><span><?php echo $emailLabel; ?></span></label>
                                <span style="display:none !important;">{emailcloak=off}</span>
                                <input class="<?php echo $cssPrefix; ?>subscriberEmailInput <?php echo $readOnlyClass; ?>" type="text" name="<?php echo MstConsts::SUBSCR_EMAIL_FIELD; ?>" value="<?php echo $email; ?>" <?php echo $readOnly; ?> />
                            </p>
                            <?php
                            if($digestChoice){?>
                                <label class="<?php echo $cssPrefix; ?>digestChoiceLabel" for="<?php echo MstConsts::SUBSCR_DIGEST_FIELD; ?>"><span><?php echo $digestChoiceLabel;?></span></label>
                            <select class="<?php echo $cssPrefix; ?>digestChoiceSelect" name="<?php echo MstConsts::SUBSCR_DIGEST_FIELD; ?>" >
                                <option value="<?php echo MstConsts::DIGEST_NO_DIGEST; ?>"><?php echo __("No digest", "wpmst-mailster"); ?></option>
                                <option value="<?php echo MstConsts::DIGEST_DAILY; ?>"><?php echo __("Daily digest", "wpmst-mailster"); ?></option>
                                <option value="<?php echo MstConsts::DIGEST_WEEKLY; ?>"><?php echo __("Weekly digest", "wpmst-mailster"); ?></option>
                                <option value="<?php echo MstConsts::DIGEST_MONTHLY; ?>"><?php echo __("Monthly digest", "wpmst-mailster"); ?></option>
                                </select><?php
                            }

                            if($captchaType != false){
                                $captchaTxt = $subscrUtils->getCaptchaHtml($captchaType, $cssPrefix, 'g-recaptcha-'.$formIdentifier); ?>
                                <p><?php echo $captchaTxt; ?></p><?php
                            }

                            if($errors <= 0){ ?>
                            <p class="<?php echo $cssPrefix; ?>submitButton">
                                <input id="submitBtn" class="wpmst-subscribe-btn" type="submit" value="<?php echo $buttonTxt; ?>" />
                                <span tabindex="-1" class="ajax_call_in_progress" style="display: none;">&nbsp;</span>
                                </p><?php
                            }
                        }else{ ?>
                            <p>
                            <span class="<?php echo $cssPrefix; ?>error"><?php echo $registrationOnlyForRegisteredUsers; ?></span>
                            </p><?php
                        }
                    }else{ ?>
                        <p>
                        <span class="<?php echo $cssPrefix; ?>error"><?php echo $registrationInactive; ?></span>
                        </p><?php
                    } ?>
                    <p class="subscribe-result" style="display:none;">
                        <span class="<?php echo $cssPrefix; ?>successMessage subscribe-result-success <?php echo $cssPrefix; ?>message"></span>
                        <span class="<?php echo $cssPrefix; ?>error subscribe-result-error <?php echo $cssPrefix; ?>message"></span>
                    </p>
                    <p class="subscribe-result" style="display:none;">
                        <span class="<?php echo $cssPrefix; ?>error subscribe-result-errorMsgs <?php echo $cssPrefix; ?>message"></span>
                    </p>
                </form>
            </div>
        <?php
        }
    }

	
	public function getUnsubscriberHtml($res, $formIdentifier)
	{
		$log = MstFactory::getLogger();
		$mstUtils = MstFactory::getUtils();
		$listUtils = MstFactory::getMailingListUtils();
		$subscrUtils = MstFactory::getSubscribeUtils();
		$recips = MstFactory::getRecipients();
		$cssPath = plugins_url( '/../asset/css/subscriber.css', dirname(__FILE__) );
		wp_enqueue_style ( "subscriber_css", $cssPath, array(), false, 'all' );
		
		$noEmail = __( 'Please provide your email address', "wpmst-mailster" );
		$noListChosen = __( 'You have no mailing list chosen', "wpmst-mailster" );		
		$captchaCodeWrong = __( 'The captcha code you entered was wrong, please try again', "wpmst-mailster" );	
		$notSubscribed = __( 'Email address is not subscribed', "wpmst-mailster" );
		$unsubscribeIncative = __( 'Unsubscribing currently not possible', "wpmst-mailster" );	
		
		$errors = 0;
		$myError = "";
		$replaceTxt = "";
		
		$allLists = $res['allLists'];
		$listIdSpecified = $res['listIdSpecified'];
		$listId = $res['listId'];    	
    	$allLists = $res['allLists'];
		$listNameSpecified = $res['listNameSpecified'];
		if($listNameSpecified){
			$listName = $res['listName'];
		}
    	$submitTxt = $res['submitTxt'];
    	$headerTxt = $res['headerTxt'];
    	$buttonTxt = $res['buttonTxt'];
    	$listLabel = $res['listLabel'];
    	$emailLabel = $res['emailLabel'];
    	$hideListName = $res['hideListName'];
		$captchaType = $res['captcha'];
    	$smartHide = $res['smartHide'];
		$cssPrefix = $res['cssPrefix'];
		$designChoice = $res['designChoice'];
    	$submitConfirmTxt = $res['submitConfirmTxt'];
    	$suggestUserData = $res['suggestUserData'];
    	$enforceUserData = $res['enforceUserData'];
    	
    	if($designChoice !== ''){
    		$cssPrefix = MstConsts::SUBSCR_CSS_DEFAULT . $designChoice . '_';
    	}
    	
		$postSent = false;	
		
		if($subscrUtils->isUserLoggedIn() && ($suggestUserData || $enforceUserData)){
			$user =  wp_get_current_user();
            $userModel = MstFactory::getUserModel();
            $userObj   = $userModel->getUserData($user->ID, true);
            $name = (property_exists($userObj, 'name') && $userObj->name && !empty($userObj->name) && (strlen(trim($userObj->name))>0)) ? $userObj->name : $user->display_name;
			$email = $user->user_email;
		}else{
			$email = "";
			$name = "";
		}				

        $smartHideActive = $subscrUtils->isSmartHideActive('unsubscribe', $res);

        if(!$smartHideActive){ // check whether whole form does not need to be shown
            ?>
            <div class="<?php echo $cssPrefix; ?>container subscribeUnsubscribeContainer">
                <form action="" method="post" id="unsubscr<?php echo $formIdentifier; ?>" class="wpmst-unsubscribe-form"  data-wpmstformid="<?php echo $formIdentifier; ?>">
                    <input type="hidden" name="action" value="wpmst_unsubscribe_plugin" />
                    <input type="hidden" name="_ajax_nonce" value="<?php echo wp_create_nonce( 'wpmst_unsubscribe_plugin_nonce' ); ?>" />
                    <input type="hidden" name="<?php echo MstConsts::SUBSCR_POST_IDENTIFIER; ?>" value="<?php echo $formIdentifier; ?>" />
                    <h4 class="<?php echo $cssPrefix; ?>header"><?php echo $headerTxt; ?></h4>
                    <?php
                    if($allLists){
                        $lists = $subscrUtils->getMailingLists2UnsubscribeFrom();
                        $lists = $subscrUtils->filterMailingListsForSmartHide($lists, 'unsubscribe', $res);
                        $dropDown = $subscrUtils->getDropdownFromLists($lists, $cssPrefix); ?>
                        <p>
                        <label class="<?php echo $cssPrefix; ?>listLabel"><span><?php echo $listLabel; ?></span></label>
                        <?php echo $dropDown; ?>
                        </p><?php
                    }else{
                        $list = null;
                        if($listIdSpecified){
                            $list = $listUtils->getMailingList($listId);
                        }else if($listNameSpecified){
                            $list = $listUtils->getMailingListByName($listName);
                        }
                        if($list){ ?>
                            <?php
                            if($hideListName == false){ ?>
                                <p>
                                <label class="<?php echo $cssPrefix; ?>listLabel" for="<?php echo MstConsts::SUBSCR_ID_FIELD; ?>"><span><?php echo $listLabel; ?></span></label>
                                <span class="<?php echo $cssPrefix; ?>listName"><?php echo $list->name; ?></span><input type="hidden" name="<?php echo MstConsts::SUBSCR_ID_FIELD; ?>" value="<?php echo $list->id; ?>" />
                                </p><?php
                            }else{ ?>
                                <input type="hidden" name="<?php echo MstConsts::SUBSCR_ID_FIELD; ?>" value="<?php echo $list->id; ?>" /><?php
                            }
                        }else{ ?>
                            <p>
                            <label class="<?php echo $cssPrefix; ?>listLabel" for="<?php echo MstConsts::SUBSCR_ID_FIELD; ?>"><span><?php echo $listLabel; ?></span></label>
                            <span class="<?php echo $cssPrefix; ?>listName <?php echo $cssPrefix; ?>errorMessage <?php echo $cssPrefix; ?>message"><?php echo JText::_( 'COM_MAILSTER_ERROR_UNKNOWN_LIST' ); ?></span><input type="hidden" name="<?php echo MstConsts::SUBSCR_ID_FIELD; ?>" value="" />
                            </p><?php
                            $errors++;
                        }
                    }

                    if($allLists || (!is_null($list) && $list->allow_unsubscribe == '1')){
                        if($subscrUtils->isUserLoggedIn() && $enforceUserData){
                            $readOnly = 'readonly="readonly"';
                            $readOnlyClass = $cssPrefix.'readyOnlyInput';
                        }else{
                            $readOnly = '';
                            $readOnlyClass = '';
                        } ?>
                        <p>
                        <label class="<?php echo $cssPrefix; ?>emailLabel" for="<?php echo MstConsts::SUBSCR_EMAIL_FIELD; ?>"><span><?php echo $emailLabel; ?></span></label>
                        <span style="display:none !important;">{emailcloak=off}</span>
                        <input class="<?php echo $cssPrefix; ?>subscriberEmailInput <?php echo $readOnlyClass; ?>" type="text" name="<?php echo MstConsts::SUBSCR_EMAIL_FIELD; ?>" value="<?php echo $email; ?>" <?php echo $readOnly; ?> />
                        </p><?php

                        if($captchaType != false){
                            $captchaTxt = $subscrUtils->getCaptchaHtml($captchaType, $cssPrefix, 'g-recaptcha-'.$formIdentifier); ?>
                            <p><?php echo $captchaTxt; ?></p><?php
                        }

                        if($errors <= 0){ ?>
                        <p class="<?php echo $cssPrefix; ?>submitButton">
                            <input id="submitBtn" class="" type="submit" value="<?php echo $buttonTxt; ?>" />
                            <span tabindex="-1" class="ajax_call_in_progress" style="display: none;">&nbsp;</span>
                            </p><?php
                        }
                    }else{ ?>
                        <p>
                        <span class="<?php echo $cssPrefix; ?>error"><?php echo $unsubscribeIncative; ?></span>
                        </p><?php
                    } ?>
                    <p class="unsubscribe-result" style="display:none;">
                        <span class="<?php echo $cssPrefix; ?>successMessage unsubscribe-result-success <?php echo $cssPrefix; ?>message"></span>
                        <span class="<?php echo $cssPrefix; ?>error unsubscribe-result-error <?php echo $cssPrefix; ?>message"></span>
                    </p>
                    <p class="unsubscribe-result" style="display:none;">
                        <span class="<?php echo $cssPrefix; ?>error unsubscribe-result-errorMsgs <?php echo $cssPrefix; ?>message"></span>
                    </p>
                </form>
            </div>
        <?php
        }
    }

	
	protected function processKeyValueStr($keyValuesStr, $res){    	
		$keyValueArray = $this->getKeyValueArray($keyValuesStr);
		$keyArray = array_keys($keyValueArray);
		
		for($i=0; $i < count($keyArray); $i++)
		{
			$key = $keyArray[$i];
			$val = $keyValueArray[$key];
			$key = strtolower(trim($key));
			$val = trim($val);
			switch ($key) {
    			case strtolower(MstConsts::SUBSCR_ID_KEY):
    				$res['allLists'] = false;
					$res['listIdSpecified'] = true;
					$res['listId'] = $val;
    				break;
    			case strtolower(MstConsts::SUBSCR_LIST_KEY):
    				$res['allLists'] = false;
					$res['listNameSpecified'] = true;
					$res['listName'] = $val;
    				break;
    			case strtolower(MstConsts::SUBSCR_SUBMIT_TEXT):
    				$res['submitTxt'] = $val;
    				break;
    			case strtolower(MstConsts::SUBSCR_HEADER_TEXT):
    				$res['headerTxt'] = $val;
    				break;
    			case strtolower(MstConsts::SUBSCR_BUTTON_TEXT):
    				$res['buttonTxt'] = $val;
    				break;
    			case strtolower(MstConsts::SUBSCR_LIST_LABEL):
    				$res['listLabel'] = $val;
    				break;
    			case strtolower(MstConsts::SUBSCR_NAME_LABEL):
    				$res['nameLabel'] = $val;
    				break;
    			case strtolower(MstConsts::SUBSCR_DIGEST_CHOICE_LABEL):
    				$res['digestChoiceLabel'] = $val;
    				break;
    			case strtolower(MstConsts::SUBSCR_EMAIL_LABEL):
    				$res['emailLabel'] = $val;
    				break;
    			case strtolower(MstConsts::SUBSCR_CSS_PREFIX):
    				$res['cssPrefix'] = $val;
    				break;  
    			case strtolower(MstConsts::SUBSCR_DESIGN_CHOICE):
    				$res['cssPrefix'] = MstConsts::SUBSCR_CSS_DEFAULT . strtolower($val) . '_';
    				$res['designChoice'] = strtolower($val);
    				break;  
    			case strtolower(MstConsts::SUBSCR_NO_LIST_NAME):
    				$choice = strtolower($val);
    				if($choice === 'yes'){
    					$res['hideListName'] = true;
    				}
    				break;  
    			case strtolower(MstConsts::SUBSCR_NO_NAME_FIELD):
    				$choice = strtolower($val);
    				if($choice === 'yes'){
    					$res['hideNameField'] = true;
    				}
    				break;    
    			case strtolower(MstConsts::SUBSCR_CAPTCHA):
					$res['captcha'] = strtolower($val);
					break;     
    			case strtolower(MstConsts::SUBSCR_ADD_2_GROUP):
					$res['subscribeAdd2Group'] = strtolower($val);
					break;  
    			case strtolower(MstConsts::SUBSCR_SMART_HIDE):
    				$choice = strtolower($val);
					if($choice === 'yes'){
    					$res['smartHide'] = true;
    				}
					break; 
    			case strtolower(MstConsts::SUBSCR_DIGEST_CHOICE):
    				$choice = strtolower($val);
					if($choice === 'yes'){
    					$res['digestChoice'] = true;
    				}
					break;
    			case strtolower(MstConsts::SUBSCR_SUGGEST_USER_DATA):
    				$choice = strtolower($val);
					if($choice !== 'yes'){
    					$res['suggestUserData'] = false;
    				}
    				break;
    			case strtolower(MstConsts::SUBSCR_ENFORCE_USER_DATA):
    				$choice = strtolower($val);
					if($choice !== 'yes'){
    					$res['enforceUserData'] = false;
    				}
    				break;
			}
		}
		return $res;
	}
	
	
	protected function findKeyValues($text, $strPos){
		$result = array();
		$result['keyValuesFound'] = false;
		$result['startPos'] = 0;
		$result['endPos'] = 0;
		$result['keyValuesStr'] = '';
		$startPos = strpos($text, MstConsts::SUBSCR_PARAM_START, $strPos);
		if( $startPos == $strPos || $startPos == ($strPos+1) )
		{
			$endPos = strpos($text, MstConsts::SUBSCR_PARAM_END, $startPos);
			if ($endPos !== false)
			{
				$endPos = $endPos - 1;
				$result['keyValuesFound'] = true;
				$result['startPos'] = $startPos;
				$result['endPos'] = $endPos;
				$result['keyValuesStr'] = substr($text, $startPos+1, $endPos-$startPos);	
			}
		}
		return $result;
	}

	protected function getKeyValueArray($keyValuesStr)
	{
		$keyValueArray = array();
		$keyValueRawArray = explode(MstConsts::SUBSCR_KEY_VALUE_PAIR_DELIMITER, $keyValuesStr); 
		for($i=0; $i < count($keyValueRawArray); $i++)
		{
			$keyValue = $keyValueRawArray[$i];
			$keyValue = trim($keyValue);
			$pos = strpos($keyValue, MstConsts::SUBSCR_KEY_VALUE_DELIMITER);
			if($pos !== false)
			{
				$key = substr($keyValue, 0, $pos);
				$value = substr($keyValue, $pos+1);
				$keyValueArray[$key] = $value;
			}
		}
		return $keyValueArray;
	}


    protected function getDropdownFromLists($lists){
        $subscrUtils = MstFactory::getSubscribeUtils();
        return $subscrUtils->getDropdownFromLists($lists);
    }

    protected function getCaptchaHtml($captchaType, $cssPrefix){
        $subscrUtils = MstFactory::getSubscribeUtils();
        return $subscrUtils->getCaptchaHtml($captchaType, $cssPrefix);
    }

	
}

?>
