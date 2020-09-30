<?php
require_once 'Flux/DataObject.php';
require_once 'Flux/ItemShop/Cart.php';
require_once 'Flux/LoginError.php';

/**
 * Contains all of Flux's session data.
 */
class Flux_MasterSessionData extends Flux_SessionData {
    /**
     * Create new SessionData instance.
     *
     * @param array $sessionData
     * @access public
     */
    public function __construct(array &$sessionData, $logout = false)
    {
        parent::__construct($sessionData, $logout);
        if ($logout) {
            $this->logout();
        }
        else {
            $this->initialize();
        }
    }

    /**
     * Initialize session data.
     *
     * @param bool $force
     * @return bool
     * @access private
     */
    private function initialize($force = false)
    {
        $keysToInit = array('username', 'serverName', 'athenaServerName', 'securityCode', 'theme');
        foreach ($keysToInit as $key) {
            if ($force || !$this->{$key}) {
                $method = ucfirst($key);
                $method = "set{$method}Data";
                $this->$method(null);
            }
        }

        $loggedIn = true;
        if (!$this->username) {
            $loggedIn = false;
            $cfgAthenaServerName = Flux::config('DefaultCharMapServer');
            $cfgLoginAthenaGroup = Flux::config('DefaultLoginGroup');

            if (Flux::getServerGroupByName($cfgLoginAthenaGroup)){
                $this->setServerNameData($cfgLoginAthenaGroup);
            }
            else {
                $defaultServerName = current(array_keys(Flux::$loginAthenaGroupRegistry));
                $this->setServerNameData($defaultServerName);
            }
        }

        if ($this->serverName && ($this->loginAthenaGroup = Flux::getServerGroupByName($this->serverName))) {
            $this->loginServer = $this->loginAthenaGroup->loginServer;

            if (!$loggedIn && $cfgAthenaServerName && $this->getAthenaServer($cfgAthenaServerName)) {
                $this->setAthenaServerNameData($cfgAthenaServerName);
            }

            if (!$this->athenaServerName || ((!$loggedIn && !$this->getAthenaServer($cfgAthenaServerName)) || !$this->getAthenaServer($this->athenaServerName))) {
                $this->setAthenaServerNameData(current($this->getAthenaServerNames()));
            }
        }

        // Get new account data every request.
        if ($this->loginAthenaGroup && $this->username && ($account = $this->getAccount($this->loginAthenaGroup, $this->username))) {
            $this->account = $account;
            $this->account->group_level = AccountLevel::getGroupLevel($account->group_id);

            // Automatically log out of account when detected as banned.
            if (($account->unban_date && $account->unban_date < new DateTime()) && !Flux::config('AllowTempBanLogin')) {
                $this->logout();
            }
        }
        else {
            $this->account = new Flux_DataObject(null, array('group_level' => AccountLevel::UNAUTH));
        }

        if (!is_array($this->cart)) {
            $this->setCartData(array());
        }

        if ($this->account->account_id && $this->loginAthenaGroup) {
            if (!array_key_exists($this->loginAthenaGroup->serverName, $this->cart)) {
                $this->cart[$this->loginAthenaGroup->serverName] = array();
            }

            foreach ($this->getAthenaServerNames() as $athenaServerName) {
                $athenaServer = $this->getAthenaServer($athenaServerName);
                $cartArray    = &$this->cart[$this->loginAthenaGroup->serverName];
                $accountID    = $this->account->account_id;

                if (!array_key_exists($accountID, $cartArray)) {
                    $cartArray[$accountID] = array();
                }

                if (!array_key_exists($athenaServerName, $cartArray[$accountID])) {
                    $cartArray[$accountID][$athenaServerName] = new Flux_ItemShop_Cart();
                }
                $cartArray[$accountID][$athenaServerName]->setAccount($this->account);
                $athenaServer->setCart($cartArray[$accountID][$athenaServerName]);
            }
        }

        if (!$this->theme || $this->theme === 'installer') { // always update if coming from installer
            $this->setThemeData(Flux::config('ThemeName.0'));
        }

        return true;
    }

