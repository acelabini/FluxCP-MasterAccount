<?php
require_once 'Flux/BaseServer.php';
require_once 'Flux/RegisterError.php';

/**
 * Represents an rAthena Login Server.
 */
class Flux_MasterLoginServer extends Flux_LoginServer {
    /**
     * Overridden to add custom properties.
     *
     * @access public
     */
    public function __construct(Flux_Config $config)
    {
        parent::__construct($config);
    }

    /**
     * Validate credentials of the master account.
     *
     * @param $email
     * @param $password
     * @return boolean
     */
    public function isAuth($email, $password)
    {
        if (!Flux::config('MasterAccount')) return false;

        if (trim($email) == '' || trim($password) == '') {
            return false;
        }
        $usersTable = Flux::config('FluxTables.MasterUserTable');
        $userColumns = Flux::config('FluxTables.MasterUserTableColumns');
        $sql  = "SELECT {$userColumns->get('id')},{$userColumns->get('password')} FROM {$this->loginDatabase}.{$usersTable}";
        $sql .= " WHERE {$userColumns->get('group_id')} >= 0 ";
        $sql .= "AND LOWER({$userColumns->get('email')}) = LOWER(?) LIMIT 1";
        $sth  = $this->connection->getStatement($sql);
        $sth->execute(array($email));
        $res = $sth->fetch();

        return password_verify($password, $res->password);
    }

    public function register($username, $password, $confirmPassword, $birthDate, $securityCode, $email, $email2, $gender)
    {
        $name = $username;
        if (!ctype_alpha(str_replace(" ", "", $name))) {
            throw new Flux_RegisterError('Invalid character(s) used in name', Flux_RegisterError::INVALID_NAME);
        }
        elseif (!Flux::config('AllowUserInPassword') &&
            stripos(str_replace(" ", "", $name),preg_replace('/[^a-z]/i','',$password)) !== false) {
            throw new Flux_RegisterError('Password contains name', Flux_RegisterError::PASSWORD_HAS_USERNAME);
        }
        elseif (!ctype_graph($password)) {
            throw new Flux_RegisterError('Invalid character(s) used in password', Flux_RegisterError::INVALID_PASSWORD);
        }
        elseif (strlen($password) < Flux::config('MinPasswordLength')) {
            throw new Flux_RegisterError('Password is too short', Flux_RegisterError::PASSWORD_TOO_SHORT);
        }
        elseif (strlen($password) > Flux::config('MaxPasswordLength')) {
            throw new Flux_RegisterError('Password is too long', Flux_RegisterError::PASSWORD_TOO_LONG);
        }
        elseif ($password !== $confirmPassword) {
            throw new Flux_RegisterError('Passwords do not match', Flux_RegisterError::PASSWORD_MISMATCH);
        }
        elseif (Flux::config('PasswordMinUpper') > 0 && preg_match_all('/[A-Z]/', $password, $matches) < Flux::config('PasswordMinUpper')) {
            throw new Flux_RegisterError('Passwords must contain at least ' . intval(Flux::config('PasswordMinUpper')) . ' uppercase letter(s)', Flux_RegisterError::PASSWORD_NEED_UPPER);
        }
        elseif (Flux::config('PasswordMinLower') > 0 && preg_match_all('/[a-z]/', $password, $matches) < Flux::config('PasswordMinLower')) {
            throw new Flux_RegisterError('Passwords must contain at least ' . intval(Flux::config('PasswordMinLower')) . ' lowercase letter(s)', Flux_RegisterError::PASSWORD_NEED_LOWER);
        }
        elseif (Flux::config('PasswordMinNumber') > 0 && preg_match_all('/[0-9]/', $password, $matches) < Flux::config('PasswordMinNumber')) {
            throw new Flux_RegisterError('Passwords must contain at least ' . intval(Flux::config('PasswordMinNumber')) . ' number(s)', Flux_RegisterError::PASSWORD_NEED_NUMBER);
        }
        elseif (Flux::config('PasswordMinSymbol') > 0 && preg_match_all('/[^A-Za-z0-9]/', $password, $matches) < Flux::config('PasswordMinSymbol')) {
            throw new Flux_RegisterError('Passwords must contain at least ' . intval(Flux::config('PasswordMinSymbol')) . ' symbol(s)', Flux_RegisterError::PASSWORD_NEED_SYMBOL);
        }
        elseif (!preg_match('/^(.+?)@(.+?)$/', $email)) {
            throw new Flux_RegisterError('Invalid e-mail address', Flux_RegisterError::INVALID_EMAIL_ADDRESS);
        }
        elseif (($birthdatestamp = strtotime($birthDate)) === false || date('Y-m-d', $birthdatestamp) != $birthDate) {
            throw new Flux_RegisterError('Invalid birthdate', Flux_RegisterError::INVALID_BIRTHDATE);
        }
        elseif (Flux::config('UseCaptcha')) {
            if (Flux::config('EnableReCaptcha')) {
                if(isset($_POST['g-recaptcha-response']) && $_POST['g-recaptcha-response'] != ""){
                    $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=".Flux::config('ReCaptchaPrivateKey')."&response=".$_POST['g-recaptcha-response']."&remoteip=".$_SERVER['REMOTE_ADDR']);
                }
                $responseKeys = json_decode($response,true);
                if(intval($responseKeys["success"]) !== 1) {
                    throw new Flux_RegisterError('Invalid security code', Flux_RegisterError::INVALID_SECURITY_CODE);
                }
            }
            elseif (strtolower($securityCode) !== strtolower(Flux::$sessionData->securityCode)) {
                throw new Flux_RegisterError('Invalid security code', Flux_RegisterError::INVALID_SECURITY_CODE);
            }
        }

        $usersTable = Flux::config('FluxTables.MasterUserTable');
        $userColumns = Flux::config('FluxTables.MasterUserTableColumns');
        $sql  = "SELECT {$userColumns->get('email')} FROM {$this->loginDatabase}.{$usersTable} WHERE LOWER({$userColumns->get('email')}) = LOWER(?) LIMIT 1";
        $sth  = $this->connection->getStatement($sql);
        $sth->execute(array($email));

        $res = $sth->fetch();
        if ($res) {
            throw new Flux_RegisterError('E-mail address is already in use', Flux_RegisterError::EMAIL_ADDRESS_IN_USE);
        }

        $password = Flux::hashPassword($password, Flux::config('MasterAccountPasswordHash'));
        $withDates = !empty($userColumns->get('created_at')) && !empty($userColumns->get('updated_at'));

        $sql = "INSERT INTO {$this->loginDatabase}.{$usersTable} ";
        $sql .= "({$userColumns->get('name')}, {$userColumns->get('email')}, {$userColumns->get('password')}, {$userColumns->get('group_id')},";
        $sql .= "{$userColumns->get('birth_date')}, {$userColumns->get('last_ip')}";
        if ($withDates) {
            $sql .= ", {$userColumns->get('created_at')}, {$userColumns->get('updated_at')}";
        }
        $sql .= ") ";
        $sql .= "VALUES (?, ?, ?, ?, ?, ?";
        if ($withDates) {
            $sql .= ", NOW(), NOW()";
        }
        $sql .=")";
        $sth = $this->connection->getStatement($sql);
        $res = $sth->execute(array(
            $name,
            $email,
            $password,
            (int)$this->config->getGroupID(),
            date('Y-m-d', $birthdatestamp),
            $_SERVER['REMOTE_ADDR']
        ));

        if ($res) {
            $idsth = $this->connection->getStatement("SELECT LAST_INSERT_ID() AS id");
            $idsth->execute();

            return $idsth->fetch()->id;
        }

        return false;
    }

