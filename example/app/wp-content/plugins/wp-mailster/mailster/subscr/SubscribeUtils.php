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

class MstSubscribeUtils
{

  public function getUnsubscribeURL($mail)
  {
    $log = MstFactory::getLogger();
    $env = MstFactory::getEnvironment();
    $hashUtils = MstFactory::getHashUtils();
    if (!empty($mail->id) && ($mail->id > 0)) {
      $hKey = $mail->hashkey;
      $salt = rand();
      $saltedKeyHash = $hashUtils->getUnsubscribeKey($salt, $hKey);
      $url = plugins_url();
      $query = get_site_url() . '?confirm_unsubscribe=indeed&m=' . ($mail->id) . '&h=' . $saltedKeyHash . '&sa=' . $salt;
      $log->debug('getUnsubscribeURL() query: ' . $query);
      return $query;
    }
    return false;
  }

  public function subscribeUser($name, $email, $listId, $digestChoice = false)
  {
    global $wpdb;
    $name = trim($name);
    $email = trim($email);
    $success = false;
    if ($digestChoice === false) {
      $digestChoice = MstConsts::DIGEST_NO_DIGEST;
    }
    $log = MstFactory::getLogger();
    $log->debug('subscribeUser for ' . $name . ' (email: ' . $email . ', list id: ' . $listId . ')');
    $mstRecipients = MstFactory::getRecipients();
    $cr = $mstRecipients->getTotalRecipientsCount($listId);
    if ($cr < MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_REC)) {
      $user = $this->getUserByEmail($email);
      if (!$user['user_found']) {
        $query = ' INSERT INTO '
          . $wpdb->prefix . 'mailster_users'
          . ' (id, name, email)'
          . ' VALUES ('
          . ' NULL, \'' . $wpdb->_real_escape($name) . '\', \'' . $wpdb->_real_escape($email) . '\')';
        $result = $wpdb->query($query);
        $user['user_id'] = $wpdb->insert_id;
        $user['is_core_user'] = '0';
        $log->debug('subscribeUser - ' . $name . ' needs to be stored as Mailster user, stored under ID: ' . $user['user_id']);
      }
      $success = $this->subscribeUserId($user['user_id'], $user['is_core_user'], $listId, $digestChoice);
    }
    return $success;
  }

  public function sendWelcomeOrGoodbyeSubscriberMsg($name, $email, $listId, $subType, $isOwner, $message = '')
  {
    $log = MstFactory::getLogger();
    $mailingListUtils = MstFactory::getMailingListUtils();
    $mList = $mailingListUtils->getMailingList($listId);

    $body = '';
    $altBody = '';

    if ($name && ($name !== '')) {
      $salutation = sprintf(__('Hello %s!', "wpmst-mailster"), $name);
    } else {
      $salutation = __('Hello', "wpmst-mailster");
    }

    switch ($subType) {
      case MstConsts::SUB_TYPE_SUBSCRIBE:
        $log->debug('Build welcome email for subscribe');
        $subject = sprintf(__('Welcome to %s', "wpmst-mailster"), $mList->name);
        $desc = sprintf(__('Your inquiry has been delivered to the owner of %s:', "wpmst-mailster"), $mList->name);
        if ($isOwner) {
          $desc = sprintf(__('You have a new inquiry about %s:', "wpmst-mailster"), $mList->name);
        }
        if ($mList->welcome_msg <= 0) {
          $log->debug('Sending of welcome email is disabled, return without sending');
          return;
        }
        break;
      case MstConsts::SUB_TYPE_UNSUBSCRIBE:
        $log->debug('Build goodbye email for unsubscribe');
        $subject = sprintf(__('You were removed from %s', "wpmst-mailster"), $mList->name);
        $desc = sprintf(__('You unsubscribed from %s with the email address %s.', "wpmst-mailster"), $mList->name, $email);
        if ($mList->goodbye_msg <= 0) {
          $log->debug('Sending of goodbye email is disabled, return without sending');
          return;
        }
        break;
    }

    // Prepare and purge message
    $message = stripslashes($message);
    $message = preg_replace('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}/i', '(email hidden)', $message);
    $message = preg_replace('/(?:(?:\+?1\s*(?:[.-]\s*)?)?(?:\(\s*([2-9]1[02-9]|[2-9][02-8]1|[2-9][02-8][02-9])\s*\)|([2-9]1[02-9]|[2-9][02-8]1|[2-9][02-8][02-9]))\s*(?:[.-]\s*)?)?([2-9]1[02-9]|[2-9][02-9]1|[2-9][02-9]{2})\s*(?:[.-]\s*)?([0-9]{4})(?:\s*(?:#|x\.?|ext\.?|extension)\s*(\d+))?/','(phone hidden)',$message);
    $message = preg_replace("/[a-zA-Z]*[:\/\/]*[A-Za-z0-9\-_]+\.+[A-Za-z0-9\.\/%&=\?\-_]+/i", '(link hidden)', $message);

    $instructions = 'Reply to this email address as you normally would to exchange information about booking plans and property details. '
    . ' Your personal information is kept private.';

    $body .= "<html><head></head>";
    $body .= "<body>";
    $body .= "<p>";
    $body .= $salutation;
    $body .= "</p>";
    $body .= "<p>";
    $body .= $desc;
    $body .= "</p>";
    $body .= "<p>";
    $body .= $message;
    $body .= "</p>";
    $body .= "<p>";
    $body .= $instructions;
    $body .= "</p>";
    $body .= "</body>";
    $body .= "</html>";

    $altBody .= $salutation;
    $altBody .= "\n\n";
    $altBody .= $desc;
    $altBody .= "\n\n";
    $altBody .= $message;
    $altBody .= "\n\n";
    $altBody .= "\n\n";
    $altBody .= $instructions;
    $altBody .= "\n\n";

    $log->debug('Email text: ' . $altBody);

    $mailSender = MstFactory::getMailSender();
    $mail = $mailSender->getListMailTmpl($mList);

    $replyTo = array($mList->admin_mail, $mList->name);
    try {
      $mail->addReplyTo($replyTo[0], $replyTo[1]);
    } catch (Exception $e) {
      $exceptionErrorMsg = 'Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
      $log->error('sendWelcomeOrGoodbyeSubscriberMsg addReplyTo for ' . print_r($replyTo, true) . ' caused exception: ' . $exceptionErrorMsg);
    }
    $mail->FromName = $mList->name;
    $mail->addCustomHeader(MstConsts::MAIL_HEADER_RETURN_PATH . ': <>'); // try to set return path to NULL
    $mail->addCustomHeader(MstConsts::MAIL_HEADER_AUTO_SUBMITTED . ': auto-generated'); // indicate this was generated and we do not want a response
    $mail->AddAddress($email, $name);
    $mail->setSubject($subject);
    $mail->setBody($body);
    $mail->AltBody = $altBody;
    $mail->IsHTML(true);

    $sendOk = $mail->Send(); // send notificaton
    $error = $mail->IsError();
    if ($error == true) {
      // send errors?
      $log->error('Sending of email to subscriber failed! Last error: ' . $mail->ErrorInfo);
      return false;
    } else {
      $log->debug('Successfully sent subscriber email');
    }
    return true;
  }

  /**
   * @param $email string Email to search the user with
   * @param bool $returnAsObj whether result should be returned as object instead of array
   * @param bool $enforceDataSource if no data source preference given, start with looking at WordPress users. if data source preference is given, then go when it is 1 (= this means is_core_user = 1)
   * @return array|stdClass
   */
  public function getUserByEmail($email, $returnAsObj = false, $enforceDataSource = false)
  {
    $userModel = MstFactory::getUserModel();

    $email = trim($email);
    $user = array();
    $user['user_found'] = false;
    $user['email'] = $email;
    $user['name'] = '';
    $user['description'] = '';
    global $wpdb;

    // if not data source preference given, start with looking at WordPress users
    // if data source preference is given, then go when it is 1 (= this means is_core_user = 1)
    if (($enforceDataSource === false) || ($enforceDataSource !== false) && ($enforceDataSource == 1)) {
      $query = ' SELECT *'
        . ' FROM ' . $wpdb->base_prefix . 'users'
        . ' WHERE user_email = "' . $wpdb->_real_escape($email) . '"';
      $users = $wpdb->get_results($query);
    } else {
      $users = array();
    }

    if (count($users) > 0) {
      $userData = $userModel->getUserData($users[0]->ID, true);

      $user['user_found'] = true;
      $user['user_id'] = $users[0]->ID;
      $user['is_core_user'] = '1';
      $user['name'] = $userData->name;
      $user['description'] = $userData->description;
    } else { // look at second data source (is_core_user = 0)
      $query = ' SELECT *'
        . ' FROM ' . $wpdb->prefix . 'mailster_users'
        . ' WHERE email = "' . $wpdb->_real_escape($email) . '"';
      $users = $wpdb->get_results($query);
      if (count($users) > 0) {
        $userData = $userModel->getUserData($users[0]->id, false);
        $user['user_found'] = true;
        $user['user_id'] = $users[0]->id;
        $user['is_core_user'] = '0';
        $user['name'] = $userData->name;
        $user['description'] = $userData->description;
      }
    }
    if ($returnAsObj) {
      $convUtils = MstFactory::getConverterUtils();
      $user = $convUtils->array2Object($user);
    }
    return $user;
  }


  public function subscribeUserId($userId, $isCoreUser, $listId, $digestChoice = false)
  {
    $log = MstFactory::getLogger();
    $success = false;
    if ($digestChoice === false) {
      $digestChoice = MstConsts::DIGEST_NO_DIGEST;
    }
    global $wpdb;
    $query = ' SELECT *'
      . ' FROM ' . $wpdb->prefix . 'mailster_list_members'
      . ' WHERE list_id =' . $listId
      . ' AND user_id =' . $userId
      . ' AND is_core_user =\'' . $isCoreUser . '\'';
    $members = $wpdb->get_results($query);
    if (count($members) > 0) {
      // no need to insert, the user is already in the list
      $log->debug('subscribeUserId - user ID ' . $userId . ', isCoreUser: ' . $isCoreUser . ' does not need to be added to list ID ' . $listId . ', is already among list members');
    } else {
      $log->debug('subscribeUserId - user ID ' . $userId . ', isCoreUser: ' . $isCoreUser . ' needs to be added to list ID ' . $listId);
      $query = ' INSERT INTO '
        . $wpdb->prefix . 'mailster_list_members'
        . ' (list_id, user_id, is_core_user)'
        . ' VALUES ('
        . ' \'' . $wpdb->_real_escape($listId) . '\', \'' . $wpdb->_real_escape($userId) . '\', \'' . $isCoreUser . '\')';
      $result = $wpdb->query($query);
    }

    if ($digestChoice != MstConsts::DIGEST_NO_DIGEST) {
      $log->debug('subscribeUserId - user ID ' . $userId . ', isCoreUser: ' . $isCoreUser . ' wants to retrieve messages in digests from list ID ' . $listId . ', digest setting: ' . $digestChoice);

      $digestModel = MstFactory::getDigestModel();
      $digestObj = new stdClass();
      $digestObj->id = 0;
      $digestObj->list_id = $listId;
      $digestObj->user_id = $userId;
      $digestObj->is_core_user = $isCoreUser;
      $digestObj->digest_freq = $digestChoice;
      $digestObj->last_send_date = null;
      $digestObj->next_send_date = null;
      $digestArray = MstFactory::getConverterUtils()->object2Array($digestObj);
      $log->debug('subscribeUserId - digest array before saving: ' . print_r($digestArray, true));
      // TODO FIXME Inlcude check in digestModel->store --> gibt es schon Digest für list, user ($isCoreUser)? -> wenn ja wird es überschrieben
      $digestId = $digestModel->store($digestArray);
      $log->debug('subscribeUserId - digest stored with ID ' . $digestId);
    }

    $mstRecipients = MstFactory::getRecipients();
    $mstRecipients->recipientsUpdated($listId);  // update cache state
    $success = true;
    return $success;
  }

  public function unsubscribeUser($email, $listId)
  {
    $log = MstFactory::getLogger();
    $log->debug('unsubscribeUser for email: ' . $email . ', list id: ' . $listId);
    $email = trim($email);
    $success = false;

    $dataSourcePreference = array();
    $dataSourcePreference[] = 1; // go with WordPress users
    $dataSourcePreference[] = 0; // go with Mailster users

    for ($i = 0; $i < 2; $i++) // one iteration for WordPress Users and for Mailster Users
    {
      $user = $this->getUserByEmail($email, false, $dataSourcePreference[$i]);
      $log->debug('unsubscribeUser - found user: ' . print_r($user, true));
      if ($user['user_found']) {
        $success = $success || $this->unsubscribeUserId($user['user_id'], $user['is_core_user'], $listId);
      }
    }
    return $success;
  }

  public function subscribeUserWithDoubleOptIn($email, $name, $listId, $add2GroupId = 0, $digestChoice = false)
  {
    $log = MstFactory::getLogger();
    if ($digestChoice === false) {
      $digestChoice = MstConsts::DIGEST_NO_DIGEST;
    }
    $subsriptionInfoResult = $this->storeSubscriptionInfo($email, $name, $listId, MstConsts::SUB_TYPE_SUBSCRIBE, $add2GroupId, $digestChoice);
    $this->sendDoubleOptInRelatedEmail($email, $name, $listId, MstConsts::SUB_TYPE_SUBSCRIBE, $subsriptionInfoResult['hashkey'], $subsriptionInfoResult['subscription_id']);
    return $subsriptionInfoResult;
  }

  public function unsubscribeUserWithDoubleOptIn($email, $listId)
  {
    $log = MstFactory::getLogger();
    $user = $this->getUserByEmail($email);
    $subsriptionInfoResult = $this->storeSubscriptionInfo($email, $user['name'], $listId, MstConsts::SUB_TYPE_UNSUBSCRIBE);
    $this->sendDoubleOptInRelatedEmail($email, $user['name'], $listId, MstConsts::SUB_TYPE_UNSUBSCRIBE, $subsriptionInfoResult['hashkey'], $subsriptionInfoResult['subscription_id']);
    return $subsriptionInfoResult;
  }

  public function getSubscribeInfo($subscriptionId)
  {
    $log = MstFactory::getLogger();
    global $wpdb;
    $query = ' SELECT *'
      . ' FROM ' . $wpdb->prefix . 'mailster_subscriptions'
      . ' WHERE id =\'' . $subscriptionId . '\'';
    $subscribeInfo = $wpdb->get_row($query);
    $log->debug($query);
    return $subscribeInfo;
  }

  public function deleteSubscriptionInfo($subscriptionId)
  {
    global $wpdb;
    /*$query = ' DELETE '
                . ' FROM ' . $wpdb->prefix . 'mailster_subscriptions'
                . ' WHERE id =\'' . $subscriptionId . '\'';*/
    $affRows = $wpdb->delete(
      $wpdb->prefix . 'mailster_subscriptions',
      array("id" => $subscriptionId),
      array("%d")
    );
    if ($affRows > 0) {
      return true;
    }
    return false;
  }

  public static function getSubscriptionInfoOlderThan($ageInDays)
  {
    global $wpdb;
    $query = 'SELECT *'
      . ' FROM ' . $wpdb->prefix . 'mailster_subscriptions'
      . ' WHERE DATEDIFF(NOW(), sub_date) > ' . $ageInDays;
    $subInfos = $wpdb->get_results($query);
    return $subInfos;
  }

  public function storeSubscriptionInfo($email, $name, $listId, $subType, $add2GroupId = 0, $digestChoice = false)
  {
    if ($digestChoice === false) {
      $digestChoice = MstConsts::DIGEST_NO_DIGEST;
    }
    $result = array();
    $hashUtils = MstFactory::getHashUtils();
    $log = MstFactory::getLogger();
    $user = $this->getUserByEmail($email);
    if ($user['user_found']) {
      $userId = $user['user_id'];
    } else {
      $userId = 0;
    }

    $log->debug('Store subscription related info for: ' . print_r($user, true));

    $hashkey = $hashUtils->getSubscriptionHashkey();
    global $wpdb;
    $query = ' INSERT INTO '
      . $wpdb->prefix . 'mailster_subscriptions'
      . ' ('
      . ' list_id,'
      . ' user_id,'
      . ' add2group,'
      . ' name,'
      . ' email,'
      . ' sub_type,'
      . ' sub_date,'
      . ' hashkey,'
      . ' digest_freq'
      . ') VALUES ('
      . ' \'' . $listId . '\','
      . ' \'' . $userId . '\','
      . ' \'' . $add2GroupId . '\','
      . ' \'' . $wpdb->_real_escape($name) . '\','
      . ' \'' . $wpdb->_real_escape($email) . '\','
      . ' \'' . $subType . '\','
      . ' NOW(),'
      . ' \'' . $wpdb->_real_escape($hashkey) . '\','
      . ' \'' . $digestChoice . '\''
      . ')';
    $wpdb->query($query);
    $subscriptionId = $wpdb->insert_id;
    $log->debug('Error msg: ' . $wpdb->last_error);
    $log->debug('Hashkey: ' . $hashkey);
    $result['hashkey'] = $hashkey;
    $result['subscription_id'] = $subscriptionId;
    return $result;
  }

  public function sendDoubleOptInRelatedEmail($email, $name, $listId, $subType, $hashkey, $subscriptionId)
  {
    $log = MstFactory::getLogger();
    $mailingListUtils = MstFactory::getMailingListUtils();
    $mList = $mailingListUtils->getMailingList($listId);

    $body = '';
    $altBody = '';

    if ($name) {
      $salutation = sprintf(__('Hello %s!', "wpmst-mailster"), $name);
    } else {
      $salutation = sprintf(__('Hello!', "wpmst-mailster"));
    }

    switch ($subType) {
      case MstConsts::SUB_TYPE_SUBSCRIBE:
        $log->debug('Build subscription email for subscribe');
        $link = $this->getDoubleOptInSubscriptionConfirmationURL($hashkey, $subscriptionId);
        $subject = sprintf(__('Confirm subscription to %s', "wpmst-mailster"), $mList->name);
        $desc = sprintf(__("You requested to subscribe to %s with the email address %s.", "wpmst-mailster"), $mList->name, $email);
        $linkDesc = __('Please confirm your subscription by clicking on this link', "wpmst-mailster");
        $htmlLink = '<a href="' . $link . '">' . __('Click link for confirmation', "wpmst-mailster") . '</a>';
        break;
      case MstConsts::SUB_TYPE_UNSUBSCRIBE:
        $log->debug('Build subscription email for unsubscribe');
        $link = $this->getDoubleOptInUnubscriptionConfirmationURL($hashkey, $subscriptionId);
        $subject = sprintf(__('Confirm unsubscription from %s', "wpmst-mailster"), $mList->name);
        $desc = sprintf(__('You requested to unsubscribe from %s with the email address %s.', "wpmst-mailster"), $mList->name, $email);
        $linkDesc = __('Please confirm your unsubscription by clicking on this link', "wpmst-mailster");
        $htmlLink = '<a href="' . $link . '">' . __('Click link for confirmation', "wpmst-mailster") . '</a>';
        break;
    }

    $body .= "<html><head></head>";
    $body .= "<body>";
    $body .= "<p>";
    $body .= $salutation;
    $body .= "</p>";
    $body .= "<p>";
    $body .= $desc;
    $body .= "<br/>";
    $body .= ($linkDesc . ': ' . $htmlLink);
    $body .= "</p>";
    $body .= "</body>";
    $body .= "</html>";


    $altBody .= $salutation;
    $altBody .= "\n\n";
    $altBody .= $desc;
    $altBody .= "\n\n";
    $altBody .= ($linkDesc . ': ' . $link);
    $altBody .= "\n\n";

    $log->debug('Subscription email text: ' . $altBody);

    $mailSender = MstFactory::getMailSender();
    $mail = $mailSender->getListMailTmpl($mList);

    $replyTo = array($mList->admin_mail, '');
    try {
      $mail->addReplyTo($replyTo[0], $replyTo[1]);
    } catch (Exception $e) {
      $exceptionErrorMsg = 'Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
      $log->error('sendDoubleOptInRelatedEmail addReplyTo for ' . print_r($replyTo, true) . ' caused exception: ' . $exceptionErrorMsg);
    }
    $mail->FromName = $mList->name;
    $mail->addCustomHeader(MstConsts::MAIL_HEADER_RETURN_PATH . ': <>'); // try to set return path to NULL
    $mail->addCustomHeader(MstConsts::MAIL_HEADER_AUTO_SUBMITTED . ': auto-generated'); // indicate this was generated and we do not want a response
    try {
      $mail->AddAddress($email, $name);
    } catch (Exception $e) {
      $exceptionErrorMsg = 'Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
      $log->error('sendDoubleOptInRelatedEmail AddAdress for recipient ' . $email . ' (name: ' . $name . ') caused exception: ' . $exceptionErrorMsg);
    }
    $mail->setSubject($subject);
    $mail->setBody($body);
    $mail->AltBody = $altBody;
    $mail->IsHTML(true);

    try {
      $mail->Send();  // send notificaton
    } catch (RuntimeException $e) {
      $exceptionErrorMsg = 'Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
      $log->error('sendDoubleOptInRelatedEmail error: ' . $exceptionErrorMsg);
    }
    $error = $mail->IsError();
    if ($error == true) { // send errors?
      $log->error('Sending of double opt-in confirmation failed! Last error: ' . $mail->ErrorInfo);
      return false;
    } else {
      $log->debug('Successfully sent subscription action confirmation email');
    }
    return true;
  }

  public function getDoubleOptInSubscriptionConfirmationURL($hashkey, $subscriptionId)
  {
    $log = MstFactory::getLogger();
    $hashUtils = MstFactory::getHashUtils();
    $env = MstFactory::getEnvironment();
    $salt = rand();
    $saltedKeyHash = $hashUtils->getSubscribeKey($salt, $hashkey);
    $log->debug('SubscriptionConfirmation salt: ' . $salt);
    $log->debug('SubscriptionConfirmation hashkey: ' . $hashkey);
    $log->debug('SubscriptionConfirmation saltedKeyHash: ' . $saltedKeyHash);


    $query = get_site_url() . '?confirm_subscribe=indeed&si=' . $subscriptionId . '&sm=' . MstConsts::SUBSCRIBE_MODE_DOUBLE_OPT_IN . '&h=' . $saltedKeyHash . '&sa=' . $salt;
    $log->debug('SubscriptionConfirmation url: ' . $query);
    return $query;
  }

  public function getDoubleOptInUnubscriptionConfirmationURL($hashkey, $subscriptionId)
  {
    $log = MstFactory::getLogger();
    $hashUtils = MstFactory::getHashUtils();
    $env = MstFactory::getEnvironment();
    $salt = rand();
    $saltedKeyHash = $hashUtils->getUnsubscribeKey($salt, $hashkey);
    $log->debug('UnsubscriptionConfirmation salt: ' . $salt);
    $log->debug('UnsubscriptionConfirmation hashkey: ' . $hashkey);
    $log->debug('UnsubscriptionConfirmation saltedKeyHash: ' . $saltedKeyHash);
    $url = plugins_url();
    $query = get_site_url() . '?confirm_unsubscribe=indeed&si=' . $subscriptionId . '&sm=' . MstConsts::UNSUBSCRIBE_MODE_DOUBLE_OPT_IN . '&h=' . $saltedKeyHash . '&sa=' . $salt;
    $log->debug('UnsubscriptionConfirmation url: ' . $query);
    return $query;
  }

  public function unsubscribeUserId($userId, $isCoreUser, $listId, $directSubscribersUnsubscribeOnly = false)
  {
    $log = MstFactory::getLogger();
    $log->debug('Unsubscribe user id: ' . $userId . ' (isCoreUser: ' . $isCoreUser . ') from list id: ' . $listId);
    $success = false;
    global $wpdb;
    if (!$directSubscribersUnsubscribeOnly) {
      $query = ' DELETE '
        . ' FROM ' . $wpdb->prefix . 'mailster_group_users'
        . ' WHERE user_id =\'' . $userId . '\''
        . ' AND is_core_user =\'' . $isCoreUser . '\''
        . ' AND group_id IN ('
        . ' 	SELECT group_id'
        . ' 	FROM ' . $wpdb->prefix . 'mailster_list_groups'
        . '		WHERE list_id = \'' . $listId . '\')';
      $affRows = $wpdb->query($query);
      if ($affRows > 0) {
        $success = true;
      }
      $log->debug('Affected rows (group users): ' . $affRows);
    }
    $query = ' DELETE '
      . ' FROM ' . $wpdb->prefix . 'mailster_list_members'
      . ' WHERE user_id =\'' . $userId . '\''
      . ' AND is_core_user =\'' . $isCoreUser . '\''
      . ' AND list_id = \'' . $listId . '\'';
    $affRows = $wpdb->query($query);
    if ($affRows > 0) {
      $success = true;
    }
    $log->debug('Affected rows (list members): ' . $affRows);
    $mstRecipients = MstFactory::getRecipients();
    $mstRecipients->recipientsUpdated($listId);  // update cache state
    return $success;
  }

  public function isUserLoggedIn()
  {
    $user = wp_get_current_user();
    if ($user->ID) {
      return true;
    }
    return false;
  }

  public function getMailingLists2RegisterAt($onlyPublicRegistration, $onlyActiveLists = false)
  {
    $publicRegistration = $onlyPublicRegistration ? '1' : '0';
    global $wpdb;
    $query = ' SELECT *'
      . ' FROM ' . $wpdb->prefix . 'mailster_lists'
      . ' WHERE allow_subscribe =\'1\'';
    if ($onlyPublicRegistration == true) {
      $query = $query . ' AND public_registration =' . $publicRegistration;
    }
    if ($onlyActiveLists) {
      $query = $query . ' AND active = \'1\'';
    }

    $lists = $wpdb->get_results($query);
    return $lists;
  }

  public function getMailingLists2UnsubscribeFrom()
  {
    global $wpdb;
    $query = ' SELECT *'
      . ' FROM ' . $wpdb->prefix . 'mailster_lists'
      . ' WHERE allow_unsubscribe =\'1\'';
    $lists = $wpdb->get_results($query);
    return $lists;
  }

  public function isUserSubscribedToMailingList($userId, $isCoreUser, $listId)
  {
    $mstRecips = MstFactory::getRecipients();
    $recipients = $mstRecips->getRecipients($listId);
    $recipCount = count($recipients);
    for ($j = 0; $j < $recipCount; $j++) {
      $recipient = &$recipients[$j];
      if ($userId == $recipient->user_id && $isCoreUser == $recipient->is_core_user) {
        return true;
      }
    }
    return false;
  }


  public function isSmartHideActive($formType, $settings)
  {
    $log = MstFactory::getLogger();
    $listUtils = MstFactory::getMailingListUtils();
    $recipUtils = MstFactory::getRecipients();
    $subscrUtils = MstFactory::getSubscribeUtils();

    $formType = strtolower(trim($formType));
    if ($formType === 'subscribe') {
      $memberHidesForm = true;
      $nonMemberHidesForm = false;
    } elseif ($formType === 'unsubscribe') {
      $memberHidesForm = false;
      $nonMemberHidesForm = true;
    }

    if ($settings['smartHide'] == true) {
      if ($settings['allLists'] == false) {
        $mList = null;
        if ($settings['listIdSpecified']) {
          $mList = $listUtils->getMailingList($settings['listId']);
        } else if ($settings['listNameSpecified']) {
          $mList = $listUtils->getMailingListByName($settings['listName']);
        }
        if (!is_null($mList) && $mList) {
          if ($subscrUtils->isUserLoggedIn()) {
            $user = wp_get_current_user();
            $email = $user->user_email;
            $isMember = $recipUtils->isRecipient($mList->id, $email);

            if ($memberHidesForm && $isMember) {
              return true; // smart hide on
            }
            if ($nonMemberHidesForm && !$isMember) {
              return true; // smart hide on
            }
          }
        }
      }
    }
    return false; // can not do smart hide
  }

  public function filterMailingListsForSmartHide($lists, $formType, $settings)
  {
    $log = MstFactory::getLogger();
    $recipUtils = MstFactory::getRecipients();
    $subscrUtils = MstFactory::getSubscribeUtils();
    $filteredLists = array();

    $formType = strtolower(trim($formType));
    if ($formType === 'subscribe') {
      $removeFromListWhenMember = true;
      $removeFromListWhenNotMember = false;
    } elseif ($formType === 'unsubscribe') {
      $removeFromListWhenMember = false;
      $removeFromListWhenNotMember = true;
    }

    if ($settings['smartHide'] == true) {
      if (!is_null($lists) && !empty($lists)) {
        if ($subscrUtils->isUserLoggedIn()) {
          $log->debug('Smart hide active, user logged in, we can filter mailing list choice, lists now: ' . print_r($lists, true));
          $user = wp_get_current_user();
          $email = $user->user_email;

          foreach ($lists AS $list) {
            $isMember = $recipUtils->isRecipient($list->id, $email);
            if ($isMember && !$removeFromListWhenMember) {
              $filteredLists[] = $list;
            } elseif (!$isMember && !$removeFromListWhenNotMember) {
              $filteredLists[] = $list;
            } else {
              $log->debug('List is filtered from ' . $formType . ' form: ' . $list->name . ' (ID: ' . $list->id . ')');
            }
          }
          $log->debug('Filtering done, result: ' . print_r($filteredLists, true));
          return $filteredLists; // filtered lists
        } else {
          $log->debug('User not logged in, we can not filter mailing list choice');
        }
      } else {
        $log->debug('No list of mailing lists available, we can no filter mailing list choice');
      }
    } else {
      $log->debug('Smart hide not active, we can not filter mailing list choice');
    }
    return $lists; // unfiltered lists
  }

  public function getDropdownFromLists($lists)
  {
    $listCount = count($lists);
    if ($listCount > 0) {
      $html = '<select name="' . MstConsts::SUBSCR_ID_FIELD . '">';
      for ($i = 0; $i < $listCount; $i++) {
        $html = $html . '<option value="' . $lists[$i]->id . '">' . $lists[$i]->name . '</option>';
      }
      $html = $html . '</select>';
    } else {
      $html = '<select name="' . MstConsts::SUBSCR_ID_FIELD . '">';
      $html = $html . '<option value="0">' . __(' - No mailing list - ', "wpmst-mailster") . '</option>';
      $html = $html . '</select>';
    }
    return $html;
  }

  public function getCaptchaHtml($captchaType, $cssPrefix, $targetHtmlId = null)
  {
    $mstUtils = MstFactory::getUtils();
    $captchaType = strtolower(trim($captchaType));
    if (strlen($captchaType) > 0) {
      $captchaTxt = '<span style="color: #f00;" class="' . $cssPrefix . 'errorMessage ' . $cssPrefix . 'message">' . __('Captcha', "wpmst-mailster") . ': ' . sprintf(__('Available in %s', "wpmst-mailster"), MstFactory::getV()->getMinV4Ft(MstVersionMgmt::MST_FT_ID_CAPTCHA)) . '</span>';

      if (MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_CAPTCHA)) {
        $mstCaptcha = $mstUtils->getCaptcha($captchaType, $cssPrefix);
        $captchaTxt = $mstCaptcha->getHtml(null, $cssPrefix, $targetHtmlId);

        if (!$mstCaptcha->htmlOk()) {
          $captchaTxt = '<span class="' . $cssPrefix . 'errorMessage ' . $cssPrefix . 'message error">' . $captchaTxt . '</span>';
        }

        if ($mstCaptcha->twoCols) {
          $captchaTxt = '<span class="' . $cssPrefix . 'captchaQuestion">' . $mstCaptcha->firstCol . '</span>'
            . '<span class="' . $cssPrefix . 'captcha">'
            . $captchaTxt
            . '</span>' . "\n";
        } else {
          $captchaTxt = $captchaTxt . "\n";
        }
      }
      return $captchaTxt;
    } else {
      return '';
    }
  }
}
