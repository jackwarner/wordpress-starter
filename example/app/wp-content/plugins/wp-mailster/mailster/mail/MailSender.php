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
  die('These droids are not the droids you are looking for.');
}

class MstMailSender
{
  protected $to_general;
  protected $cc_general;
  protected $bcc_general;

  protected $to;
  protected $cc;
  protected $bcc;

  function sendMails($minDuration, $execEnd)
  {
    $mstQueue = MstFactory::getMailQueue();
    $mstDigestSender = MstFactory::getDigestSender();
    $mailList = $mstQueue->getPendingMails();
    $nrEmailsSent = $this->sendPendingMails($mailList, $minDuration, $execEnd);
    $pendingDigests = $mstQueue->getPendingDigests();
    $nrDigestsSent = $mstDigestSender->sendPendingDigests($pendingDigests, $minDuration, $execEnd);
    return ($nrEmailsSent + $nrDigestsSent);
  }

  function sendMailsOfMailingList($listId, $minDuration, $execEnd)
  {
    $mstQueue = MstFactory::getMailQueue();
    $mstDigestSender = MstFactory::getDigestSender();
    $mailList = $mstQueue->getPendingMailsOfMailingList($listId);
    $nrEmailsSent = $this->sendPendingMails($mailList, $minDuration, $execEnd);
    $pendingDigests = $mstQueue->getPendingDigestsOfMailingList($listId);
    $nrDigestsSent = $mstDigestSender->sendPendingDigests($pendingDigests, $minDuration, $execEnd);
    return ($nrEmailsSent + $nrDigestsSent);
  }

  function sendPendingMails($mailList, $minDuration, $execEnd)
  {
    $log = MstFactory::getLogger();
    $mailCount = count($mailList);
    $nrEmailsSent = 0;
    if ($mailCount > 0) {
      $log->debug('Mail Count to send: ' . $mailCount, MstConsts::LOGENTRY_MAIL_SEND);
      for ($i = 0; $i < $mailCount; $i++) {
        $log->debug('Time left to run: ' . ($execEnd - time()) . ' for sending mails (execEnd: ' . $execEnd . ', minDuration: ' . $minDuration . ')', MstConsts::LOGENTRY_MAIL_SEND);
        if (($execEnd - time()) > $minDuration) {
          $mail = $mailList[$i];
          $nrEmailsSent = $nrEmailsSent + $this->sendPendingMail($mail, $minDuration, $execEnd);
        } else {
          $log->debug('Timeout, do not work on next pending mail', MstConsts::LOGENTRY_MAIL_SEND);
          break;
        }
      }
    } else {
      $log->debug('No mails to send', MstConsts::LOGENTRY_MAIL_SEND);
    }
    return $nrEmailsSent;
  }

