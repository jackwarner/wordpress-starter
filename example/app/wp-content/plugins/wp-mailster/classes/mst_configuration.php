if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
die( 'These are not the droids you are looking for.' );
}