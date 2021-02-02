<?php if (!defined('FLUX_ROOT')) exit; ?>

<h2><?php echo htmlspecialchars(Flux::message('GameAccountsViewHeading')) ?></h2>
<?php if (!empty($errorMessage)): ?>
    <p class="red"><?php echo htmlspecialchars($errorMessage) ?></p>
<?php endif ?>
<?php if ($account): ?>
    <?php foreach ($userAccounts as $serverName => $userAccount): ?>
        <?php if ($userAccount): ?>
            <table class="vertical-table">
                <tr>
                    <th><?php echo htmlspecialchars(Flux::message('UsernameLabel')) ?></th>
                    <th><?php echo htmlspecialchars(Flux::message('AccountGroupIDLabel')) ?></th>
                    <th><?php echo htmlspecialchars(Flux::message('LoginCountLabel')) ?></th>
                    <th><?php echo htmlspecialchars(Flux::message('LastLoginDateLabel')) ?></th>
                    <th><?php echo htmlspecialchars(Flux::message('LastUsedIpLabel')) ?></th>
                    <th><?php echo htmlspecialchars(Flux::message('AccountStateLabel')) ?></th>
                </tr>
                <?php foreach ($userAccount as $acct):?>
                    <tr>
                        <td align="right">
                            <a href="<?php echo $this->url('account', 'view', array('id' => $acct->account_id)); ?>">
                                <?php echo htmlspecialchars($acct->userid) ?>
                            </a>
                        </td>
                        <td><?php echo (int)$acct->group_id ?></td>
                        <td><?php echo (int)$acct->logincount ?></td>
                        <td><?php echo $acct->lastlogin ? date(Flux::config('DateTimeFormat'), $acct->lastlogin) : null ?></td>
                        <td><?php echo $acct->last_ip ?></td>
                        <td>
                            <?php if (($state = $this->accountStateText($account->state)) && !$account->unban_time): ?>
                                <?php echo $state ?>
                            <?php elseif ($account->unban_time): ?>
                                <span class="account-state state-banned">
                                    <?php printf(htmlspecialchars(Flux::message('AccountStateTempBanned')), date(Flux::config('DateTimeFormat'), $account->unban_time)) ?>
                                </span>
                            <?php else: ?>
                                <span class="account-state state-unknown"><?php echo htmlspecialchars(Flux::message('UnknownLabel')) ?></span>
                            <?php endif ?></td>
                    </tr>
                <?php endforeach ?>
            </table>
        <?php else: ?>
            <p><?php echo htmlspecialchars(sprintf(Flux::message('AccountViewNoChars'), $serverName)) ?></p>
        <?php endif ?>
    <?php endforeach ?>
<?php else: ?>
    <p>
        <?php echo htmlspecialchars(Flux::message('AccountViewNotFound')) ?>
        <a href="javascript:history.go(-1)"><?php echo htmlspecialchars(Flux::message('GoBackLabel')) ?></a>
    </p>
<?php endif ?>
