<?php

if( isset($_REQUEST['success']) ) {
	success();
} else {
    $message = htmlspecialchars($_GET['mes'], ENT_QUOTES, 'UTF-8');
	failed($message);
}

function success() {
    ?>
    <h2 class="componentheading mailsterUnsubscriberHeader">Unsubscription</h2>
    <div class="contentpane">
        <div id="mailsterContainer">
            <div id="mailsterUnsubscriber">
                <div id="mailsterUnsubscriberDescription"><?php echo $_GET['mes']; ?></div>
            </div>
        </div>
    </div>
	<?php
}
function failed($error_message) {
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