    public function createGameAccount($username, $password, $confirmPassword, $gender, $securityCode)
    {
        if (!($account = Flux::$sessionData->account)) {
            return false;
        }
        $maxGameAccount = Flux::config('MasterAccountMaxAccounts');
        if ($maxGameAccount > 0) {
            if (count($this->getGameAccounts($account->id)) >= $maxGameAccount) {
                return false;
            }
        }

        $accountId = $this->registerGameAccount(
            $account,
            $username,
            $password,
            $confirmPassword,
            $gender,
            $securityCode
        );

        if (!$accountId) return false;

        $userAccountTable = Flux::config('FluxTables.MasterUserAccountTable');
        $sql = "INSERT INTO {$this->loginDatabase}.{$userAccountTable} ";
        $sql .= "(user_id, account_id, create_date) VALUES (?, ?, NOW())";
        $sth  = $this->connection->getStatement($sql);

        return $sth->execute(array($account->id, $accountId));
    }

    /**
     *
     */
    public function temporarilyBan($bannedBy, $banReason, $accountID, $until)
    {
        $table = Flux::config('FluxTables.AccountBanTable');

        $sql  = "INSERT INTO {$this->loginDatabase}.$table (account_id, banned_by, ban_type, ban_until, ban_date, ban_reason) ";
        $sql .= "VALUES (?, ?, 1, ?, NOW(), ?)";
        $sth  = $this->connection->getStatement($sql);

        if ($sth->execute(array($accountID, $bannedBy, $until, $banReason))) {
            $ts   = strtotime($until);
            $sql  = "UPDATE {$this->loginDatabase}.login SET state = 0, unban_time = '$ts' WHERE account_id = ?";
            $sth  = $this->connection->getStatement($sql);
            return $sth->execute(array($accountID));
        }
        else {
            return false;
        }
    }