    public function login($server, $email, $password, $securityCode = null)
    {
        $loginAthenaGroup = Flux::getServerGroupByName($server);
        if (!$loginAthenaGroup) {
            throw new Flux_LoginError('Invalid server.', Flux_LoginError::INVALID_SERVER);
        }

        if ($loginAthenaGroup->loginServer->isIpBanned() && !Flux::config('AllowIpBanLogin')) {
            throw new Flux_LoginError('IP address is banned', Flux_LoginError::IPBANNED);
        }

        if ($securityCode !== false && Flux::config('UseLoginCaptcha')) {
            if (strtolower($securityCode) != strtolower($this->securityCode)) {
                throw new Flux_LoginError('Invalid security code', Flux_LoginError::INVALID_SECURITY_CODE);
            }
            elseif (Flux::config('EnableReCaptcha')) {
                if(isset($_POST['g-recaptcha-response']) && $_POST['g-recaptcha-response'] != ""){
                    $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=".Flux::config('ReCaptchaPrivateKey')."&response=".$_POST['g-recaptcha-response']."&remoteip=".$_SERVER['REMOTE_ADDR']);
                }
                $responseKeys = json_decode($response,true);
                if(intval($responseKeys["success"]) !== 1) {
                    throw new Flux_LoginError('Invalid security code', Flux_LoginError::INVALID_SECURITY_CODE);
                }
            }
        }

        if (!$loginAthenaGroup->isAuth($email, $password)) {
            throw new Flux_LoginError('Invalid login', Flux_LoginError::INVALID_LOGIN);
        }

        $usersTable = Flux::config('FluxTables.MasterUserTable');
        $userColumns = Flux::config('FluxTables.MasterUserTableColumns');
        $sql  = "SELECT * FROM {$loginAthenaGroup->loginDatabase}.{$usersTable} ";
        $sql .= "WHERE {$userColumns->get('group_id')} >= 0 AND {$userColumns->get('email')} = ? LIMIT 1";
        $smt  = $loginAthenaGroup->connection->getStatement($sql);
        $res  = $smt->execute(array($email));
        $unbanAt = $userColumns->get('unban_at');

        if ($res && ($row = $smt->fetch())) {
            if ($row->$unbanAt) {
                if (new DateTime() > new DateTime($row->$userColumns->get('unban_at'))) {
                    $row->$unbanAt = 0;
                    $sql = "UPDATE {$loginAthenaGroup->loginDatabase}.{$usersTable} SET {$userColumns->get('unban_at')} = NULL WHERE {$userColumns->get('id')} = ?";
                    $sth = $loginAthenaGroup->connection->getStatement($sql);
                    $sth->execute(array($row->$unbanAt));
                }
                elseif (!Flux::config('AllowTempBanLogin')) {
                    throw new Flux_LoginError('Temporarily banned', Flux_LoginError::BANNED);
                }
            }
            if (is_null($row->confirmed_date) && Flux::config('RequireEmailConfirm')) {
                throw new Flux_LoginError('Pending confirmation', Flux_LoginError::PENDING_CONFIRMATION);
            }

            $this->setServerNameData($server);
            $this->setUsernameData($email);
            $this->initialize(false);
        }
        else {
            $message  = "Unexpected error during login.\n";
            $message .= 'PDO error info, if any: '.print_r($smt->errorInfo(), true);
            throw new Flux_LoginError($message, Flux_LoginError::UNEXPECTED);
        }

        return true;
    }


    private function getAccount(Flux_LoginAthenaGroup $loginAthenaGroup, $email)
    {
        $usersTable = Flux::config('FluxTables.MasterUserTable');
        $userColumns = Flux::config('FluxTables.MasterUserTableColumns');

        $sql  = "SELECT *, {$userColumns->get('email')} as userid FROM {$loginAthenaGroup->loginDatabase}.{$usersTable} ";
        $sql .= "WHERE {$userColumns->get('group_id')} >= 0 AND {$userColumns->get('email')} = ? LIMIT 1";
        $smt  = $loginAthenaGroup->connection->getStatement($sql);
        $res  = $smt->execute(array($email));

        if ($res && ($row = $smt->fetch())) {
            return $row;
        }
        else {
            return false;
        }
    }
}
?>
