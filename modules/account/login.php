<?php
if (!defined('FLUX_ROOT')) exit;

if (Flux::config('UseLoginCaptcha') && Flux::config('EnableReCaptcha')) {
	$recaptcha = Flux::config('ReCaptchaPublicKey');
	$theme = Flux::config('ReCaptchaTheme');
}

$title = Flux::message('LoginTitle');
$loginLogTable = Flux::config('FluxTables.LoginLogTable');

if (count($_POST)) {
	if (Flux::config('MasterAccount')) {
		require_once 'includes/masterlogin.php';
	} else {
		require_once 'includes/defaultlogin.php';
	}
}

$serverNames = $this->getServerNames();
?>
