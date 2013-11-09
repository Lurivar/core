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

namespace Thelia\Controller\Admin;

use Propel\Runtime\Exception\PropelException;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Event\Customer\CustomerCreateOrUpdateEvent;
use Thelia\Core\Event\Customer\CustomerEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\Security\AccessManager;
use Thelia\Form\CustomerCreateForm;
use Thelia\Form\CustomerUpdateForm;
use Thelia\Form\Exception\FormValidationException;
use Thelia\Model\CustomerQuery;
use Thelia\Core\Translation\Translator;
use Thelia\Tools\Password;
use Thelia\Model\AddressQuery;
use Thelia\Model\Address;

/**
 * Class CustomerController
 * @package Thelia\Controller\Admin
 * @author Manuel Raynaud <mraynaud@openstudio.fr>
 */
class CustomerController extends AbstractCrudController
{
    public function __construct()
    {
        parent::__construct(
                'customer',
                'lastname',
                'customer_order',

                AdminResources::CUSTOMER,

                TheliaEvents::CUSTOMER_CREATEACCOUNT,
                TheliaEvents::CUSTOMER_UPDATEACCOUNT,
                TheliaEvents::CUSTOMER_DELETEACCOUNT
        );
    }

    protected function getCreationForm()
    {
        return new CustomerCreateForm($this->getRequest());
    }

    protected function getUpdateForm()
    {
        return new CustomerUpdateForm($this->getRequest());
    }

    protected function getCreationEvent($formData)
    {
        return $this->createEventInstance($formData);
    }

    protected function getUpdateEvent($formData)
    {
        $event = $this->createEventInstance($formData);

        $event->setCustomer($this->getExistingObject());

        return $event;
    }

    protected function getDeleteEvent()
    {
        return new CustomerEvent($this->getExistingObject());
    }

    protected function eventContainsObject($event)
    {
        return $event->hasCustomer();
    }

    protected function hydrateObjectForm($object)
    {
        // Get default adress of the customer
        $address = $object->getDefaultAddress();

        // Prepare the data that will hydrate the form
        $data = array(
                'id'        => $object->getId(),
                'firstname' => $object->getFirstname(),
                'lastname'  => $object->getLastname(),
                'email'     => $object->getEmail(),
                'title'     => $object->getTitleId(),

                'company'   => $address->getCompany(),
                'address1'  => $address->getAddress1(),
                'address2'  => $address->getAddress2(),
                'address3'  => $address->getAddress3(),
                'phone'     => $address->getPhone(),
                'cellphone' => $address->getCellphone(),
                'zipcode'   => $address->getZipcode(),
                'city'      => $address->getCity(),
                'country'   => $address->getCountryId(),
        );

        // A loop is used in the template
        return new CustomerUpdateForm($this->getRequest(), 'form', $data);
    }

    protected function getObjectFromEvent($event)
    {
        return $event->hasCustomer() ? $event->getCustomer() : null;
    }

    /**
     * @param $data
     * @return \Thelia\Core\Event\Customer\CustomerCreateOrUpdateEvent
     */
    private function createEventInstance($data)
    {
        $customerCreateEvent = new CustomerCreateOrUpdateEvent(
                $data["title"],
                $data["firstname"],
                $data["lastname"],
                $data["address1"],
                $data["address2"],
                $data["address3"],
                $data["phone"],
                $data["cellphone"],
                $data["zipcode"],
                $data["city"],
                $data["country"],
                isset($data["email"])?$data["email"]:null,
                isset($data["password"]) && ! empty($data["password"]) ? $data["password"]:null,
                $this->getRequest()->getSession()->getLang()->getId(),
                isset($data["reseller"])?$data["reseller"]:null,
                isset($data["sponsor"])?$data["sponsor"]:null,
                isset($data["discount"])?$data["discount"]:null,
                isset($data["company"])?$data["company"]:null
        );

        return $customerCreateEvent;
    }

    protected function getExistingObject()
    {
        return CustomerQuery::create()->findPk($this->getRequest()->get('customer_id', 0));
    }

    protected function getObjectLabel($object)
    {
        return $object->getRef() . "(".$object->getLastname()." ".$object->getFirstname().")";
    }

    protected function getObjectId($object)
    {
        return $object->getId();
    }

    protected function getEditionArguments()
    {
        return array(
                'customer_id' => $this->getRequest()->get('customer_id', 0),
                'page'        => $this->getRequest()->get('page', 1)
        );
    }

    protected function renderListTemplate($currentOrder)
    {
        return $this->render('customers', array(
                'customer_order'   => $currentOrder,
                'display_customer' => 20,
                'page'             => $this->getRequest()->get('page', 1)
        ));
    }

    protected function redirectToListTemplate()
    {
        $this->redirectToRoute('admin.customers', array(
                'page' => $this->getRequest()->get('page', 1))
        );
    }

    protected function renderEditionTemplate()
    {
        return $this->render('customer-edit', $this->getEditionArguments());
    }

    protected function redirectToEditionTemplate()
    {
        $this->redirectToRoute("admin.customer.update.view", $this->getEditionArguments());
    }
}