<?php

if(version_compare(phpversion(), "5.4.0", ">=")){
    if(session_status() == PHP_SESSION_NONE){
        session_start();
    }
}else{
    if(session_id() === ''){
        session_start();
    }
}

$notSubscribed = (array_key_exists('ns', $_REQUEST) && (intval($_REQUEST['ns']) == 1)) ? true : false;
$unsubscribeCompletedOk = (array_key_exists('sc', $_REQUEST) && (intval($_REQUEST['sc']) == 1)) ? true : false;
$unsubscribeFailed = (array_key_exists('sf', $_REQUEST) && (intval($_REQUEST['sf']) == 1)) ? true : false;
$unsubscribeFormNeeded = (array_key_exists('uf', $_REQUEST) && (intval($_REQUEST['uf']) == 1)) ? true : false;

if($unsubscribeFormNeeded){
    showUnsubscribeDialog();
}elseif($notSubscribed) {
    $log->debug('Unsubscribing failed, hash was not correct');
    fail('Email address is not subscribed');
}elseif($unsubscribeFailed){
    fail('Unsubscription failed');
}elseif($unsubscribeCompletedOk){
    success();
}

function showUnsubscribeDialog(){
    ?>
    <h2 class="componentheading mailsterUnsubscriberHeader">Unsubscription</h2>
    <div class="contentpane">
        <div id="mailsterContainer">
            <div id="mailsterUnsubscriber">
                <div class="unsubscribe_header">Are you sure you want to unsubscribe?</div>
                <form action="<?php echo $_SESSION['query']; ?>" method="post" >
                    <table id="person_details">
                        <tr>
                            <th>Mailing List</th><td><?php echo $_SESSION['listName']; ?></td>
                        </tr>
                        <tr>
                            <th>Email</th><td><input name="email" type="text" size="45" maxlength="100" value="<?php echo is_null($_SESSION['email']) ? '' : $_SESSION['email']; ?>" /></td>
                        </tr>
                        <tr>
                            <td>&nbsp;</td><td><input type="submit" value="Unsubscribe"/></td>
                        </tr>
                    </table>
                </form>
            </div>
        </div>
    </div>
<?php
}

function success() {
	?>
	<h2 class="componentheading mailsterUnsubscriberHeader">Unsubscription</h2>
	<div class="contentpane">
		<div id="mailsterContainer">
			<div id="mailsterUnsubscriber">
				<div id="mailsterUnsubscriberDescription">Unsubscription successful</div>
			</div>
		</div>
	</div>
<?php
}

function fail($error_message) {
	?>
	<h2 class="componentheading mailsterUnsubscriberHeader">Unsubscription</h2>
	<div class="contentpane">
		<div id="mailsterContainer">
			<div id="mailsterUnsubscriber">
				<div id="mailsterUnsubscriberDescription"><?php echo $error_message; ?></div>
			</div>
		</div>
	</div>
	<?php
}