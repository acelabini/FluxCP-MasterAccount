<?php
if (!defined('FLUX_ROOT')) exit;

$userid    = $params->get('userid');
$email     = $params->get('email');
$groupName = $params->get('login');

if (Flux::config('MasterAccount') && $session->isLoggedIn()) {
    $userid = $accountID;
    $email = $session->account->email;
}

if (!$userid) {
    $errorMessage = Flux::message('ResetPassEnterAccount');
}
elseif (!$email) {
    $errorMessage = Flux::message('ResetPassEnterEmail');
}
elseif (preg_match('/[^' . Flux::config('UsernameAllowedChars') . ']/', $userid)) {
    $errorMessage = sprintf(Flux::message('AccountInvalidChars'), Flux::config('UsernameAllowedChars'));
}
elseif (!preg_match('/^(.+?)@(.+?)$/', $email)) {
    $errorMessage = Flux::message('InvalidEmailAddress');
}
else {
    if (!$groupName || !($loginAthenaGroup=Flux::getServerGroupByName($groupName))) {
        $loginAthenaGroup = $session->loginAthenaGroup;
    }

    $sql  = "SELECT account_id, user_pass, group_id FROM {$loginAthenaGroup->loginDatabase}.login WHERE ";
    $sql .= "account_id = ? AND email = ? AND state = 0 AND sex IN ('M', 'F') LIMIT 1";
    $sth  = $loginAthenaGroup->connection->getStatement($sql);
    $sth->execute(array($userid, $email));
    $row = $sth->fetch();

    if ($row) {
        $groups = AccountLevel::getArray();
        if (AccountLevel::getGroupLevel($row->group_id) >= Flux::config('NoResetPassGroupLevel')) {
            $errorMessage = Flux::message('ResetPassDisallowed');
        }
        else {
            $code = md5(rand() + $row->account_id);
            $sql  = "INSERT INTO {$loginAthenaGroup->loginDatabase}.$resetPassTable ";
            $sql .= "(code, account_id, old_password, request_date, request_ip, reset_done) ";
            $sql .= "VALUES (?, ?, ?, NOW(), ?, 0)";
            $sth  = $loginAthenaGroup->connection->getStatement($sql);
            $res  = $sth->execute(array($code, $row->account_id, $row->user_pass, $_SERVER['REMOTE_ADDR']));
            if ($res) {
                require_once 'Flux/Mailer.php';
                $name = $loginAthenaGroup->serverName;
                $link = $this->url('account', 'resetpw', array('_host' => true, 'code' => $code, 'account' => $row->account_id, 'login' => $name));
                $mail = new Flux_Mailer();
                $sent = $mail->send($email, 'Reset Password', 'resetpass', array('AccountUsername' => $userid, 'ResetLink' => htmlspecialchars($link)));
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