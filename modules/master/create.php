<?php
if (!defined('FLUX_ROOT')) exit;

if (Flux::config('UseCaptcha') && Flux::config('EnableReCaptcha')) {
    $recaptcha = Flux::config('ReCaptchaPublicKey');
    $theme = Flux::config('ReCaptchaTheme');
}

if (!Flux::config('MasterAccount')) {
    $this->deny();
}

$title = Flux::message('AccountCreateTitle');

$serverNames = $this->getServerNames();

if (!$session->account) {
    $this->deny();
}

$maxGameAccount = Flux::config('MasterAccountMaxAccounts');
$maxGameAccountReached = false;
if ($maxGameAccount > 0) {
    $accounts = $server->loginServer->getGameAccounts($session->account->id);
    if (count($accounts) >= $maxGameAccount) {
        $maxGameAccountReached = true;
        $session->setMessageData(sprintf(Flux::message('ReachedMaxGameAccounts'), Flux::config('MasterAccountMaxAccounts')));
    }
}

if (count($_POST)) {
    require_once 'Flux/RegisterError.php';

    try {
        $serverGroupName = $params->get('server');
        $username  = $params->get('username');
        $password  = $params->get('password');
        $confirm   = $params->get('confirm_password');
        $gender    = $params->get('gender');
        $code      = $params->get('security_code');

        if (!($server = Flux::getServerGroupByName($serverGroupName))) {
            throw new Flux_RegisterError('Invalid server', Flux_RegisterError::INVALID_SERVER);
        }

        // Woohoo! Register ;)
        $result = $server->loginServer->createGameAccount($username, $password, $confirm, $gender, $code);

        if ($result) {
            $session->setMessageData(Flux::message('MasterGameAccountCreated'));
        }
        else {
            exit('Uh oh, what happened?');
        }
    }
    catch (Flux_RegisterError $e) {
        switch ($e->getCode()) {
            case Flux_RegisterError::USERNAME_ALREADY_TAKEN:
                $errorMessage = Flux::message('UsernameAlreadyTaken');
                break;
            case Flux_RegisterError::USERNAME_TOO_SHORT:
                $errorMessage = Flux::message('UsernameTooShort');
                break;
            case Flux_RegisterError::USERNAME_TOO_LONG:
                $errorMessage = Flux::message('UsernameTooLong');
                break;
            case Flux_RegisterError::PASSWORD_HAS_USERNAME:
                $errorMessage = Flux::message ('PasswordHasUsername');
                break;
            case Flux_RegisterError::PASSWORD_TOO_SHORT:
                $errorMessage = sprintf(Flux::message('PasswordTooShort'), Flux::config('MinPasswordLength'), Flux::config('MaxPasswordLength'));
                break;
            case Flux_RegisterError::PASSWORD_TOO_LONG:
                $errorMessage = sprintf(Flux::message('PasswordTooLong'), Flux::config('MinPasswordLength'), Flux::config('MaxPasswordLength'));
                break;
            case Flux_RegisterError::PASSWORD_MISMATCH:
                $errorMessage = Flux::message('PasswordsDoNotMatch');
                break;
            case Flux_RegisterError::PASSWORD_NEED_UPPER:
                $errorMessage = sprintf(Flux::message ('PasswordNeedUpper'), Flux::config('PasswordMinUpper'));
                break;
            case Flux_RegisterError::PASSWORD_NEED_LOWER:
                $errorMessage = sprintf(Flux::message ('PasswordNeedLower'), Flux::config('PasswordMinLower'));
                break;
            case Flux_RegisterError::PASSWORD_NEED_NUMBER:
                $errorMessage = sprintf(Flux::message ('PasswordNeedNumber'), Flux::config('PasswordMinNumber'));
                break;
            case Flux_RegisterError::PASSWORD_NEED_SYMBOL:
                $errorMessage = sprintf(Flux::message ('PasswordNeedSymbol'), Flux::config('PasswordMinSymbol'));
                break;
            case Flux_RegisterError::EMAIL_ADDRESS_IN_USE:
                $errorMessage = Flux::message('EmailAddressInUse');
                break;
            case Flux_RegisterError::INVALID_EMAIL_ADDRESS:
                $errorMessage = Flux::message('InvalidEmailAddress');
                break;
            case Flux_RegisterError::INVALID_EMAIL_CONF:
                $errorMessage = Flux::message('InvalidEmailconf');
                break;
            case Flux_RegisterError::INVALID_GENDER:
                $errorMessage = Flux::message('InvalidGender');
                break;
            case Flux_RegisterError::INVALID_SERVER:
                $errorMessage = Flux::message('InvalidServer');
                break;
            case Flux_RegisterError::INVALID_SECURITY_CODE:
                $errorMessage = Flux::message('InvalidSecurityCode');
                break;
            case Flux_RegisterError::INVALID_USERNAME:
                $errorMessage = sprintf(Flux::message('AccountInvalidChars'), Flux::config('UsernameAllowedChars'));
                break;
            case Flux_RegisterError::INVALID_PASSWORD:
                $errorMessage = Flux::message ('InvalidPassword');
                break;
            case Flux_RegisterError::INVALID_BIRTHDATE:
                $errorMessage = Flux::message('InvalidBirthdate');
                break;
            default:
                $errorMessage = Flux::message('CriticalRegisterError');
                break;
        }
    }
}
?>
