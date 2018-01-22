<?php
	if ( preg_match( '#' . basename( __FILE__ ) . '#', $_SERVER['PHP_SELF'] ) ) {
		die( 'These are not the droids you are looking for.' );
	}
	$mstConfig = MstFactory::getConfig();
	$user              = 'test.mailster@brandt-oss.com';
	$pw                = 'K04fmKkda3s1f';
	$host              = 'mail.your-server.de';
	$port              = 993;
	$folder            = 'INBOX';
	$options           = '/imap/ssl';
	$host              = '{' . $host . ':' . $port . $options . '/novalidate-cert}' . $folder;
	$openTimeoutOld    = imap_timeout( IMAP_OPENTIMEOUT );
	$chgTimeoutSuccess = imap_timeout( IMAP_OPENTIMEOUT, $mstConfig->getMailboxOpenTimeout() );
	if ( ! $mbh = imap_open( $host, $user, $pw ) ) {  // not using @
		echo __( 'Not OK', 'wpmst-mailster' ) . '<br/><br/>';
		$imapErrors = imap_errors();
		if ( $imapErrors ) {
			echo __( 'Erros', 'wpmst-mailster' ) . ':<br/>';
			foreach ( $imapErrors as $error ) {
				echo $error . "<br />";
			}
		} else {
			echo __( 'Error messages available', 'wpmst-mailster' ) . '<br/>';
		}
	} else {
		echo __( 'OK', 'wpmst-mailster' );
		$imapErrors = imap_errors(); // clear (useless) notices/warnings
		imap_close( $mbh );
	}
	$chgTimeoutSuccess = imap_timeout( IMAP_OPENTIMEOUT, $openTimeoutOld );