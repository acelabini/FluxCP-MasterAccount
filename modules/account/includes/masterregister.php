<?php
if (!defined('FLUX_ROOT')) exit;

try {
    $serverGroupName = $params->get('server');
    $name      = $params->get('name');
    $email     = trim($params->get('email_address'));
    $password  = $params->get('password');
    $confirm   = $params->get('confirm_password');
    $birthdate = $params->get('birthdate_date');
    $code      = $params->get('security_code');

    if (!($server = Flux::getServerGroupByName($serverGroupName))) {
        throw new Flux_RegisterError('Invalid server', Flux_RegisterError::INVALID_SERVER);
    }

    // Woohoo! Register ;)
    $result = $server->loginServer->register($name, $password, $confirm, $birthdate, $code, $email, null, null);

    if ($result) {
        if (Flux::config('RequireEmailConfirm')) {
            require_once 'Flux/Mailer.php';

            $user = $email;
            $code = md5(rand());
            $link = $this->url('account', 'confirm', array('_host' => true, 'code' => $code, 'user' => $email, 'login' => $session->loginAthenaGroup->serverName));
            $mail = new Flux_Mailer();
            $sent = $mail->send(
                $email,
                'Project Freya: Account Confirmation',
                'confirm',
                array('AccountUsername' => $email, 'ConfirmationLink' => htmlspecialchars($link)));

            $usersTable = Flux::config('FluxTables.MasterUserTable');
            $bind = array($code);

            // Insert confirmation code.
            $sql  = "UPDATE {$server->loginDatabase}.{$usersTable} SET ";
            $sql .= "confirm_code = ?, confirmed = 0 ";
            if ($expire=Flux::config('EmailConfirmExpire')) {
                $sql .= ", confirm_expire = ? ";
                $bind[] = date('Y-m-d H:i:s', time() + (60 * 60 * $expire));
            }

            $sql .= " WHERE id = ?";
            $bind[] = $result;

            $sth  = $server->connection->getStatement($sql);
            $sth->execute($bind);

            $session->loginServer->permanentlyBan(null, sprintf(Flux::message('AccountConfirmBan'), $code), $result);

            if ($sent) {
                $message  = Flux::message('AccountCreateEmailSent');
                $discordMessage = 'Confirmation email has been sent.';
            }
            else {
                $message  = Flux::message('AccountCreateFailed');
                $discordMessage = 'Failed to send the Confirmation email.';
            }

            $session->setMessageData($message);
        }
        else {
            $session->login($server->serverName, $email, $password, false);
            $session->setMessageData(Flux::message('AccountCreated'));
            $discordMessage = 'Account Created.';
        }
        if(Flux::config('DiscordUseWebhook')) {
            if(Flux::config('DiscordSendOnRegister')) {
                sendtodiscord(Flux::config('DiscordWebhookURL'), 'New User registration: "'. $email . '" , ' . $discordMessage);
            }
        }
        $this->redirect();
    }
    else {
        exit('Uh oh, what happened?');
    }
}
catch (Flux_RegisterError $e) {
    switch ($e->getCode()) {
        case Flux_RegisterError::USERNAME_ALREADY_TAKEN:
            $errorMessage = Flux::message('EmailAlreadyRegistered');
            break;
        case Flux_RegisterError::PASSWORD_HAS_USERNAME:
            $errorMessage = Flux::message ('NewPasswordHasName');
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
        case Flux_RegisterError::INVALID_SERVER:
            $errorMessage = Flux::message('InvalidServer');
            break;
        case Flux_RegisterError::INVALID_SECURITY_CODE:
            $errorMessage = Flux::message('InvalidSecurityCode');
            break;
        case Flux_RegisterError::INVALID_PASSWORD:
            $errorMessage = Flux::message ('InvalidPassword');
            break;
        case Flux_RegisterError::INVALID_BIRTHDATE:
            $errorMessage = Flux::message('InvalidBirthdate');
            break;
        case Flux_RegisterError::INVALID_NAME:
            $errorMessage = Flux::message('AccountNameInvalidChars');
            break;
        default:
            $errorMessage = Flux::message('CriticalRegisterError');
            break;
    }
}
?>