    public function permanentlyBan($bannedBy, $banReason, $userId)
    {
        $table = Flux::config('FluxTables.MasterUserBanTable');
        $usersTable = Flux::config('FluxTables.MasterUserTable');
        $userColumns = Flux::config('FluxTables.MasterUserTableColumns');

        $sql  = "INSERT INTO {$this->loginDatabase}.$table (user_id, banned_by, ban_type, ban_until, ban_date, ban_reason) ";
        $sql .= "VALUES (?, ?, 2, '9999-12-31 23:59:59', NOW(), ?)";
        $sth  = $this->connection->getStatement($sql);

        if ($sth->execute(array($userId, $bannedBy, $banReason)) && !empty($userColumns->get('unban_at'))) {
            $sql = "UPDATE {$this->loginDatabase}.{$usersTable} SET {$userColumns->get('unban_at')} = NOW() WHERE id = ?";
            $sth = $this->connection->getStatement($sql);
            return $sth->execute(array($userId));
        }
        else {
            return false;
        }
    }

    public function unban($unbannedBy, $unbanReason, $userId)
    {
        $table = Flux::config('FluxTables.MasterUserBanTable');
        $usersTable = Flux::config('FluxTables.MasterUserTable');
        $userColumns = Flux::config('FluxTables.MasterUserTableColumns');

        $sql  = "INSERT INTO {$this->loginDatabase}.{$table} (user_id, banned_by, ban_type, ban_until, ban_date, ban_reason) ";
        $sql .= "VALUES (?, ?, 0, '1000-01-01 00:00:00', NOW(), ?)";
        $sth  = $this->connection->getStatement($sql);

        if ($sth->execute(array($userId, $unbannedBy, $unbanReason)) && !empty($userColumns->get('unban_at'))) {
            $sql  = "UPDATE {$this->loginDatabase}.{$usersTable} SET ";
            if (!$unbannedBy) {
                $sql .= "confirmed_date = NOW(), confirm_expire = NULL, ";
            }
            $sql .= "{$userColumns->get('unban_at')} = NULL WHERE id = ?";
            $sth  = $this->connection->getStatement($sql);
            $sth->execute(array($userId));
        }
        else {
            return false;
        }
    }

    /**
     *
     */
    public function getBanInfo($accountID)
    {
        $table = Flux::config('FluxTables.AccountBanTable');
        $col   = "$table.id, $table.account_id, $table.banned_by, $table.ban_type, ";
        $col  .= "$table.ban_until, $table.ban_date, $table.ban_reason, login.userid";
        $sql   = "SELECT $col FROM {$this->loginDatabase}.$table ";
        $sql  .= "LEFT OUTER JOIN {$this->loginDatabase}.login ON login.account_id = $table.banned_by ";
        $sql  .= "WHERE $table.account_id = ? ORDER BY $table.ban_date DESC ";
        $sth   = $this->connection->getStatement($sql);
        $res   = $sth->execute(array($accountID));

        if ($res) {
            $ban = $sth->fetchAll();
            return $ban;
        }
        else {
            return false;
        }
    }

    public function getGameAccount($userId, $accountID)
    {
        $userAccountTable = Flux::config('FluxTables.MasterUserAccountTable');
        $creditsTable  = Flux::config('FluxTables.CreditsTable');
        $creditColumns = 'credits.balance, credits.last_donation_date, credits.last_donation_amount';

        $sql  = "SELECT *, {$creditColumns}, login.account_id, login.userid, login.logincount, login.lastlogin, login.last_ip, login.sex";
        $sql .= " FROM {$this->loginDatabase}.{$userAccountTable} AS ua";
        $sql .= " JOIN {$this->loginDatabase}.login ON login.account_id = ua.account_id";
        $sql .= " LEFT OUTER JOIN {$this->loginDatabase}.{$creditsTable} AS credits ON login.account_id = credits.account_id ";
        $sql .= " WHERE ua.user_id = ? and login.account_id = ? ORDER BY ua.id ASC";
        $sth  = $this->connection->getStatement($sql);
        $res = $sth->execute(array($userId, $accountID));

        if ($res) {
            return $sth->fetch();
        }
        else {
            return false;
        }
    }

    public function getGameAccounts($userId, $toArray = false)
    {
        $userAccountTable = Flux::config('FluxTables.MasterUserAccountTable');

        $sql  = "SELECT *, login.account_id, login.userid, login.logincount, login.lastlogin, login.last_ip, login.sex";
        $sql .= " FROM {$this->loginDatabase}.{$userAccountTable} AS ua";
        $sql .= " JOIN {$this->loginDatabase}.login ON login.account_id = ua.account_id";
        $sql .= " WHERE ua.user_id = ? ORDER BY ua.id ASC";
        $sth  = $this->connection->getStatement($sql);
        $res = $sth->execute(array($userId));

        if ($res) {
            return $sth->fetchAll($toArray ? \PDO::FETCH_ASSOC : null);
        }
        else {
            return false;
        }
    }
}
?>
