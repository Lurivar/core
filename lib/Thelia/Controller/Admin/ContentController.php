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
use Thelia\Core\Event\Content\ContentCreateEvent;
use Thelia\Core\Event\Content\ContentDeleteEvent;
use Thelia\Core\Event\Content\ContentToggleVisibilityEvent;
use Thelia\Core\Event\Content\ContentUpdateEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\Event\UpdatePositionEvent;
use Thelia\Form\ContentCreationForm;
use Thelia\Form\ContentModificationForm;
use Thelia\Model\ContentQuery;

/**
 * Class ContentController
 * @package Thelia\Controller\Admin
 * @author manuel raynaud <mraynaud@openstudio.fr>
 */
class ContentController extends AbstractCrudController
{

    public function __construct()
    {
        parent::__construct(
            'content',
            'manual',
            'content_order',

            'admin.content.default',
            'admin.content.create',
            'admin.content.update',
            'admin.content.delete',

            TheliaEvents::CONTENT_CREATE,
            TheliaEvents::CONTENT_UPDATE,
            TheliaEvents::CONTENT_DELETE,
            TheliaEvents::CONTENT_TOGGLE_VISIBILITY,
            TheliaEvents::CONTENT_UPDATE_POSITION
        );
    }

    /**
     * Return the creation form for this object
     */
    protected function getCreationForm()
    {
        return new ContentCreationForm($this->getRequest());
    }

    /**
     * Return the update form for this object
     */
    protected function getUpdateForm()
    {
        return new ContentModificationForm($this->getRequest());
    }

    /**
     * Hydrate the update form for this object, before passing it to the update template
     *
     * @param \Thelia\Form\ContentModificationForm $object
     */
    protected function hydrateObjectForm($object)
    {
        // Prepare the data that will hydrate the form
        $data = array(
            'id'           => $object->getId(),
            'locale'       => $object->getLocale(),
            'title'        => $object->getTitle(),
            'chapo'        => $object->getChapo(),
            'description'  => $object->getDescription(),
            'postscriptum' => $object->getPostscriptum(),
            'visible'      => $object->getVisible(),
            'url'          => $object->getRewrittenUrl($this->getCurrentEditionLocale()),
        );

        // Setup the object form
        return new ContentModificationForm($this->getRequest(), "form", $data);
    }

    /**
     * Creates the creation event with the provided form data
     *
     * @param unknown $formData
     */
    protected function getCreationEvent($formData)
    {
        $contentCreateEvent = new ContentCreateEvent();

        $contentCreateEvent
            ->setLocale($formData['locale'])
            ->setDefaultFolder($formData['default_folder'])
            ->setTitle($formData['title'])
            ->setVisible($formData['visible'])
        ;

        return $contentCreateEvent;
    }

    /**
     * Creates the update event with the provided form data
     *
     * @param unknown $formData
     */
    protected function getUpdateEvent($formData)
    {
        $contentUpdateEvent = new ContentUpdateEvent($formData['id']);

        $contentUpdateEvent
            ->setLocale($formData['locale'])
            ->setTitle($formData['title'])
            ->setChapo($formData['chapo'])
            ->setDescription($formData['description'])
            ->setPostscriptum($formData['postscriptum'])
            ->setVisible($formData['visible'])
            ->setUrl($formData['url'])
            ->setDefaultFolder($formData['default_folder']);

        return $contentUpdateEvent;
    }

    /**
     * Creates the delete event with the provided form data
     */
    protected function getDeleteEvent()
    {
        return new ContentDeleteEvent($this->getRequest()->get('content_id'));
    }

    /**
     * Return true if the event contains the object, e.g. the action has updated the object in the event.
     *
     * @param \Thelia\Core\Event\Content\ContentEvent $event
     */
    protected function eventContainsObject($event)
    {
        return $event->hasContent();
    }

    /**
     * Get the created object from an event.
     *
     * @param $event \Thelia\Core\Event\Content\ContentEvent
     *
     * @return null|\Thelia\Model\Content
     */
    protected function getObjectFromEvent($event)
    {
        return $event->getContent();
    }

