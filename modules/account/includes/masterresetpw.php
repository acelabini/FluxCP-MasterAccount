<?php
if (!defined('FLUX_ROOT')) exit;

if (!Flux::config('MasterAccount')) {
    $this->deny();
}
$email = $params->get('email');
$usersTable = Flux::config('FluxTables.MasterUserTable');
$userColumns = Flux::config('FluxTables.MasterUserTableColumns');
$emailColumn = $userColumns->get('email');
$idColumn = $userColumns->get('id');

$sql = "SELECT {$emailColumn}, {$idColumn} FROM {$loginAthenaGroup->loginDatabase}.{$usersTable} WHERE ";
$sql .="{$emailColumn} = ? AND {$idColumn} = ? LIMIT 1";
$sth = $loginAthenaGroup->connection->getStatement($sql);
$sth->execute(array($email, $account));
$acc = $sth->fetch();

if (!$acc) {
    $this->deny();
}

$sql  = "SELECT id FROM {$loginAthenaGroup->loginDatabase}.$resetPassTable WHERE ";
$sql .= "user_id = ? AND code = ? AND reset_done = 0 LIMIT 1";
$sth  = $loginAthenaGroup->connection->getStatement($sql);

if (!$sth->execute(array($account, $code)) || !($reset=$sth->fetch())) {
    $this->deny();
}

$sql  = "UPDATE {$loginAthenaGroup->loginDatabase}.$resetPassTable SET ";
$sql .= "reset_done = 1, reset_date = NOW(), reset_ip = ?, new_password = ? WHERE id = ?";
$sth  = $loginAthenaGroup->connection->getStatement($sql);

$newPassword = '';
$characters  = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
$characters  = str_split($characters, 1);
$passLength  = intval(($len=Flux::config('RandomPasswordLength')) < 8 ? 8 : $len);

for ($i = 0; $i < $passLength; ++$i) {
    $newPassword .= $characters[array_rand($characters)];
}

$unhashedNewPassword = $newPassword;
$newPassword = Flux::hashPassword($newPassword, Flux::config('MasterAccountPasswordHash'));

if (!$sth->execute(array($_SERVER['REMOTE_ADDR'], $newPassword, $reset->id))) {
    $session->setMessageData(Flux::message('ResetPwFailed'));
    $this->redirect();
}

$sql = "UPDATE {$loginAthenaGroup->loginDatabase}.{$usersTable} SET {$userColumns->get('password')} = ? WHERE {$idColumn} = ?";
$sth = $loginAthenaGroup->connection->getStatement($sql);

if (!$sth->execute(array($newPassword, $account))) {
    $session->setMessageData(Flux::message('ResetPwFailed'));
    $this->redirect();
}

require_once 'Flux/Mailer.php';
$mail = new Flux_Mailer();
$sent = $mail->send($acc->email, 'Password Has Been Reset', 'newpass', array('AccountUsername' => $acc->$emailColumn, 'NewPassword' => $unhashedNewPassword));

if ($sent) {
    $message = Flux::message('ResetPwDone');
}
else {
    $message = Flux::message('ResetPwDone2');
}

$session->setMessageData($message);
$this->redirect();