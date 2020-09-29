<?php
if (!defined('FLUX_ROOT')) exit;

$title = Flux::message('AccountConfirmTitle');

$user  = $params->get('user');
$code  = $params->get('code');
$login = $params->get('login');

if (!$login || !$user || !$code || strlen($code) !== 32) {
	$this->deny();
}

$loginAthenaGroup = Flux::getServerGroupByName($login);
if (!$loginAthenaGroup) {
	$this->deny();
}

if (Flux::config('MasterAccount')) {
	$createTable = Flux::config('FluxTables.MasterUserTable');

	$sql = "SELECT id as account_id FROM {$loginAthenaGroup->loginDatabase}.$createTable WHERE ";
	$sql .= "email = ? AND confirm_code = ? AND confirmed_date = NULL AND confirm_expire > NOW() LIMIT 1";
	$sth = $loginAthenaGroup->connection->getStatement($sql);

	if (!$sth->execute(array($user, $code)) || !($account = $sth->fetch())) {
		$this->deny();
	}

	$sql = "UPDATE {$loginAthenaGroup->loginDatabase}.$createTable SET ";
	$sql .= "confirmed_date = NOW(), confirm_expire = NULL WHERE id = ?";

	$loginAthenaGroup->loginServer->unban(null, Flux::message('AccountConfirmUnban'), $account->account_id);
} else {
	$createTable = Flux::config('FluxTables.AccountCreateTable');

	$sql = "SELECT account_id FROM {$loginAthenaGroup->loginDatabase}.$createTable WHERE ";
	$sql .= "userid = ? AND confirm_code = ? AND confirmed = 0 AND confirm_expire > NOW() LIMIT 1";
	$sth = $loginAthenaGroup->connection->getStatement($sql);

	if (!$sth->execute(array($user, $code)) || !($account = $sth->fetch())) {
		$this->deny();
	}

	$sql = "UPDATE {$loginAthenaGroup->loginDatabase}.$createTable SET ";
	$sql .= "confirmed = 1, confirm_expire = NULL WHERE account_id = ?";

	$loginAthenaGroup->loginServer->unban(null, Flux::message('AccountConfirmUnban'), $account->account_id);
}

$sth = $loginAthenaGroup->connection->getStatement($sql);

$sth->execute(array($account->account_id));


$session->setMessageData(Flux::message('AccountConfirmMessage'));
$this->redirect();
?>
