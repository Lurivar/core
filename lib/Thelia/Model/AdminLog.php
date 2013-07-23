<?php

namespace Thelia\Model;

use Thelia\Model\Base\AdminLog as BaseAdminLog;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Log\Tlog;
use Thelia\Model\Base\Admin as BaseAdminUser;

class AdminLog extends BaseAdminLog {

	/**
	 * A sdimple helper to insert an entry in the admin log
	 *
	 * @param unknown $actionLabel
	 * @param Request $request
	 * @param Admin $adminUser
	 */
	public static function append($actionLabel, Request $request, BaseAdminUser $adminUser = null) {

		$log = new AdminLog();

        $log
	       	->setAdminLogin($adminUser !== null ? $adminUser->getLogin() : '<no login>')
	       	->setAdminFirstname($adminUser !== null ? $adminUser->getFirstname() : '<no first name>')
	       	->setAdminLastname($adminUser !== null ? $adminUser->getLastname() : '<no last name>')
	       	->setAction($actionLabel)
	       	->setRequest($request->__toString())
	    ;

        try {
        	$log->save();
        }
        catch (\Exception $ex) {
        	Tlog::getInstance()->err("Failed to insert new entry in AdminLog: {ex}", array('ex' => $ex));
        }

	}
}