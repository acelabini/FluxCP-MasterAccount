<?php
if (!defined('FLUX_ROOT')) exit;

if (Flux::config('UseCaptcha') && Flux::config('EnableReCaptcha')) {
	$recaptcha = Flux::config('ReCaptchaPublicKey');
	$theme = Flux::config('ReCaptchaTheme');
}

$title = Flux::message('AccountCreateTitle');

$serverNames = $this->getServerNames();

if (count($_POST)) {
	require_once 'Flux/RegisterError.php';

	if (Flux::config('MasterAccount')) {
		require_once 'includes/masterregister.php';
	} else {
		require_once 'includes/defaultregister.php';
	}
}
?>
