<?php
if (!defined('FLUX_ROOT')) exit;

$this->loginRequired();

$title = Flux::message('GenderChangeTitle');

$cost    = +(int)Flux::config('ChargeGenderChange');
$badJobs = Flux::config('GenderLinkedJobClasses')->toArray();
$accountID = $params->get('id');
$account = $session->account;

if (!$accountID && Flux::config('MasterAccount')) {
	$this->redirect();
}
if ($accountID && Flux::config('MasterAccount')) {
	if (!($account = $session->loginServer->getGameAccount($account->id, $accountID))) {
		$this->redirect();
	}
}

if ($cost && $account->balance < $cost && !$auth->allowedToAvoidSexChangeCost) {
	$hasNecessaryFunds = false;
}
else {
	$hasNecessaryFunds = true;
}

if (count($_POST)) {
	if (!$hasNecessaryFunds || !$params->get('changegender')) {
		$this->deny();
	}

	$classes = array();
	foreach ($session->loginAthenaGroup->athenaServers as $athenaServer) {
		$sql = "SELECT COUNT(1) AS num FROM {$athenaServer->charMapDatabase}.`char` WHERE account_id = ? AND `class` IN (".implode(',', array_fill(0, count($badJobs), '?')).")";
		$sth = $athenaServer->connection->getStatement($sql);
		$sth->execute(array_merge(array($account->account_id), array_keys($badJobs)));
		
		if ($sth->fetch()->num) {
			$errorMessage = sprintf(Flux::message('GenderChangeBadChars'), implode(', ', array_values($badJobs)));
			break;
		}
	}
	
	if (empty($errorMessage)) {
		$sex = $account->sex == 'M' ? 'F' : 'M';
		$sql = "UPDATE {$server->loginDatabase}.login SET sex = ? WHERE account_id = ?";
		$sth = $server->connection->getStatement($sql);

		$sth->execute(array($sex, $account->account_id));

		$changeTimes = (int)$session->loginServer->getPref($account->account_id, 'NumberOfGenderChanges');
		$session->loginServer->setPref($account->account_id, 'NumberOfGenderChanges', $changeTimes + 1);

		if ($cost && !$auth->allowedToAvoidSexChangeCost) {
			$session->loginServer->depositCredits($account->account_id, -$cost);
			$session->setMessageData(sprintf(Flux::message('GenderChanged'), $cost));
		}
		else {
			$session->setMessageData(Flux::message('GenderChangedForFree'));
		}

		$this->redirect($this->url('account', 'view', $params->get('id') ? ['id' => $params->get('id') ]: ''));
	}
}
?>
