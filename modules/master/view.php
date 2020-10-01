<?php
if (!defined('FLUX_ROOT')) exit;

$this->loginRequired();
if (!Flux::config('MasterAccount')) {
    $this->redirect();
}
$title = Flux::message('AccountViewTitle');

require_once 'Flux/TemporaryTable.php';
$account   = $session->account;
$userId = $params->get('id');
$isMine = false;
$headerTitle = Flux::message('MasterAccountViewHeading');

if ($userId && $session->account->id !== $userId) {
    $isMine = false;
}

if (!$userId || $session->account->id === $userId) {
    $isMine = true;
}

if (!$isMine) {
    // Allowed to view other peoples' account information?
    if (!$auth->allowedToViewAccount) {
        $this->deny();
    }
    $usersTable = Flux::config('FluxTables.MasterUserTable');

    $sql = "SELECT * FROM {$server->loginDatabase}.{$usersTable} WHERE id = ? LIMIT 1";
    $sth = $server->connection->getStatement($sql);
    $sth->execute(array($userId));
    $account = $sth->fetch();
    $headerTitle = $title = sprintf(Flux::message('MasterAccountViewHeading2'), $account->email);
}

$userAccounts = array();
$userAccountTable = Flux::config('FluxTables.MasterUserAccountTable');
foreach ($session->getAthenaServerNames() as $serverName) {
    $athena = $session->getAthenaServer($serverName);

    $sql  = "SELECT *, login.account_id, login.userid, login.logincount, login.lastlogin, login.last_ip, login.sex";
    $sql .= " FROM {$athena->charMapDatabase}.{$userAccountTable} AS ua";
    $sql .= " JOIN {$athena->charMapDatabase}.login ON login.account_id = ua.account_id ";
    $sql .= " WHERE ua.user_id = ? ORDER BY ua.id ASC";
    $sth  = $server->connection->getStatement($sql);
    $sth->execute(array($account->id));

    $userAccount = $sth->fetchAll();
    $userAccounts[$athena->serverName] = $userAccount;
}