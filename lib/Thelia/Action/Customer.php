<?php
/*************************************************************************************/
/*                                                                                   */
/*      Thelia	                                                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : info@thelia.net                                                      */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      This program is free software; you can redistribute it and/or modify         */
/*      it under the terms of the GNU General Public License as published by         */
/*      the Free Software Foundation; either version 3 of the License                */
/*                                                                                   */
/*      This program is distributed in the hope that it will be useful,              */
/*      but WITHOUT ANY WARRANTY; without even the implied warranty of               */
/*      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the                */
/*      GNU General Public License for more details.                                 */
/*                                                                                   */
/*      You should have received a copy of the GNU General Public License            */
/*	    along with this program. If not, see <http://www.gnu.org/licenses/>.         */
/*                                                                                   */
/*************************************************************************************/

namespace Thelia\Action;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Core\Event\ActionEvent;
use Thelia\Core\Event\CustomerCreateOrUpdateEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Model\Customer as CustomerModel;
use Thelia\Core\Event\CustomerLoginEvent;

/**
 *
 * customer class where all actions are managed
 *
 * Class Customer
 * @package Thelia\Action
 * @author Manuel Raynaud <mraynaud@openstudio.fr>
 */
class Customer extends BaseAction implements EventSubscriberInterface
{

    public function create(CustomerCreateOrUpdateEvent $event)
    {

        $customer = new CustomerModel();

        $this->createOrUpdateCustomer($customer, $event);

    }

    public function modify(CustomerCreateOrUpdateEvent $event)
    {

        $customer = $event->getCustomer();

        $this->createOrUpdateCustomer($customer, $event);

    }

    private function createOrUpdateCustomer(CustomerModel $customer, CustomerCreateOrUpdateEvent $event)
    {
        $customer->setDispatcher($this->getDispatcher());

        $customer->createOrUpdate(
            $event->getTitle(),
            $event->getFirstname(),
            $event->getLastname(),
            $event->getAddress1(),
            $event->getAddress2(),
            $event->getAddress3(),
            $event->getPhone(),
            $event->getCellphone(),
            $event->getZipcode(),
            $event->getCity(),
            $event->getCountry(),
            $event->getEmail(),
            $event->getPassword(),
            $event->getLang(),
            $event->getReseller(),
            $event->getSponsor(),
            $event->getDiscount()
        );

        $event->setCustomer($customer);
    }

    public function login(CustomerLoginEvent $event)
    {
        $this->getSecurityContext()->setCustomerUser($event->getCustomer());
    }

    /**
     * Perform user logout. The user is redirected to the provided view, if any.
     *
     * @param ActionEvent $event
     */
    public function logout(ActionEvent $event)
    {
        $this->getSecurityContext()->clearCustomerUser();
    }

    public function changePassword(ActionEvent $event)
    {
    // TODO
    }

    /**
     * Return the security context
     *
     * @return Thelia\Core\Security\SecurityContext
     */
    protected function getSecurityContext()
    {
        return $this->container->get('thelia.securityContext');
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     *
     * @api
     */
    public static function getSubscribedEvents()
    {
        return array(
            TheliaEvents::CUSTOMER_CREATEACCOUNT => array("create", 128),
            TheliaEvents::CUSTOMER_UPDATEACCOUNT => array("modify", 128),
            TheliaEvents::CUSTOMER_LOGOUT        => array("logout", 128),
            TheliaEvents::CUSTOMER_LOGIN         => array("login" , 128),
        );
    }
}
