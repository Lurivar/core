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

use Thelia\Core\Event\Attribute\AttributeDeleteEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\Event\Attribute\AttributeUpdateEvent;
use Thelia\Core\Event\Attribute\AttributeCreateEvent;
use Thelia\Model\AttributeQuery;
use Thelia\Form\AttributeModificationForm;
use Thelia\Form\AttributeCreationForm;
use Thelia\Core\Event\UpdatePositionEvent;
use Thelia\Model\AttributeAv;
use Thelia\Model\AttributeAvQuery;
use Thelia\Core\Event\Attribute\AttributeAvUpdateEvent;
use Thelia\Core\Event\Attribute\AttributeEvent;

/**
 * Manages attributes
 *
 * @author Franck Allimant <franck@cqfdev.fr>
 */
class AttributeController extends AbstractCrudController
{
    public function __construct()
    {
        parent::__construct(
            'attribute',
            'manual',
            'order',

            'admin.configuration.attributes.view',
            'admin.configuration.attributes.create',
            'admin.configuration.attributes.update',
            'admin.configuration.attributes.delete',

            TheliaEvents::ATTRIBUTE_CREATE,
            TheliaEvents::ATTRIBUTE_UPDATE,
            TheliaEvents::ATTRIBUTE_DELETE,
            null, // No visibility toggle
            TheliaEvents::ATTRIBUTE_UPDATE_POSITION
        );
    }

    protected function getCreationForm()
    {
        return new AttributeCreationForm($this->getRequest());
    }

    protected function getUpdateForm()
    {
        return new AttributeModificationForm($this->getRequest());
    }

    protected function getCreationEvent($formData)
    {
        $createEvent = new AttributeCreateEvent();

        $createEvent
            ->setTitle($formData['title'])
            ->setLocale($formData["locale"])
            ->setAddToAllTemplates($formData['add_to_all'])
        ;

        return $createEvent;
    }

    protected function getUpdateEvent($formData)
    {
        $changeEvent = new AttributeUpdateEvent($formData['id']);

        // Create and dispatch the change event
        $changeEvent
            ->setLocale($formData["locale"])
            ->setTitle($formData['title'])
            ->setChapo($formData['chapo'])
            ->setDescription($formData['description'])
            ->setPostscriptum($formData['postscriptum'])
        ;

        return $changeEvent;
    }

    /**
     * Process the attributes values (fix it in future version to integrate it in the attribute form as a collection)
     *
     * @see \Thelia\Controller\Admin\AbstractCrudController::performAdditionalUpdateAction()
     */
    protected function performAdditionalUpdateAction($updateEvent)
    {
        $attr_values = $this->getRequest()->get('attribute_values', null);

        if ($attr_values !== null) {

            foreach($attr_values as $id => $value) {

                $event = new AttributeAvUpdateEvent($id);

                $event->setTitle($value);
                $event->setLocale($this->getCurrentEditionLocale());

                $this->dispatch(TheliaEvents::ATTRIBUTE_AV_UPDATE, $event);
            }
        }

        return null;
    }

    protected function createUpdatePositionEvent($positionChangeMode, $positionValue)
    {
        return new UpdatePositionEvent(
                $this->getRequest()->get('attribute_id', null),
                $positionChangeMode,
                $positionValue
        );
    }

    protected function getDeleteEvent()
    {
        return new AttributeDeleteEvent($this->getRequest()->get('attribute_id'));
    }

    protected function eventContainsObject($event)
    {
        return $event->hasAttribute();
    }

    protected function hydrateObjectForm($object)
    {

        $data = array(
            'id'           => $object->getId(),
            'locale'       => $object->getLocale(),
            'title'        => $object->getTitle(),
            'chapo'        => $object->getChapo(),
            'description'  => $object->getDescription(),
            'postscriptum' => $object->getPostscriptum()
        );

        // Setup attributes values
        /*
         * FIXME : doesn't work. "We get a This form should not contain extra fields." error
        $attr_av_list = AttributeAvQuery::create()
                    ->joinWithI18n($this->getCurrentEditionLocale())
                    ->filterByAttributeId($object->getId())
                    ->find();

        $attr_array = array();

        foreach($attr_av_list as $attr_av) {
            $attr_array[$attr_av->getId()] = $attr_av->getTitle();
        }

        $data['attribute_values'] = $attr_array;
        */

        // Setup the object form
        return new AttributeModificationForm($this->getRequest(), "form", $data);
    }

    protected function getObjectFromEvent($event)
    {
        return $event->hasAttribute() ? $event->getAttribute() : null;
    }

    protected function getExistingObject()
    {
        return AttributeQuery::create()
        ->joinWithI18n($this->getCurrentEditionLocale())
        ->findOneById($this->getRequest()->get('attribute_id'));
    }

    protected function getObjectLabel($object)
    {
        return $object->getTitle();
    }

    protected function getObjectId($object)
    {
        return $object->getId();
    }

    protected function renderListTemplate($currentOrder)
    {
        return $this->render('attributes', array('order' => $currentOrder));
    }

    protected function renderEditionTemplate()
    {
        return $this->render(
                'attribute-edit',
                array(
                        'attribute_id' => $this->getRequest()->get('attribute_id'),
                        'attributeav_order' => $this->getAttributeAvListOrder()
                )
        );
    }

    protected function redirectToEditionTemplate()
    {
        $this->redirectToRoute(
                "admin.configuration.attributes.update",
                array(
                        'attribute_id' => $this->getRequest()->get('attribute_id'),
                        'attributeav_order' => $this->getAttributeAvListOrder()
                )
        );
    }

    protected function redirectToListTemplate()
    {
        $this->redirectToRoute('admin.configuration.attributes.default');
    }

    /**
     * Get the Attribute value list order.
     *
     * @return string the current list order
     */
    protected function getAttributeAvListOrder()
    {
        return $this->getListOrderFromSession(
                'attributeav',
                'attributeav_order',
                'manual'
        );
    }

    /**
     * Add or Remove from all product templates
     */
    protected function addRemoveFromAllTemplates($eventType)
    {
        // Check current user authorization
        if (null !== $response = $this->checkAuth("admin.configuration.attributes.update")) return $response;

        try {
            if (null !== $object = $this->getExistingObject()) {

                $event = new AttributeEvent($object);

                $this->dispatch($eventType, $event);
            }
        }
        catch (\Exception $ex) {
            // Any error
            return $this->errorPage($ex);
        }

        $this->redirectToListTemplate();
    }

    /**
     * Remove from all product templates
     */
    public function removeFromAllTemplates()
    {
        return $this->addRemoveFromAllTemplates(TheliaEvents::ATTRIBUTE_REMOVE_FROM_ALL_TEMPLATES);
    }

    /**
     * Add to all product templates
     */
    public function addToAllTemplates()
    {
        return $this->addRemoveFromAllTemplates(TheliaEvents::ATTRIBUTE_ADD_TO_ALL_TEMPLATES);
    }
}