    /**
     * Load an existing object from the database
     *
     * @return \Thelia\Model\Content
     */
    protected function getExistingObject()
    {
        return ContentQuery::create()
            ->joinWithI18n($this->getCurrentEditionLocale())
            ->findOneById($this->getRequest()->get('content_id', 0));
    }

    /**
     * Returns the object label form the object event (name, title, etc.)
     *
     * @param $object \Thelia\Model\Content
     *
     * @return string content title
     *
     */
    protected function getObjectLabel($object)
    {
        return $object->getTitle();
    }

    /**
     * Returns the object ID from the object
     *
     * @param $object \Thelia\Model\Content
     *
     * @return int content id
     */
    protected function getObjectId($object)
    {
        return $object->getId();
    }

    protected function getFolderId()
    {
        $folderId = $this->getRequest()->get('folder_id', null);

        if (null === $folderId) {
            $content = $this->getExistingObject();

            if ($content) {
                $folderId = $content->getDefaultFolderId();
            }
        }

        return $folderId ?: 0;
    }

    /**
     * Render the main list template
     *
     * @param unknown $currentOrder, if any, null otherwise.
     */
    protected function renderListTemplate($currentOrder)
    {
        $this->getListOrderFromSession('content', 'content_order', 'manual');

        return $this->render('folders',
            array(
                'content_order' => $currentOrder,
                'parent' => $this->getFolderId()
            ));
    }

    protected function getEditionArguments()
    {
        return array(
            'content_id' => $this->getRequest()->get('content_id', 0),
            'current_tab' => $this->getRequest()->get('current_tab', 'general')
        );
    }

    /**
     * Render the edition template
     */
    protected function renderEditionTemplate()
    {
        return $this->render('content-edit', $this->getEditionArguments());
    }

    /**
     * Redirect to the edition template
     */
    protected function redirectToEditionTemplate()
    {
        $this->redirect($this->getRoute('admin.content.update', $this->getEditionArguments()));
    }

    /**
     * Redirect to the list template
     */
    protected function redirectToListTemplate()
    {
        $this->redirectToRoute(
            'admin.content.default',
            array('parent' => $this->getFolderId())
        );
    }

    /**
     * @param  \Thelia\Core\Event\Content\ContentUpdateEvent $updateEvent
     * @return Response|void
     */
    protected function performAdditionalUpdateAction($updateEvent)
    {
        if ($this->getRequest()->get('save_mode') != 'stay') {

            // Redirect to parent category list
            $this->redirectToRoute(
                'admin.folders.default',
                array('parent' => $this->getFolderId())
            );
        }
    }

    /**
     * Put in this method post object delete processing if required.
     *
     * @param  \Thelia\Core\Event\Content\ContentDeleteEvent $deleteEvent the delete event
     * @return Response                                      a response, or null to continue normal processing
     */
    protected function performAdditionalDeleteAction($deleteEvent)
    {
        // Redirect to parent category list
        $this->redirectToRoute(
            'admin.folders.default',
            array('parent' => $deleteEvent->getDefaultFolderId())
        );
    }

    /**
     * @param $event \Thelia\Core\Event\UpdatePositionEvent
     * @return null|Response
     */
    protected function performAdditionalUpdatePositionAction($event)
    {

        if (null !== $content = ContentQuery::create()->findPk($event->getObjectId())) {
            // Redirect to parent category list
            $this->redirectToRoute(
                'admin.folders.default',
                array('parent' => $content->getDefaultFolderId())
            );
        }

        return null;
    }

    /**
     * @param $positionChangeMode
     * @param $positionValue
     * @return UpdatePositionEvent|void
     */
    protected function createUpdatePositionEvent($positionChangeMode, $positionValue)
    {
        return new UpdatePositionEvent(
            $this->getRequest()->get('content_id', null),
            $positionChangeMode,
            $positionValue
        );
    }

    /**
     * @return ContentToggleVisibilityEvent|void
     */
    protected function createToggleVisibilityEvent()
    {
        return new ContentToggleVisibilityEvent($this->getExistingObject());
    }
}
