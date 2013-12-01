<?php

namespace Mailer\Core;

use Mailer\Error\LogicError;
use Mailer\Security\Account;

/**
 * Very simple session component
 *
 * @todo Make it backendable
 */
class Session
{
    /**
     * @var Account
     */
    private $account;

    /**
     * @var boolean
     */
    private $started = false;

    /**
     * @var boolean
     */
    private $destroyed = false;

    /**
     * @var boolean
     */
    private $regenerated = false;

    /**
     * @var \ArrayAccess
     */
    private $storage;

    /**
     * Default constructor
     */
    public function __construct()
    {
        $this->storage = new NativeSessionStorage();
    }

    /**
     * Is the session started
     *
     * @return boolean
     */
    public function isStarted()
    {
        return $this->started;
    }

    /**
     * Start session
     *
     * @return boolean
     */
    public function start()
    {
        if ($this->destroyed) {
            throw new LogicError("Cannot start as destroyed session");
        }
        if ($this->regenerated) {
            throw new LogicError("Cannot start as regenerated session");
        }

        if (!$this->started) {
            $this->started = session_start();
        }

        return $this->started;
    }

    /**
     * Regenerate session
     *
     * @param Account $account
     *
     * @return boolean
     */
    public function regenerate(Account $account = null)
    {
        if (!$this->regenerated) {
            $this->regenerated = session_regenerate_id(true);
            $this->setAccount($account);
        }

        return $this->regenerated;
    }

    /**
     * Destroy session
     *
     * @return boolean
     */
    public function destroy()
    {
        if (!$this->destroyed) {
            $this->destroyed = session_destroy();
        }

        return $this->destroyed;
    }

    /**
     * Commit session
     */
    public function commit()
    {
        session_write_close();
    }

    /**
     * Get storage
     *
     * @return \ArrayAccess
     */
    public function getStorage()
    {
        return $this->storage;
    }

    /**
     * Set logged in account
     *
     * @param Account $account
     */
    public function setAccount(Account $account = null)
    {
        $this->account = $account;
        $this->storage['account'] = $account;
    }

    /**
     * Get logged in account
     *
     * @return Account
     */
    public function getAccount()
    {
        if (null === $this->account) {
            if (isset($this->storage['account'])) {
                $this->account = $this->storage['account'];
            } else {
                $this->account = new Account(0, "anonymous", null);
            }
        }

        return $this->account;
    }

    /**
     * Is current account authenticated
     */
    public function isAuthenticated()
    {
        return 0 != $this->getAccount()->getId();
    }
}
