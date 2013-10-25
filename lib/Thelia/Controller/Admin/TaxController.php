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

use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Event\Tax\TaxEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Form\TaxCreationForm;
use Thelia\Form\TaxModificationForm;
use Thelia\Form\TaxTaxListUpdateForm;
use Thelia\Model\TaxQuery;

class TaxController extends AbstractCrudController
{
    public function __construct()
    {
        parent::__construct(
            'tax',
            'manual',
            'order',

            AdminResources::TAX,

            TheliaEvents::TAX_CREATE,
            TheliaEvents::TAX_UPDATE,
            TheliaEvents::TAX_DELETE
        );
    }

    protected function getCreationForm()
    {
        return new TaxCreationForm($this->getRequest());
    }

    protected function getUpdateForm()
    {
        return new TaxModificationForm($this->getRequest());
    }

    protected function getCreationEvent($formData)
    {
        $event = new TaxEvent();

        $event->setLocale($formData['locale']);
        $event->setTitle($formData['title']);
        $event->setDescription($formData['description']);
        $event->setType($formData['type']);
        $event->setRequirements($this->getRequirements($formData['type'], $formData));

        return $event;
    }

    protected function getUpdateEvent($formData)
    {
        $event = new TaxEvent();

        $event->setLocale($formData['locale']);
        $event->setId($formData['id']);
        $event->setTitle($formData['title']);
        $event->setDescription($formData['description']);
        $event->setType($formData['type']);
        $event->setRequirements($this->getRequirements($formData['type'], $formData));

        return $event;
    }

    protected function getDeleteEvent()
    {
        $event = new TaxEvent();

        $event->setId(
            $this->getRequest()->get('tax_id', 0)
        );

        return $event;
    }

    protected function eventContainsObject($event)
    {
        return $event->hasTax();
    }

    protected function hydrateObjectForm($object)
    {
        $data = array(
            'id'           => $object->getId(),
            'locale'       => $object->getLocale(),
            'title'        => $object->getTitle(),
            'description'  => $object->getDescription(),
            'type'         => $object->getType(),
        );

        // Setup the object form
        return new TaxModificationForm($this->getRequest(), "form", $data);
    }

    protected function getObjectFromEvent($event)
    {
        return $event->hasTax() ? $event->getTax() : null;
    }

    protected function getExistingObject()
    {
        return TaxQuery::create()
            ->joinWithI18n($this->getCurrentEditionLocale())
            ->findOneById($this->getRequest()->get('tax_id'));
    }

    protected function getObjectLabel($object)
    {
        return $object->getTitle();
    }

    protected function getObjectId($object)
    {
        return $object->getId();
    }

    protected function getViewArguments()
    {
        return array();
    }

    protected function getRouteArguments($tax_id = null)
    {
        return array(
            'tax_id' => $tax_id === null ? $this->getRequest()->get('tax_id') : $tax_id,
        );
    }

    protected function renderListTemplate($currentOrder)
    {
        // We always return to the feature edition form
        return $this->render(
            'taxes-rules',
            array()
        );
    }

    protected function renderEditionTemplate()
    {
        // We always return to the feature edition form
        return $this->render('tax-edit', array_merge($this->getViewArguments(), $this->getRouteArguments()));
    }

    protected function redirectToEditionTemplate($request = null, $country = null)
    {
        // We always return to the feature edition form
        $this->redirectToRoute(
            "admin.configuration.taxes.update",
            $this->getViewArguments($country),
            $this->getRouteArguments()
        );
    }

    /**
     * Put in this method post object creation processing if required.
     *
     * @param  TaxEvent  $createEvent the create event
     * @return Response a response, or null to continue normal processing
     */
    protected function performAdditionalCreateAction($createEvent)
    {
        $this->redirectToRoute(
            "admin.configuration.taxes.update",
            $this->getViewArguments(),
            $this->getRouteArguments($createEvent->getTax()->getId())
        );
    }

    protected function redirectToListTemplate()
    {
        $this->redirectToRoute(
            "admin.configuration.taxes-rules.list"
        );
    }

    protected function checkRequirements($formData)
    {
        $type = $formData['type'];


    }

    protected function getRequirements($type, $formData)
    {
        $requirements = array();
        foreach($formData as $data => $value) {
            if(!strstr($data, ':')) {
                continue;
            }

            $couple = explode(':', $data);

            if(count($couple) != 2 || $couple[0] != $type) {
                continue;
            }

            $requirements[$couple[1]] = $value;
        }

        return $requirements;
    }
}