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

use Thelia\Core\Event\ConfigDeleteEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\Event\ConfigUpdateEvent;
use Thelia\Core\Event\ConfigCreateEvent;
use Thelia\Model\ConfigQuery;
use Thelia\Form\ConfigModificationForm;
use Thelia\Form\ConfigCreationForm;
use Thelia\Core\Event\UpdatePositionEvent;

/**
 * Manages variables
 *
 * @author Franck Allimant <franck@cqfdev.fr>
 */
class ConfigController extends AbstractCrudController
{
    public function __construct() {
        parent::__construct(
            'variable',
            'name',
            'order',

            'admin.configuration.variables.view',
            'admin.configuration.variables.create',
            'admin.configuration.variables.update',
            'admin.configuration.variables.delete',

            TheliaEvents::CONFIG_CREATE,
            TheliaEvents::CONFIG_UPDATE,
            TheliaEvents::CONFIG_DELETE,
            null, // No visibility toggle
            null // no position change
        );
    }

    protected function getCreationForm() {
        return new ConfigCreationForm($this->getRequest());
    }

    protected function getUpdateForm() {
        return new ConfigModificationForm($this->getRequest());
    }

    protected function getCreationEvent($data) {
        $createEvent = new ConfigCreateEvent();

        $createEvent
            ->setEventName($data['name'])
            ->setValue($data['value'])
            ->setLocale($data["locale"])
            ->setTitle($data['title'])
            ->setHidden($data['hidden'])
            ->setSecured($data['secured'])
            ;


        return $createEvent;
    }

    protected function getUpdateEvent($data) {
        $changeEvent = new ConfigUpdateEvent($data['id']);

        // Create and dispatch the change event
        $changeEvent
            ->setEventName($data['name'])
            ->setValue($data['value'])
            ->setHidden($data['hidden'])
            ->setSecured($data['secured'])
            ->setLocale($data["locale"])
            ->setTitle($data['title'])
            ->setChapo($data['chapo'])
            ->setDescription($data['description'])
            ->setPostscriptum($data['postscriptum'])
        ;

        return $changeEvent;
    }

    protected function getDeleteEvent() {
        return new ConfigDeleteEvent($this->getRequest()->get('variable_id'));
    }

    protected function eventContainsObject($event) {
        return $event->hasConfig();
    }

    protected function hydrateObjectForm($object) {

        // Prepare the data that will hydrate the form
        $data = array(
            'id'           => $object->getId(),
            'name'         => $object->getName(),
            'value'        => $object->getValue(),
            'hidden'       => $object->getHidden(),
            'secured'      => $object->getSecured(),
            'locale'       => $object->getLocale(),
            'title'        => $object->getTitle(),
            'chapo'        => $object->getChapo(),
            'description'  => $object->getDescription(),
            'postscriptum' => $object->getPostscriptum()
        );

        // Setup the object form
        return new ConfigModificationForm($this->getRequest(), "form", $data);
    }

    protected function getObjectFromEvent($event) {
        return $event->hasConfig() ? $event->getConfig() : null;
    }

    protected function getExistingObject() {
        return ConfigQuery::create()
        ->joinWithI18n($this->getCurrentEditionLocale())
        ->findOneById($this->getRequest()->get('variable_id'));
    }

    protected function getObjectLabel($object) {
        return $object->getName();
    }

    protected function getObjectId($object) {
        return $object->getId();
    }

    protected function renderListTemplate($currentOrder) {
        return $this->render('variables', array('order' => $currentOrder));
    }

    protected function renderEditionTemplate() {
        return $this->render('variable-edit', array('variable_id' => $this->getRequest()->get('variable_id')));
    }

    protected function redirectToEditionTemplate() {
        $this->redirectToRoute(
                "admin.configuration.variables.update",
                array('variable_id' => $this->getRequest()->get('variable_id'))
        );
    }

    protected function redirectToListTemplate() {
        $this->redirectToRoute('admin.configuration.variables.default');
    }

    /**
     * Change values modified directly from the variable list
     *
     * @return Symfony\Component\HttpFoundation\Response the response
     */
    public function changeValuesAction()
    {
        // Check current user authorization
        if (null !== $response = $this->checkAuth("admin.configuration.variables.update")) return $response;

        $variables = $this->getRequest()->get('variable', array());

        // Process all changed variables
        foreach ($variables as $id => $value) {
            $event = new ConfigUpdateEvent($id);
            $event->setValue($value);

            $this->dispatch(TheliaEvents::CONFIG_SETVALUE, $event);
        }

        $this->redirectToRoute('admin.configuration.variables.default');
    }
}