  function getSessionTriggerSrcInfo()
  {
    $sessionInfo = microtime(true) . ' ';
    if (isset($_SERVER['REQUEST_TIME'])) {
      $sessionInfo .= '-rt-' . $_SERVER['REQUEST_TIME'];
    }
    if (isset($_SERVER['REMOTE_ADDR'])) {
      $sessionInfo .= ' -ra-' . $_SERVER['REMOTE_ADDR'];
    }
    if (isset($_SERVER['REMOTE_PORT'])) {
      $sessionInfo .= ' -rp-' . $_SERVER['REMOTE_PORT'];
    }
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
      $sessionInfo .= ' -hua-' . $_SERVER['HTTP_USER_AGENT'];
    }
    if (isset($_SERVER['HTTP_COOKIE'])) {
      $sessionInfo .= ' -hc-' . $_SERVER['HTTP_COOKIE'];
    }
    return $sessionInfo;
  }

  function sendPendingMail($mail, $minDuration, $execEnd)
  {
    $log = MstFactory::getLogger();
    $mstApp = MstFactory::getApplication();
    $mstQueue = MstFactory::getMailQueue();
    $mstConfig = MstFactory::getConfig();
    $mstUtils = MstFactory::getUtils();
    $mailingListUtils = MstFactory::getMailingListUtils();
    $timeout = false;
    $nrEmailsSentInFunctCall = 0;

    $log->debug('Mail to sent is from list ' . $mail->list_id . ', mail to be sent now: ' . $mail->id, MstConsts::LOGENTRY_MAIL_SEND);
    $mList = $mailingListUtils->getMailingList($mail->list_id);

    $listLocked = $mailingListUtils->isListLocked($mail->list_id);

    if ($listLocked) {
      $log->debug('List ' . $mail->list_id . ' of mail is locked!');
      $listLockInvalid = $mailingListUtils->isListLockInvalid($mail->list_id);
      if ($listLockInvalid) {
        $log->debug('Lock of list ' . $mail->list_id . ' is invalid, continue with sending (but do not reset lock)');
      } else {
        $log->debug('Do not prepare email further, locking of list ' . $mail->list_id . ' is valid');
        return; // exit function
      }
    }

    $sendThrottlingActive = $mailingListUtils->isSendThrottlingActive();
    $sendLimitReached = ($sendThrottlingActive && $mailingListUtils->isSendLimitReached());
    if ($sendLimitReached) {
      $log->debug('Do not prepare email further, send limit reached');
      return; // exit function
    }

    $globalInBetweenMailsThrottlingActive = false;
    $waitBetweenTwoMails = $mstConfig->getWaitBetweenTwoMails();
    if (MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_THROTTLE) && ($waitBetweenTwoMails > 0)) {
      $globalInBetweenMailsThrottlingActive = true;
      $log->debug('"Wait between Two Mails" Throttling ACTIVE');
    } else {
      $log->debug('"Wait between Two Mails" Throttling not active');
    }

    // ####### SAVE SEND EVENT  ########
    global $mailster_trigger_source;
    $sessionId = (substr(base_convert(md5($this->getSessionTriggerSrcInfo()), 16, 10), 0, 9) + 0);
    $sendEvents = MstFactory::getSendEvents();
    $sendEvents->sendingRunStarted($mail->id, $mailster_trigger_source, $sessionId);
    // #################################

    $maxSendAttempts = $mList->max_send_attempts > 0 ? $mList->max_send_attempts : 3;
    $nrBCC = $mList->bcc_count > 0 ? $mList->bcc_count : 10;

    if ($mList->addressing_mode == MstConsts::ADDRESSING_MODE_TO) {
      $log->debug('Not using BCC or CC, send one mail to one recipient at a time', MstConsts::LOGENTRY_MAIL_SEND);
      $nrRecipients = 1;
    } elseif ($mList->addressing_mode == MstConsts::ADDRESSING_MODE_BCC) {
      $log->debug('Using BCC send to ' . $nrBCC . ' recipients at a time', MstConsts::LOGENTRY_MAIL_SEND);
      $nrRecipients = $nrBCC;
    } elseif ($mList->addressing_mode == MstConsts::ADDRESSING_MODE_CC) {
      $log->debug('Using CC send to all recipients with one mail', MstConsts::LOGENTRY_MAIL_SEND);
      $nrRecipients = 0; // no limit, all recipients
    }

    if ($this->needToCorrectBCCAndCCOnWindows($mList)) {
      $log->warning('Running on Windows with WordPress Mailer -> no CC and BCC possible', MstConsts::LOGENTRY_MAIL_SEND);
      $nrRecipients = 1;
    }

    $recipCount = 1;
    $sendError = false;
    $preparedMail = $this->prepareMail($mail, $mList);

    // ####### SAVE SEND EVENT  ########
    $sendEvents = MstFactory::getSendEvents();
    $remainingRecipCount = $mstQueue->getNumberOfQueueEntriesForMail($mail->id);
    $mailSendErrorCount = $mstQueue->getErrorCountOfMail($mail->id);
    $sendEvents->mailPrepared($mail->id, $remainingRecipCount, $mailSendErrorCount);
    // #################################

    $log->debug('Session info before: ' . $this->getSessionTriggerSrcInfo(), MstConsts::LOGENTRY_MAIL_SEND);

    while (($recipCount > 0) && ($timeout == false) && ($sendLimitReached == false)) {
      $recipients = $mstQueue->getNextRecipientsInQueue($mail->id, $nrRecipients);
      $recipCount = count($recipients);
      $log->debug('recipCount of this mail: ' . $recipCount, MstConsts::LOGENTRY_MAIL_SEND);
      if ($globalInBetweenMailsThrottlingActive) {
        if ($waitBetweenTwoMails > 0) {
          $log->debug('"Wait between Two Mails" Throttling Active, setting: ' . $waitBetweenTwoMails . ' seconds');
          $lastMailSentAt = $mstConfig->getLastMailSentAt();
          $timeDiffSinceLastMail = (time() - $lastMailSentAt);
          if ($lastMailSentAt < 0) {
            $log->warning('"Wait between Two Mails" Throttling -> we have a negative result (' . $lastMailSentAt . ') for "last mail sent at"...');
          }
          if ($timeDiffSinceLastMail < 0) {
            $log->warning('"Wait between Two Mails" Throttling -> we have a negative result (' . $timeDiffSinceLastMail . ') for the "time difference"...');
          }
          if (($timeDiffSinceLastMail >= $waitBetweenTwoMails) || ($timeDiffSinceLastMail < 0) || ($lastMailSentAt < 0)) {
            $log->debug('"Wait between Two Mails" Throttling -> Time diff since last mail is ' . $timeDiffSinceLastMail . ' seconds, enough, therefore proceed...');
          } else {
            $timeToSleepNow = ($waitBetweenTwoMails - $timeDiffSinceLastMail);
            $log->debug('"Wait between Two Mails" Throttling -> Time diff since last mail is only ' . $timeDiffSinceLastMail . ' seconds, not enough, therefore we have to pause for ' . $timeToSleepNow . ' seconds!');
            if ($timeToSleepNow > 0) {
              sleep($timeToSleepNow);
              $log->debug('Back from sleeping!');
            } else {
              $log->warning('"Wait between Two Mails" Throttling -> Do not sleep, negative time: ' . $timeToSleepNow);
            }
          }
        }
      }
      if (($execEnd - time()) <= $minDuration) {
        $timeout = true;
        $log->info('Timeout in before sending next mail (time left: ' . ($execEnd - time()) . ', minDuration: ' . $minDuration . ')', MstConsts::LOGENTRY_MAIL_SEND);
        break;
      }
      if ($recipCount > 0) {
        $mail2send = $this->prepareMail4Recipients($preparedMail, $recipients, $mList, $nrRecipients);
        if (is_null($mail2send)) {
          $log->info('Will NOT send mail ' . $mail->subject . ' (id: ' . $mail->id . ', list id: ' . $mail->list_id . ') to: ' . print_r($recipients, true), MstConsts::LOGENTRY_MAIL_SEND);
          $this->processSendResults(false, $mail, $recipients, $maxSendAttempts);  // no error, although not sent, remove from queue
        } else {
          // ####### SAVE SEND EVENT  ########
          $sendEvents = MstFactory::getSendEvents();
          $remainingRecipCount = $mstQueue->getNumberOfQueueEntriesForMail($mail->id);
          $mailSendErrorCount = $mstQueue->getErrorCountOfMail($mail->id);
          $toRecips = array_merge($this->to, $this->to_general);
          $ccRecips = array_merge($this->cc, $this->cc_general);
          $bccRecips = array_merge($this->bcc, $this->bcc_general);
          $recipsJson = $mstUtils->jsonEncode(array("to" => $toRecips, "cc" => $ccRecips, "bcc" => $bccRecips));
          $sendEvents->mailPreparedForRecips($mail->id, $recipsJson, $remainingRecipCount, $mailSendErrorCount);
          // #################################

          $log->info('Sending mail ' . $mail->subject . ' (id: ' . $mail->id . ', list id: ' . $mail->list_id . ') to ' . count($recipients) . ' recipients', MstConsts::LOGENTRY_MAIL_SEND);

          $loggingLevel = $mstConfig->getLoggingLevel(); // get current Logging Level
          $isDebugMode = ($loggingLevel == $log->getLoggingLevel(MstLog::DEBUG));
          $log->debug('Logging level: ' . $loggingLevel . ', is debug: ' . ($isDebugMode ? 'true' : 'false'), MstConsts::LOGENTRY_MAIL_SEND);
          if (ob_get_level()) {
            ob_end_clean(); // clean output buffering
          }
          $smtpDebugOutput = '- Not active -';
          if ($isDebugMode) {
            $log->debug('*** Start SMTP debug ***', MstConsts::LOGENTRY_MAIL_SEND);
            $log->debug('ob_get_level before: ' . ob_get_level(), MstConsts::LOGENTRY_MAIL_SEND);
            ob_start(); // activate output buffering
            $log->debug('ob_get_level after activating buffering: ' . ob_get_level(), MstConsts::LOGENTRY_MAIL_SEND);
            $mail2send->SMTPDebug = 2;
          }
          try {
            $mail2send->Send();
          } catch (RuntimeException $e) {
            $exceptionErrorMsg = 'Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
          }
          if ($isDebugMode) {
            $smtpDebugOutput = ob_get_contents();
            if (ob_get_level()) {
              ob_end_clean();  // deactivate output buffering
            }
            $log->debug('SMTP Debug Output: ' . $smtpDebugOutput, MstConsts::LOGENTRY_MAIL_SEND);
            $log->debug('ob_get_level after deactivating buffering: ' . ob_get_level(), MstConsts::LOGENTRY_MAIL_SEND);
            $log->debug('*** Stop SMTP debug ***', MstConsts::LOGENTRY_MAIL_SEND);
          }

          $error = $mail2send->IsError();
          $this->processSendResults($error, $mail, $recipients, $maxSendAttempts);
          if ($error == true) { // send errors?
            $errorMsg = 'Sending of mail ' . $mail->id . ' failed!';
            $errorMsg .= ' Last error: ' . $mail2send->ErrorInfo;
            $log->error($errorMsg, MstConsts::LOGENTRY_MAIL_SEND);

            // ####### TRIGGER NEW EVENT #######
            $mstEvents = MstFactory::getEvents();
            $mstEvents->sendError($mail->id, $errorMsg . '   SMTP DEBUG: ' . $smtpDebugOutput);
            // #################################

            // ####### SAVE SEND EVENT  ########
            $sendEvents = MstFactory::getSendEvents();
            $mailSendErrorCount = $mstQueue->getErrorCountOfMail($mail->id);
            $sendEvents->mailSendError($mail->id, $recipsJson, $errorMsg . '   SMTP DEBUG: ' . $smtpDebugOutput, $mailSendErrorCount, $maxSendAttempts);
            // #################################
            $sendError = true;
          } else {
            $log->debug('Sending of mail ' . $mail->id . ' ok', MstConsts::LOGENTRY_MAIL_SEND);
            // ####### SAVE SEND EVENT  ########
            $sendEvents = MstFactory::getSendEvents();
            $sendEvents->mailSendOk($mail->id);
            // #################################
            $nrMailsSentRecipientBased = count($this->to) + count($this->cc) + count($this->bcc); // sum up the recipient count to have the statistics right
            $mailingListUtils->writeListStat($mail->list_id, 1, $nrMailsSentRecipientBased);
            $this->writeSendStatistics($mail);
            $nrEmailsSentInFunctCall = $nrEmailsSentInFunctCall + count($recipients);
          }
          unset($mail2send); // we don't need this object anymore now
          $log->info('Time left after sending this mail: ' . ($execEnd - time()), MstConsts::LOGENTRY_MAIL_SEND);
        }
      }
      $sendLimitReached = ($sendThrottlingActive && $mailingListUtils->isSendLimitReached());
      if ($sendLimitReached) {
        $log->info('Send limit reached after sending mail', MstConsts::LOGENTRY_MAIL_SEND);
      }
      if (($execEnd - time()) <= $minDuration) {
        $timeout = true;
        $log->info('Timeout after sending mail (time left: ' . ($execEnd - time()) . ', minDuration: ' . $minDuration . ')', MstConsts::LOGENTRY_MAIL_SEND);
        break;
      }
    }
    if (($timeout == false) && ($sendError == false) && ($sendLimitReached == false)) {
      // either all recipients are currently locked or there are no more recipients
      $recipsInQueue = $mstQueue->getNumberOfQueueEntriesForMail($mail->id);
      if ($recipsInQueue <= 0) { // no more recipients?
        $log->info('Sending of mail ' . $mail->id . ' COMPLETE', MstConsts::LOGENTRY_MAIL_SEND);
        $mstQueue->sendingComplete($mail->id);  // all recpipients done
        // ####### SAVE SEND EVENT  ########
        $sendEvents = MstFactory::getSendEvents();
        $mailSendErrorCount = $mstQueue->getErrorCountOfMail($mail->id);
        $sendEvents->sendingFinished($mail->id, $mailSendErrorCount);
        // #################################
      } else {
        $log->debug('There are still recipients in the queue, probably locked ones', MstConsts::LOGENTRY_MAIL_SEND);
      }
    }
    // ####### SAVE SEND EVENT  ########
    global $mailster_trigger_source;
    $sendEvents = MstFactory::getSendEvents();
    $sendEvents->sendingRunStopped($mail->id, $mailster_trigger_source, $sessionId);
    // #################################

    return $nrEmailsSentInFunctCall;
  }

  function writeSendStatistics($mail)
  {
    $log = MstFactory::getLogger();
    $mstConfig = MstFactory::getConfig();
    $mailingListUtils = MstFactory::getMailingListUtils();

    $log->debug('writeSendStatistics()');

    $mailingListUtils->setLastMailSent($mail->list_id);

    $lastMailSentAt = $mstConfig->getLastMailSentAt();
    $lastHourMailSentIn = $mstConfig->getLastHourMailSentIn();
    $lastDayMailSentIn = $mstConfig->getLastDayMailSentIn();
    $nrOfMailsSentInLastHour = $mstConfig->getNrOfMailsSentInLastHour();
    $nrOfMailsSentInLastDay = $mstConfig->getNrOfMailsSentInLastDay();

    $log->debug('writeSendStatistics BEFORE: '
      . 'lastMailSentAt: ' . $lastMailSentAt . ', '
      . 'lastHourMailSentIn: ' . $lastHourMailSentIn . ', '
      . 'lastDayMailSentIn: ' . $lastDayMailSentIn . ', '
      . 'nrOfMailsSentInLastHour: ' . $nrOfMailsSentInLastHour . ', '
      . 'nrOfMailsSentInLastDay: ' . $nrOfMailsSentInLastDay);

    $currTime = time();
    $currDay = date("Y-m-d");
    $currHour = date("H");

    if ($lastDayMailSentIn !== $currDay) {
      $log->debug('writeSendStatistics -> new Day (' . $lastDayMailSentIn . ' VS ' . $currDay . ')');
      $nrOfMailsSentInLastHour = 0;
      $nrOfMailsSentInLastDay = 0;
    } elseif ($lastHourMailSentIn !== $currHour) {
      $log->debug('writeSendStatistics -> new Hour (' . $lastHourMailSentIn . ' VS ' . $currHour . ')');
      $nrOfMailsSentInLastHour = 0;
    }

    $lastHourMailSentIn = $currHour;
    $lastDayMailSentIn = $currDay;
    $nrOfMailsSentInLastHour++;
    $nrOfMailsSentInLastDay++;

    $mstConfig->setLastMailSentAt($currTime);
    $mstConfig->setLastHourMailSentIn($lastHourMailSentIn);
    $mstConfig->setLastDayMailSentIn($lastDayMailSentIn);
    $mstConfig->setNrOfMailsSentInLastHour($nrOfMailsSentInLastHour);
    $mstConfig->setNrOfMailsSentInLastDay($nrOfMailsSentInLastDay);

    $log->debug('writeSendStatistics AFTER: '
      . 'lastMailSentAt: ' . $currTime . ', '
      . 'lastHourMailSentIn: ' . $lastHourMailSentIn . ', '
      . 'lastDayMailSentIn: ' . $lastDayMailSentIn . ', '
      . 'nrOfMailsSentInLastHour: ' . $nrOfMailsSentInLastHour . ', '
      . 'nrOfMailsSentInLastDay: ' . $nrOfMailsSentInLastDay);
  }

  function needToCorrectBCCAndCCOnWindows($mList)
  {
    $log = MstFactory::getLogger();
    if ($mList->use_cms_mailer != '1') {
      $log->debug('We are not using the CMS mailer, no need to correct BCC settings', MstConsts::LOGENTRY_MAIL_SEND);
      return false; // using smtp is fine
    }
    $jMailer =& MstFactory::getMailer();
    $jMailerType = strtolower($jMailer->Mailer);
    $log->debug('CMS Mailer Type: ' . $jMailerType, MstConsts::LOGENTRY_MAIL_SEND);
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
      if ($jMailerType !== 'smtp') {
        $log->info('Using PHP mail or sendmail function with windows -> we cannot use BCC addresses -> reset to 1', MstConsts::LOGENTRY_MAIL_SEND);
        return true; // only one recipient per mail possible with mail() function on windows, smtp works
      }
    }
    return false;
  }

  /**
   * @param stdClass $mail mail object
   * @param stdClass $mList mailing list object
   * @return MstMailer
   */
  function prepareMail($mail, $mList)
  {// Prepare E-Mail without specifying recipient...
    $log = MstFactory::getLogger();
    $mstUtils = MstFactory::getUtils();
    $mailUtils = MstFactory::getMailUtils();
    $mstConfig = MstFactory::getConfig();
    $threadUtils = MstFactory::getThreadUtils();
    $attachUtils = MstFactory::getAttachmentsUtils();
    $env = MstFactory::getEnvironment();

    $this->to = array();
    $this->cc = array();
    $this->bcc = array();
    $this->to_general = array();
    $this->cc_general = array();
    $this->bcc_general = array();

    $log->debug('Prepare general mail content, working with: ' . print_r($mail, true), MstConsts::LOGENTRY_MAIL_SEND);

    // add/remove/convert parts according to list settings (i.e. HTML only mail without plain text part or vice versa)
    $mail = $mailUtils->addRemoveConvertBodyParts($mail, $mList);
    // do modifications (header, footer, subject)
    $mail = $mailUtils->modifyMailContent($mList, $mail);
    // load template
    $mail2send = $this->getListMailTmpl($mList);

    $noFromName = (is_null($mail->from_name) || (trim($mail->from_name) === ''));

    $mailingListAsFrom = false;
    if ($mList->mail_from_mode == MstConsts::MAIL_FROM_MODE_GLOBAL) {
      if ($mstConfig->useMailingListAddressAsFromField()) {
        $mailingListAsFrom = true;
      } else {
        $mailingListAsFrom = false;
      }
    } elseif ($mList->mail_from_mode == MstConsts::MAIL_FROM_MODE_MAILING_LIST) {
      $mailingListAsFrom = true;
    } elseif ($mList->mail_from_mode == MstConsts::MAIL_FROM_MODE_SENDER_EMAIL) {
      $mailingListAsFrom = false;

    }

    $mailingListAsName = false;
    if ($mList->name_from_mode == MstConsts::NAME_FROM_MODE_GLOBAL) {
      if ($mstConfig->useMailingListNameAsFromField()) {
        $mailingListAsName = true;
      } else {
        $mailingListAsName = false;
      }
    } elseif ($mList->name_from_mode == MstConsts::NAME_FROM_MODE_MAILING_LIST_NAME) {
      $mailingListAsName = true;
    } elseif ($mList->name_from_mode == MstConsts::NAME_FROM_MODE_SENDER_NAME) {
      $mailingListAsName = false;
    }

    if ($mailingListAsFrom) {
      $mail2send->From = trim($mList->list_mail);
      $log->debug('Set as FROM address (should be mailing list address): ' . $mail2send->From, MstConsts::LOGENTRY_MAIL_SEND);
    } else {
      $mail2send->From = trim($mail->from_email);
      $log->debug('Set as FROM address (should be the sender address): ' . $mail2send->From, MstConsts::LOGENTRY_MAIL_SEND);
    }

    if ($mailingListAsName) {
      $mail2send->FromName = trim(str_replace(',', ' ', $mList->name)); // names may not contain commas
      $log->debug('Set as FROM name (should be the mailing list name): ' . $mail2send->FromName, MstConsts::LOGENTRY_MAIL_SEND);
    } else {
      if ($noFromName) {
        if ($mstConfig->insertSenderAddressForEmptySenderName()) {
          $mail2send->FromName = trim(str_replace(',', ' ', $mail->from_email));
          $log->debug('Set as FROM name (should be sender email address): ' . $mail2send->FromName, MstConsts::LOGENTRY_MAIL_SEND);
        } else {
          $mail2send->FromName = '';
          $log->debug('Set as FROM name (should be empty): ' . $mail2send->FromName, MstConsts::LOGENTRY_MAIL_SEND);
        }
      } else {
        $mail2send->FromName = trim(str_replace(',', ' ', $mail->from_name));
        $log->debug('Set as FROM name (should be the sender name): ' . $mail2send->FromName, MstConsts::LOGENTRY_MAIL_SEND);
      }
    }

    // Bounce Handling here
    $bounceAddress = trim($mList->list_mail); // default
    if ($mList->bounce_mode == MstConsts::BOUNCE_MODE_LIST_ADDRESS) {
      $bounceAddress = trim($mList->list_mail); // bounces return to list
    } elseif ($mList->bounce_mode == MstConsts::BOUNCE_MODE_DEDICATED_ADDRESS) {
      $bounceAddress = trim($mList->bounce_mail); // bounces go to dedicated and fixed address
    }

    $mail2send->addCustomHeader(MstConsts::MAIL_HEADER_RETURN_PATH . ': <' . $bounceAddress . '>'); // try to set return path
    $mail2send->addCustomHeader(MstConsts::MAIL_HEADER_ERRORS_TO . ': ' . $bounceAddress); // try to ensure return/error path

    // Fixed in Mailster 0.4.1 -> Sender is always the mailing list...
    $senderAddress = trim($mList->list_mail); // default
    $mail2send->Sender = $senderAddress;
    $mail2send->addCustomHeader(MstConsts::MAIL_HEADER_SENDER . ': ' . $senderAddress); //  make sure Sender is really set correct

    if ($mstConfig->addMailsterMailHeaderTag()) {
      $mail2send->addCustomHeader(MstConsts::MAIL_HEADER_MAILSTER_TAG); // tag mail as a Mailster mail
    }

    if ((!is_null($mail->in_reply_to)) && (strlen($mail->in_reply_to) > 0)) {
      // This mail is a reply
      $log->debug('This is a reply, adding In-Reply-To header...', MstConsts::LOGENTRY_MAIL_SEND);
      $mail2send->addCustomHeader(MstConsts::MAIL_HEADER_IN_REPLY_TO . ': ' . $mail->in_reply_to);
      if ($mList->clean_up_subject > 0) {
        $mail->subject = $threadUtils->getThreadSubject($mail->thread_id);
        $log->debug('Subject after cleanup: ' . $mail->subject);
        // as we just undo the subject modifications (e.g. prefix), we have to do this again:
        $mail->subject = $mailUtils->modifyMailSubject($mail, $mList);
        $log->debug('Subject modifying it again: ' . $mail->subject);
      }
      $replyPrefix = $mstConfig->getReplyPrefix();
      if ($mstConfig->addSubjectPrefixToReplies()) {
        $log->debug('Adding reply prefix: ' . $replyPrefix, MstConsts::LOGENTRY_MAIL_SEND);
        $mail->subject = $replyPrefix . ' ' . $mail->subject;
      } else {
        $log->debug('Do not add reply prefix (' . $replyPrefix . ')', MstConsts::LOGENTRY_MAIL_SEND);
      }
    }

    $mail2send->setSubject($mail->subject);

    if (is_null($mail->html) || strlen(trim($mail->html)) < 1) {
      $log->debug('Send as plain text mail', MstConsts::LOGENTRY_MAIL_SEND);
      //$log->debug('Body before line splitting: '.$mail->body, MstConsts::LOGENTRY_MAIL_SEND);
      //$mail->body = $mailUtils->mb_chunk_split($mail->body);
      //$log->debug('Body after line splitting: '.$mail->body, MstConsts::LOGENTRY_MAIL_SEND);
      $mail2send->setBody($mail->body);
    } else {
      $log->debug('Send as html mail', MstConsts::LOGENTRY_MAIL_SEND);
      $mail->html = $mailUtils->htmlWordwrapIfNeeded($mail->html);
      //$mail->body = $mailUtils->mb_chunk_split($mail->body);
      $mail2send->IsHTML(true);
      $mail2send->Body = $mail->html;
      $mail2send->AltBody = $mail->body;
    }

    $mail2send->addCustomHeader(MstConsts::MAIL_HEADER_PRECEDENCE . ': list');
    $mail2send->addCustomHeader($mailUtils->getListIDMailHeader($mList));
    $mail2send->addCustomHeader(MstConsts::MAIL_HEADER_LIST_UNSUBSCRIBE . ': <mailto:' . trim($mList->admin_mail) . '?subject=unsubscribe>'); // admin gets unsubscribe requests
    $mail2send->addCustomHeader(MstConsts::MAIL_HEADER_LIST_ARCHIVE . ': <' . home_url() . '>'); // archive currently not directly linked
    $mail2send->addCustomHeader(MstConsts::MAIL_HEADER_LIST_POST . ': <mailto:' . trim($mList->list_mail) . '>'); // address for posting new posts/replies
    $mail2send->addCustomHeader(MstConsts::MAIL_HEADER_LIST_HELP . ': <mailto:' . trim($mList->admin_mail) . '?subject=help>'); // admin gets help requests
    $mail2send->addCustomHeader(MstConsts::MAIL_HEADER_LIST_SUBSCRIBE . ': <mailto:' . trim($mList->admin_mail) . '?subject=subscribe>'); // admin gets subscribe requests
    $mail2send->addCustomHeader(MstConsts::MAIL_HEADER_MSG_ID . ': ' . $mail->id); // insert mail ID, this can be used to identify the mail within Mailster
    $mail2send->addCustomHeader(MstConsts::MAIL_HEADER_BEEN_THERE . ': ' . trim($mList->list_mail)); // we have been here...
    //	$mail2send->addCustomHeader(MstConsts::MAIL_HEADER_MAILSTER_DEBUG . ': ' . $this->getSessionTriggerSrcInfo()); // optional session info (debug purposes)

    $mstRef = $threadUtils->getThreadReference($mail->thread_id);
    $references = $threadUtils->getAllReferencesOfThread($mail->thread_id, 30); // max 30 references
    $references = $mail->message_id . ' ' . $mstRef . ' ' . $references;
    $log->debug('All references: ' . $references, MstConsts::LOGENTRY_MAIL_SEND);
    $mail2send->addCustomHeader(MstConsts::MAIL_HEADER_REFERENCES . ': ' . $references);

    if ($mList->addressing_mode == MstConsts::ADDRESSING_MODE_CC) {
      $mail2send->addCustomHeader(MstConsts::MAIL_HEADER_CC . ': ' . trim(str_replace(',', ' ', $mList->name)) . ' <' . trim($mList->list_mail) . '>');
      $log->debug('CC addressing: Added List address ' . trim($mList->list_mail) . ' as CC recipient because of CC addressing mode', MstConsts::LOGENTRY_MAIL_SEND);
      $recipInfoArr = array("email" => trim($mList->list_mail), "name" => trim(str_replace(',', ' ', $mList->name)));
      $this->cc_general[] = $recipInfoArr;
    } elseif ($mList->addressing_mode == MstConsts::ADDRESSING_MODE_BCC) {
      $mail2send->AddAddress(trim($mList->list_mail), trim(str_replace(',', ' ', $mList->name)));
      $log->debug('BCC addressing: Added List address ' . trim($mList->list_mail) . ' as To recipient...', MstConsts::LOGENTRY_MAIL_SEND);
      $recipInfoArr = array("email" => trim($mList->list_mail), "name" => trim(str_replace(',', ' ', $mList->name)));
      $this->to_general[] = $recipInfoArr;

      if ($mList->incl_orig_headers > 0) {
        $log->debug('Inlude original headers IS ACTIVE', MstConsts::LOGENTRY_MAIL_SEND);
        $origToRecips = $mstUtils->jsonDecode($mail->orig_to_recips);
        $origCcRecips = $mstUtils->jsonDecode($mail->orig_cc_recips);
        foreach ($origToRecips AS $origToRecip) {
          if (strtolower(trim($origToRecip->email)) !== $mList->list_mail) {
            $mail2send->AddAddress(trim($origToRecip->email), trim(str_replace(',', ' ', $origToRecip->name)));
            $log->debug('BCC addressing: Added Original TO recipient ' . trim($origToRecip->email) . ' as To recipient...', MstConsts::LOGENTRY_MAIL_SEND);
            $recipInfoArr = array("email" => trim($origToRecip->email), "name" => trim(str_replace(',', ' ', $origToRecip->name)));
            $this->to_general[] = $recipInfoArr;
          }
        }
        foreach ($origCcRecips AS $origCcRecip) {
          if (strtolower(trim($origCcRecip->email)) !== $mList->list_mail) {
            $mail2send->addCustomHeader(MstConsts::MAIL_HEADER_CC . ': ' . trim(str_replace(',', ' ', $origCcRecip->name)) . ' <' . trim($origCcRecip->email) . '>');
            $log->debug('BCC addressing: Added Original CC recipient ' . trim($origCcRecip->email) . ' as CC recipient...', MstConsts::LOGENTRY_MAIL_SEND);
            $recipInfoArr = array("email" => trim($origCcRecip->email), "name" => trim(str_replace(',', ' ', $origCcRecip->name)));
            $this->cc_general = $recipInfoArr;
          }
        }
      }
    } else {
      $log->debug('TO addressing: Do not add list mail as addtional TO addressee', MstConsts::LOGENTRY_MAIL_SEND);
    }

    if ($mList->reply_to_sender != 2) {
      $replyTo = $mailUtils->getReplyToArray($mList, $mail->from_email, trim(str_replace(',', ' ', $mail->from_name)));
      $mail2send->addReplyTo($replyTo[0], $replyTo[1]); // only in the cases where exact one address should be the reply-to destination
    }

    if ($mail->has_attachments === '1') { // add attachments...
      $attachs = $attachUtils->getAttachmentsOfMail($mail->id);
      $log->debug('prepareMail: has ' . count($attachs) . ' attachments...', MstConsts::LOGENTRY_MAIL_SEND);
      for ($k = 0; $k < count($attachs); $k++) {
        $log->debug('prepareMail: ----------- add attachment ' . ($k + 1) . ' -----------', MstConsts::LOGENTRY_MAIL_SEND);
        $attach = &$attachs[$k];
        $upload_dir = wp_upload_dir();
        $basepath = $upload_dir['basedir'];
        $pathToImage = $basepath . trim($attach->filepath);
        $filePath = $pathToImage . "/" . $attach->filename;
        $log->debug('Filepath of attachment: ' . $filePath);
        $newFilename = rawurldecode($attach->filename);
        $log->debug('File name before URL decode: ' . $attach->filename);
        $log->debug('File name after URL decode: ' . $newFilename);
        $typeStr = $attachUtils->getAttachmentTypeString($attach->type, $attach->subtype);
        $params = trim($attach->params);
        $log->debug('prepareMail: has type: ' . $typeStr, MstConsts::LOGENTRY_MAIL_SEND);

        if ($attach->disposition == MstConsts::DISPOSITION_TYPE_ATTACH) {
          if (strtoupper(trim($attach->subtype)) === 'CALENDAR') {
            $log->debug('prepareMail: adding as calendar entry: ' . $filePath, MstConsts::LOGENTRY_MAIL_SEND);
            if (strtolower(trim($attach->filename)) === strtolower(trim(MstConsts::ATTACHMENT_NO_FILENAME_FOUND))) {
              $log->debug('prepareMail: we have no filename for this calendar entry, take "meeting.ics"', MstConsts::LOGENTRY_MAIL_SEND);
              $newFilename = 'meeting.ics';
            }
          }
          $log->debug('prepareMail: adding as attachment: ' . $newFilename . ', type: ' . $typeStr . ', params: ' . $params . ' (path: ' . $filePath . ')', MstConsts::LOGENTRY_MAIL_SEND);
          $mail2send->AddAttachment($filePath, $newFilename, 'base64', $typeStr . $params);
        } elseif ($attach->disposition == MstConsts::DISPOSITION_TYPE_INLINE) {
          $contentId = $attach->content_id;
          $noContentIdProvided = (is_null($contentId) || (trim($contentId) === ''));
          if ($noContentIdProvided) {
            $log->debug('No content id provided, take file name as content id (filename is ' . $newFilename . ')');
            $contentId = $newFilename;
          }
          $log->debug('prepareMail: adding as inline attachment: content id: ' . $contentId . ', type: ' . $typeStr . ' (' . $filePath . ')', MstConsts::LOGENTRY_MAIL_SEND);
          $mail2send->AddEmbeddedImage($filePath, $contentId, '', 'base64', $typeStr . $params);
        }
        if (method_exists($mail2send, 'GetAttachments')) {
          $log->debug('Attachments now: ' . print_r($mail2send->GetAttachments(), true));
        }
      }
    }
    $log->debug('Prepared Mail: ' . print_r($mail2send, true), MstConsts::LOGENTRY_MAIL_SEND);
    return $mail2send;
  }

  /**
   * @param MstMailer $mail2send
   * @param array $recipients
   * @param stdClass $mList
   * @param int $nrRecipientsPlanned2Add
   * @return MstMailer|null
   */
  function prepareMail4Recipients($mail2send, $recipients, $mList, $nrRecipientsPlanned2Add)
  {
    $log = MstFactory::getLogger();
    $mailUtils = MstFactory::getMailUtils();
    $listEmail = $mList->list_mail;
    $recipientMail = clone($mail2send);
    $recipCount = count($recipients);
    $nrRecipsAdded = 0;

    $this->to = array();
    $this->cc = array();
    $this->bcc = array();

    for ($i = 0; $i < $recipCount; $i++) {
      $recipient = &$recipients[$i];
      if (strtolower(trim($recipient->email)) !== strtolower(trim($listEmail))) {
        $nrRecipsAdded = $nrRecipsAdded + 1;
        $log->debug('Next recipient: ' . $recipient->name . ' (' . $recipient->email . ', #errors:' . $recipient->error_count . ')', MstConsts::LOGENTRY_MAIL_SEND);

        $recipient->name = str_replace(',', ' ', $recipient->name); // names may not contain commas

        $recipInfoArr = array("email" => $recipient->email, "name" => $recipient->name);
        if ($mList->addressing_mode == MstConsts::ADDRESSING_MODE_TO) {
          $log->debug('Add ' . $recipient->email . ' to TO recipients', MstConsts::LOGENTRY_MAIL_SEND);
          $recipientMail->AddAddress($recipient->email, $recipient->name);
          $this->to[] = $recipInfoArr;
        } elseif ($mList->addressing_mode == MstConsts::ADDRESSING_MODE_BCC) {
          $log->debug('Add ' . $recipient->email . ' to BCC recipients', MstConsts::LOGENTRY_MAIL_SEND);
          $recipientMail->AddBCC($recipient->email, $recipient->name);
          $this->bcc[] = $recipInfoArr;
        } elseif ($mList->addressing_mode == MstConsts::ADDRESSING_MODE_CC) {
          $log->debug('Add ' . $recipient->email . ' to CC recipients', MstConsts::LOGENTRY_MAIL_SEND);
          $recipientMail->AddCC($recipient->email, $recipient->name); // because of "add at least one recipient" error message, seems to be buggy, at least under Windows
          //	$recipientMail->addCustomHeader(MstConsts::MAIL_HEADER_CC . ': ' . trim($recipient->name) . ' <' . trim($recipient->email) . '>'); // actual addressing
          $this->cc[] = $recipInfoArr;
        }
      } else {
        $log->warning('Do not add recipient, recipient ' . $recipient->email
          . ' is the email address of the mailing list (' . $listEmail . ')', MstConsts::LOGENTRY_MAIL_SEND);
      }
    }
    if ($nrRecipientsPlanned2Add == 1) {
      $log->debug('Only one recipient  - use replaceRecipientWildcards');
      $recipientMail->Body = $mailUtils->replaceRecipientWildcards($recipientMail->Body, $mList, $recipients);
      $recipientMail->AltBody = $mailUtils->replaceRecipientWildcards($recipientMail->AltBody, $mList, $recipients);
      $recipientMail->Subject = $mailUtils->replaceRecipientWildcards($recipientMail->Subject, $mList, $recipients);
    }
    if ($nrRecipsAdded > 0) {   // check if we have added at least one recipient
      $log->debug('Mail with recipients: ' . print_r($recipientMail, true), MstConsts::LOGENTRY_MAIL_SEND);
      return $recipientMail;
    }
    return null; // no recipient, null will indicate that this does not need to be sent
  }

  function processSendResults($error, $mail, $recipients, $maxSendAttempts)
  {
    $log = MstFactory::getLogger();
    $log->debug('MailSender->processSendResults');
    $mstQueue = MstFactory::getMailQueue();
    $recipCount = count($recipients);
    for ($i = 0; $i < $recipCount; $i++) {
      $recipient = &$recipients[$i];
      if ($error == false) { // send errors?
        $mstQueue->removeMailFromQueue($mail->id, $recipient->email);
      } else {
        $mstQueue->incrementError($mail->id, $recipient->email, $maxSendAttempts);
      }
    }
  }

  public static function getListMailTmpl($mList)
  { //TODO SOS use wp methods...
    $mail2send = MstFactory::getMailer();
    $mail2send->ClearAllRecipients();
    $mail2send->From = $mList->list_mail; // not $mail->from_email because of PHPMAILER_FROM_FAILED error
    if ($mList->use_cms_mailer != '1') {
      if (property_exists($mail2send, 'SMTPAutoTLS')) {
        $mail2send->SMTPAutoTLS = false;
      }
      $mail2send->useSMTP($mList->mail_out_use_sec_auth == '1' ? true : false,
        $mList->mail_out_host,
        $mList->mail_out_user,
        $mList->mail_out_pw,
        $mList->mail_out_use_secure,
        $mList->mail_out_port);
    }
    return $mail2send;
  }

  public static function sendMail2ListAdmin($mList, $subject, $body)
  {
    $log = MstFactory::getLogger();
    $log->debug('MailSender::sendMail2ListAdmin');
    $mail2send = self::getListMailTmpl($mList);
    $mail2send->FromName = 'Mailster';
    try {
      $mail2send->addReplyTo(trim($mList->list_mail), trim(str_replace(',', ' ', $mList->name)));
      $mail2send->AddAddress(trim($mList->admin_mail), trim(str_replace(',', ' ', $mList->name)) . ' Admin');
    } catch (RuntimeException $e) {
      $exceptionErrorMsg = 'Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
      $log->error('MailSender::sendMail2ListAdmin error during preparation: ' . $exceptionErrorMsg);
    }
    $mail2send->setSubject($subject);
    $mail2send->setBody($body);
    try {
      $mail2send->Send();
    } catch (RuntimeException $e) {
      $exceptionErrorMsg = 'Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
      $log->error('MailSender::sendMail2ListAdmin error during sending: ' . $exceptionErrorMsg);
    }
    $error = $mail2send->IsError();
    return !$error;
  }

}

?>
