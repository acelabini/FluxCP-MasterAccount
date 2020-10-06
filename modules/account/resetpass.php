<?php
if (!defined('FLUX_ROOT')) exit;

$title = Flux::message('ResetPassTitle');

$serverNames    = $this->getServerNames();
$resetPassTable = Flux::config('FluxTables.ResetPasswordTable');

if (!$params->get('id') && Flux::config('MasterAccount') && $session->isLoggedIn()) {
	$this->deny();
}

$account = null;
if (Flux::config('MasterAccount') && $session->isLoggedIn()) {
	$accountID = $params->get('id');
	$account = $session->account;
	if (!$accountID && Flux::config('MasterAccount')) {
		$this->deny();
	}
	if ($accountID && Flux::config('MasterAccount')) {
		if (!($account = $session->loginServer->getGameAccount($account->id, $accountID))) {
			$this->deny();
		}
	}
}

if (count($_POST)) {
	if (Flux::config('MasterAccount') && !$session->isLoggedIn()) {
		require_once 'includes/masterresetpass.php'; //reset master account
	} else {
		require_once 'includes/defaultresetpass.php';//reset game account
	}
}
?>
