<?php
if (!defined('FLUX_ROOT')) exit;

$this->loginRequired();
if (!Flux::config('MasterAccount')) {
    $this->deny();
}
$title = Flux::message('AccountViewTitle');

require_once 'Flux/TemporaryTable.php';
$account       = $session->account;

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