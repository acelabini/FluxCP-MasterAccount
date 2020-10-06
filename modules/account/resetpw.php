<?php
if (!defined('FLUX_ROOT')) exit;

$title = Flux::message('ResetPassButton');

$account = $params->get('account');
$code    = $params->get('code');
$login   = $params->get('login');

$resetPassTable = Flux::config('FluxTables.ResetPasswordTable');

if (!$login || !$account || !$code || strlen($code) !== 32) {
	$this->deny();
}
$id = $params->get('id') ?: $params->get('account');
if (!$id && Flux::config('MasterAccount') && $session->isLoggedIn()) {
	$this->deny();
}

$loginAthenaGroup = Flux::getServerGroupByName($login);
if (!$loginAthenaGroup) {
	$this->deny();
}

if (Flux::config('MasterAccount') && !$session->isLoggedIn()) {
	require_once 'includes/masterresetpw.php'; //reset master account
} else {
	require_once 'includes/defaultresetpw.php';//reset game account
}
?>
