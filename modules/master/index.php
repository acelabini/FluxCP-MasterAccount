<?php
if (!defined('FLUX_ROOT')) exit;

$this->loginRequired();

$title = 'List Master Accounts';
if (!Flux::config('MasterAccount')) {
    $this->deny();
}

$usersTable = Flux::config('FluxTables.MasterUserTable');
$userAccountTable = Flux::config('FluxTables.MasterUserAccountTable');
$userColumns = Flux::config('FluxTables.MasterUserTableColumns');
$bind = [];

$sql .= "LEFT OUTER JOIN {$server->loginDatabase}.{$userAccountTable} AS useraccounts ON {$usersTable}.{$userColumns->get('id')} = useraccounts.user_id ";
$sql .= "WHERE {$userColumns->get('group_id')} >= 0 ";
$userId = $params->get('master_id');
if ($userId) {
    $sql .= "AND {$usersTable}.{$userColumns->get('id')} = ?";
    $bind[]      = $userId;
}
else {
    $opMapping        = array('eq' => '=', 'gt' => '>', 'lt' => '<');
    $opValues         = array_keys($opMapping);
    $name             = $params->get('name');
    $email            = $params->get('email');
    $lastIP           = $params->get('last_ip');
    $gender           = $params->get('gender');
    $accountState     = $params->get('account_state');
    $accountGroupIdOp = $params->get('account_group_id_op');
    $accountGroupID   = $params->get('account_group_id');
    $birthdateA       = $params->get('birthdate_after_date');
    $birthdateB       = $params->get('birthdate_before_date');

    if ($email) {
        $sql .= "AND (login.email LIKE ? OR login.email = ?) ";
        $bind[]      = "%$email%";
        $bind[]      = $email;
    }

    if ($lastIP) {
        $sql .= "AND (login.last_ip LIKE ? OR login.last_ip = ?) ";
        $bind[]      = "%$lastIP%";
        $bind[]      = $lastIP;
    }

    if (in_array($accountGroupIdOp, $opValues) && trim($accountGroupID) != '') {
        $op          = $opMapping[$accountGroupIdOp];
        $sql .= "AND login.group_id $op ? ";
        $bind[]      = $accountGroupID;
    }

    if ($birthdateB && ($timestamp = strtotime($birthdateB))) {
        $sql .= 'AND login.birthdate <= ? ';
        $bind[]      = date('Y-m-d', $timestamp);
    }

    if ($birthdateA && ($timestamp = strtotime($birthdateA))) {
        $sql .= 'AND login.birthdate >= ? ';
        $bind[]      = date('Y-m-d', $timestamp);
    }
}
$totalAccounts = "SELECT COUNT({$usersTable}.{$userColumns->get('id')}) AS total FROM {$server->loginDatabase}.{$usersTable} $sql";
$sth = $server->connection->getStatement($totalAccounts);
$sth->execute($bind);

$paginator = $this->getPaginator($sth->fetch()->total);
$paginator->setSortableColumns(array(
    'login.account_id' => 'asc', 'login.userid', 'login.user_pass',
    'login.sex', 'group_id', 'state', 'balance',
    'login.email', 'logincount', 'lastlogin', 'last_ip',
    'reg_date'
));

$columns = implode(", {$usersTable}.",$userColumns->toArray());
$sql = "SELECT {$usersTable}.{$columns}, count(useraccounts.id) as totalAccounts FROM {$server->loginDatabase}.{$usersTable} $sql";
$sql .= "GROUP BY {$usersTable}.{$columns}";
$sth  = $server->connection->getStatement($sql);
$sth->execute($bind);
$accounts   = $sth->fetchAll();

$authorized = $auth->actionAllowed('master', 'view') && $auth->allowedToViewAccount;

if ($accounts && count($accounts) === 1 && $authorized && Flux::config('SingleMatchRedirect')) {
    $this->redirect($this->url('master', 'view', array('id' => $accounts[0]->account_id)));
}
?>
