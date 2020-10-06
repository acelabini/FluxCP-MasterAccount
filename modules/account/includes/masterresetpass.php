<?php
if (!defined('FLUX_ROOT')) exit;

if (!Flux::config('MasterAccount')) {
    $this->deny();
}

$email     = $params->get('email');
$groupName = $params->get('login');

if (!$email) {
    $errorMessage = Flux::message('ResetPassEnterEmail');
}
elseif (!preg_match('/^(.+?)@(.+?)$/', $email)) {
    $errorMessage = Flux::message('InvalidEmailAddress');
}
else {
    if (!$groupName || !($loginAthenaGroup=Flux::getServerGroupByName($groupName))) {
        $loginAthenaGroup = $session->loginAthenaGroup;
    }
    $usersTable = Flux::config('FluxTables.MasterUserTable');
    $userColumns = Flux::config('FluxTables.MasterUserTableColumns');
    $idColumn = $userColumns->get('id');
    $passwordColumn = $userColumns->get('password');
    $groupIdColumn = $userColumns->get('group_id');
    $emailColumn = $userColumns->get('email');

    $sql  = "SELECT {$idColumn}, {$passwordColumn}, {$groupIdColumn}, {$emailColumn} ";
    $sql .= "FROM {$loginAthenaGroup->loginDatabase}.{$usersTable} WHERE {$emailColumn} = ? LIMIT 1";
    $sth  = $loginAthenaGroup->connection->getStatement($sql);
    $sth->execute(array($email));

    $row = $sth->fetch();
    if ($row) {
        $groups = AccountLevel::getArray();
        if (AccountLevel::getGroupLevel($row->$groupIdColumn) >= Flux::config('NoResetPassGroupLevel')) {
            $errorMessage = Flux::message('ResetPassDisallowed');
        }
        else {
            $masterId = $this->getMasterId($row->$idColumn);
            $code = md5(rand() + $masterId);
            $sql  = "INSERT INTO {$loginAthenaGroup->loginDatabase}.$resetPassTable ";
            $sql .= "(code, user_id, old_password, request_date, request_ip, reset_done) ";
            $sql .= "VALUES (?, ?, ?, NOW(), ?, 0)";
            $sth  = $loginAthenaGroup->connection->getStatement($sql);
            $res  = $sth->execute(array($code, $row->$idColumn, $row->$passwordColumn, $_SERVER['REMOTE_ADDR']));

            if ($res) {
                require_once 'Flux/Mailer.php';
                $name = $loginAthenaGroup->serverName;
                $link = $this->url('account', 'resetpw', array('_host' => true, 'code' => $code, 'account' => $masterId, 'email' => $row->$emailColumn, 'login' => $name));
                $mail = new Flux_Mailer();
                $sent = $mail->send($email, 'Reset Password', 'resetpass', array('AccountUsername' => $email, 'ResetLink' => htmlspecialchars($link)));
            }
        }
    }

    if (empty($errorMessage)) {
        if (empty($sent)) {
            $errorMessage = Flux::message('ResetPassFailed');
        }
        else {
            $session->setMessageData(Flux::message('ResetPassEmailSent'));
            $this->redirect();
        }
    }
}