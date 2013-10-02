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

use Thelia\Core\Event\Message\MessageDeleteEvent;
use Thelia\Core\Event\TheliaEvents;use Thelia\Core\Event\Message\MessageUpdateEvent;
use Thelia\Core\Event\Message\MessageCreateEvent;
use Thelia\Model\MessageQuery;
use Thelia\Form\MessageModificationForm;
use Thelia\Form\MessageCreationForm;

/**
 * Manages messages sent by mail
 *
 * @author Franck Allimant <franck@cqfdev.fr>
 */
class MessageController extends AbstractCrudController
{
    public function __construct()
    {
        parent::__construct(
            'message',
            null, // no sort order change
            null, // no sort order change

            'admin.configuration.messages.view',
            'admin.configuration.messages.create',
            'admin.configuration.messages.update',
            'admin.configuration.messages.delete',

            TheliaEvents::MESSAGE_CREATE,
            TheliaEvents::MESSAGE_UPDATE,
            TheliaEvents::MESSAGE_DELETE,
            null, // No visibility toggle
            null  // No position update
        );
    }

    protected function getCreationForm()
    {
        return new MessageCreationForm($this->getRequest());
    }

    protected function getUpdateForm()
    {
        return new MessageModificationForm($this->getRequest());
    }

    protected function getCreationEvent($formData)
    {
        $createEvent = new MessageCreateEvent();

        $createEvent
            ->setMessageName($formData['name'])
            ->setLocale($formData["locale"])
            ->setTitle($formData['title'])
            ->setSecured($formData['secured'])
            ;

        return $createEvent;
    }

    protected function getUpdateEvent($formData)
    {
        $changeEvent = new MessageUpdateEvent($formData['id']);

        // Create and dispatch the change event
        $changeEvent
            ->setMessageName($formData['name'])
            ->setSecured($formData['secured'])
            ->setLocale($formData["locale"])
            ->setTitle($formData['title'])
            ->setSubject($formData['subject'])
            ->setHtmlMessage($formData['html_message'])
            ->setTextMessage($formData['text_message'])
        ;

        return $changeEvent;
    }

    protected function getDeleteEvent()
    {
        return new MessageDeleteEvent($this->getRequest()->get('message_id'));
    }

    protected function eventContainsObject($event)
    {
        return $event->hasMessage();
    }

    protected function hydrateObjectForm($object)
    {
        // Prepare the data that will hydrate the form
        $data = array(
            'id'           => $object->getId(),
            'name'         => $object->getName(),
            'secured'      => $object->getSecured(),
            'locale'       => $object->getLocale(),
            'title'        => $object->getTitle(),
            'subject'      => $object->getSubject(),
            'html_message' => $object->getHtmlMessage(),
            'text_message' => $object->getTextMessage()
        );

        // Setup the object form
        return new MessageModificationForm($this->getRequest(), "form", $data);
    }

    protected function getObjectFromEvent($event)
    {
        return $event->hasMessage() ? $event->getMessage() : null;
    }

    protected function getExistingObject()
    {
        return MessageQuery::create()
        ->joinWithI18n($this->getCurrentEditionLocale())
        ->findOneById($this->getRequest()->get('message_id'));
    }

    protected function getObjectLabel($object)
    {
        return $object->getName();
    }

    protected function getObjectId($object)
    {
        return $object->getId();
    }

    protected function renderListTemplate($currentOrder)
    {
        return $this->render('messages');
    }

    protected function renderEditionTemplate()
    {
        return $this->render('message-edit', array('message_id' => $this->getRequest()->get('message_id')));
    }

    protected function redirectToEditionTemplate()
    {
        $this->redirectToRoute(
                "admin.configuration.messages.update",
                array('message_id' => $this->getRequest()->get('message_id'))
        );
    }

    protected function redirectToListTemplate()
    {
        $this->redirectToRoute('admin.configuration.messages.default');
    }
}
