<?php
if (!defined('FLUX_ROOT')) exit;

if (!Flux::config('MasterAccount')) {
    $this->deny();
}

$serverGroupName = $params->get('server');
$email = $params->get('email');
$password = $params->get('password');
$code     = $params->get('security_code');
$usersTable = Flux::config('FluxTables.MasterUserTable');

try {
    $session->login($serverGroupName, $email, $password, $code);
    $returnURL = $params->get('return_url');

    $password = Flux::hashPassword($password, Flux::config('MasterAccountPasswordHash'));

    $sql  = "INSERT INTO {$session->loginAthenaGroup->loginDatabase}.$loginLogTable ";
    $sql .= "(user_id, username, password, ip, error_code, login_date) ";
    $sql .= "VALUES (?, ?, ?, ?, ?, NOW())";
    $sth  = $session->loginAthenaGroup->connection->getStatement($sql);
    $res = $sth->execute(array($session->account->id, $email, $password, $_SERVER['REMOTE_ADDR'], null));

    if ($returnURL) {
        $this->redirect($returnURL);
    }
    else {
        $this->redirect();
    }
}
catch (Flux_LoginError $e) {
    if ($email && $password && $e->getCode() != Flux_LoginError::INVALID_SERVER) {
        $loginAthenaGroup = Flux::getServerGroupByName($serverGroupName);
        $userColumns = Flux::config('FluxTables.MasterUserTableColumns');

        $sql = "SELECT {$userColumns->get('id')}, {$userColumns->get('email')} FROM {$loginAthenaGroup->loginDatabase}.{$usersTable} WHERE ";
        $sql .= "email = ? LIMIT 1";
        $sth = $loginAthenaGroup->connection->getStatement($sql);
        $sth->execute(array($email));
        $row = $sth->fetch();

        if ($row) {
            $password = Flux::hashPassword($password, Flux::config('MasterAccountPasswordHash'));

            $sql  = "INSERT INTO {$loginAthenaGroup->loginDatabase}.$loginLogTable ";
            $sql .= "(user_id, username, password, ip, error_code, login_date) ";
            $sql .= "VALUES (?, ?, ?, ?, ?, NOW())";
            $sth  = $loginAthenaGroup->connection->getStatement($sql);
            $sth->execute(array($row->id, $email, $password, $_SERVER['REMOTE_ADDR'], $e->getCode()));
        }
    }

    switch ($e->getCode()) {
        case Flux_LoginError::UNEXPECTED:
            $errorMessage = Flux::message('UnexpectedLoginError');
            break;
        case Flux_LoginError::INVALID_SERVER:
            $errorMessage = Flux::message('InvalidLoginServer');
            break;
        case Flux_LoginError::INVALID_LOGIN:
            $errorMessage = Flux::message('InvalidLoginCredentials');
            break;
        case Flux_LoginError::BANNED:
            $errorMessage = Flux::message('TemporarilyBanned');
            break;
        case Flux_LoginError::PERMABANNED:
            $errorMessage = Flux::message('PermanentlyBanned');
            break;
        case Flux_LoginError::IPBANNED:
            $errorMessage = Flux::message('IpBanned');
            break;
        case Flux_LoginError::INVALID_SECURITY_CODE:
            $errorMessage = Flux::message('InvalidSecurityCode');
            break;
        case Flux_LoginError::PENDING_CONFIRMATION:
            $errorMessage = Flux::message('PendingConfirmation');
            break;
        default:
            $errorMessage = Flux::message('CriticalLoginError');
            break;
    }
}