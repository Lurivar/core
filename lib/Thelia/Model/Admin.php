<?php

namespace Thelia\Model;

use Propel\Runtime\ActiveQuery\Criteria;
use Thelia\Core\Event\AdminResources;
use Thelia\Core\Security\User\UserInterface;
use Thelia\Core\Security\Role\Role;

use Thelia\Model\Base\Admin as BaseAdmin;
use Propel\Runtime\Connection\ConnectionInterface;

/**
 * Skeleton subclass for representing a row from the 'admin' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 * @package    propel.generator.Thelia.Model
 */
class Admin extends BaseAdmin implements UserInterface
{
    public function getPermissions()
    {
        $profileId = $this->getProfileId();

        if( null === $profileId ) {
            return AdminResources::SUPERADMINISTRATOR;
        }

        $userPermissionsQuery = ProfileResourceQuery::create()
            ->joinResource("resource", Criteria::LEFT_JOIN)
            ->withColumn('resource.code', 'code')
            ->filterByProfileId($profileId)
            ->find();

        $userPermissions = array();
        foreach($userPermissionsQuery as $userPermission) {
            $userPermissions[] = $userPermission->getVirtualColumn('code');
        }

        return $userPermissions;
    }

    /**
     * {@inheritDoc}
     */
    public function preInsert(ConnectionInterface $con = null)
    {
        // Set the serial number (for auto-login)
        $this->setRememberMeSerial(uniqid());

        return true;
    }

    public function setPassword($password)
    {
        if ($this->isNew() && ($password === null || trim($password) == "")) {
            throw new \InvalidArgumentException("customer password is mandatory on creation");
        }

        if($password !== null && trim($password) != "") {
            $this->setAlgo("PASSWORD_BCRYPT");
            return parent::setPassword(password_hash($password, PASSWORD_BCRYPT));
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function checkPassword($password)
    {
    	return password_verify($password, $this->password);
    }

	/**
     * {@inheritDoc}
     */
    public function getUsername() {
        return $this->getLogin();
    }

    /**
     * {@inheritDoc}
     */
    public function eraseCredentials() {
    	$this->setPassword(null);
    }

    /**
     * {@inheritDoc}
     */
    public function getRoles() {
    	return array(new Role('ADMIN'));
    }

    /**
     * {@inheritDoc}
     */
    public function getToken() {
        return $this->getRememberMeToken();
    }

    /**
     * {@inheritDoc}
     */
    public function setToken($token) {
        $this->setRememberMeToken($token)->save();
    }

    /**
     * {@inheritDoc}
     */
    public function getSerial() {
        return $this->getRememberMeSerial();
    }

    /**
     * {@inheritDoc}
     */
    public function setSerial($serial) {
        $this->setRememberMeSerial($serial)->save();
    }
}
