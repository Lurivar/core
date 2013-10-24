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

use Symfony\Component\Form\Form;
use Thelia\Core\Event\Lang\LangCreateEvent;
use Thelia\Core\Event\Lang\LangDefaultBehaviorEvent;
use Thelia\Core\Event\Lang\LangDeleteEvent;
use Thelia\Core\Event\Lang\LangToggleDefaultEvent;
use Thelia\Core\Event\Lang\LangUpdateEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Form\Exception\FormValidationException;
use Thelia\Form\Lang\LangCreateForm;
use Thelia\Form\Lang\LangDefaultBehaviorForm;
use Thelia\Form\Lang\LangUpdateForm;
use Thelia\Form\Lang\LangUrlEvent;
use Thelia\Form\Lang\LangUrlForm;
use Thelia\Model\ConfigQuery;
use Thelia\Model\LangQuery;


/**
 * Class LangController
 * @package Thelia\Controller\Admin
 * @author Manuel Raynaud <mraynaud@openstudio.fr>
 */
class LangController extends BaseAdminController
{

    public function defaultAction()
    {
        if (null !== $response = $this->checkAuth(AdminResources::LANGUAGE, AccessManager::VIEW)) return $response;

        return $this->renderDefault();
    }

    public function renderDefault(array $param = array())
    {
        $data = array();
        foreach (LangQuery::create()->find() as $lang) {
            $data[LangUrlForm::LANG_PREFIX.$lang->getId()] = $lang->getUrl();
        }
        $langUrlForm = new LangUrlForm($this->getRequest(), 'form', $data);
        $this->getParserContext()->addForm($langUrlForm);

        return $this->render('languages', array_merge($param, array(
            'lang_without_translation' => ConfigQuery::getDefaultLangWhenNoTranslationAvailable(),
            'one_domain_per_lang' => ConfigQuery::read("one_domain_foreach_lang", false)
        )));
    }

    public function updateAction($lang_id)
    {
        if (null !== $response = $this->checkAuth(AdminResources::LANGUAGE, AccessManager::UPDATE)) return $response;

        $this->checkXmlHttpRequest();

        $lang = LangQuery::create()->findPk($lang_id);

        $langForm = new LangUpdateForm($this->getRequest(), 'form', array(
            'id' => $lang->getId(),
            'title' => $lang->getTitle(),
            'code' => $lang->getCode(),
            'locale' => $lang->getLocale(),
            'date_format' => $lang->getDateFormat(),
            'time_format' => $lang->getTimeFormat()
        ));

        $this->getParserContext()->addForm($langForm);

        return $this->render('ajax/language-update-modal', array(
            'lang_id' => $lang_id
        ));
    }

    public function processUpdateAction($lang_id)
    {
        if (null !== $response = $this->checkAuth(AdminResources::LANGUAGE, AccessManager::UPDATE)) return $response;

        $error_msg = false;

        $langForm = new LangUpdateForm($this->getRequest());

        try {
            $form = $this->validateForm($langForm);

            $event = new LangUpdateEvent($form->get('id')->getData());
            $event = $this->hydrateEvent($event, $form);

            $this->dispatch(TheliaEvents::LANG_UPDATE, $event);

            if (false === $event->hasLang()) {
                throw new \LogicException(
                    $this->getTranslator()->trans("No %obj was updated.", array('%obj', 'Lang')));
            }

            $changedObject = $event->getLang();
            $this->adminLogAppend(sprintf("%s %s (ID %s) modified", 'Lang', $changedObject->getTitle(), $changedObject->getId()));
            $this->redirectToRoute('/admin/configuration/languages');
        } catch(\Exception $e) {
            $error_msg = $e->getMessage();
        }

        return $this->renderDefault();
    }

    protected function hydrateEvent($event,Form $form)
    {
        return $event
            ->setTitle($form->get('title')->getData())
            ->setCode($form->get('code')->getData())
            ->setLocale($form->get('locale')->getData())
            ->setDateFormat($form->get('date_format')->getData())
            ->setTimeFormat($form->get('time_format')->getData())
        ;
    }

    public function toggleDefaultAction($lang_id)
    {
        if (null !== $response = $this->checkAuth(AdminResources::LANGUAGE, AccessManager::UPDATE)) return $response;

        $this->checkXmlHttpRequest();
        $error = false;
        try {
            $event = new LangToggleDefaultEvent($lang_id);

            $this->dispatch(TheliaEvents::LANG_TOGGLEDEFAULT, $event);

            if (false === $event->hasLang()) {
                throw new \LogicException(
                    $this->getTranslator()->trans("No %obj was updated.", array('%obj', 'Lang')));
            }

            $changedObject = $event->getLang();
            $this->adminLogAppend(sprintf("%s %s (ID %s) modified", 'Lang', $changedObject->getTitle(), $changedObject->getId()));

        } catch (\Exception $e) {
            \Thelia\Log\Tlog::getInstance()->error(sprintf("Error on changing default languages with message : %s", $e->getMessage()));
            $error = $e->getMessage();
        }

        if($error) {
            return $this->nullResponse(500);
        } else {
            return $this->nullResponse();
        }
    }

    public function addAction()
    {
        if (null !== $response = $this->checkAuth(AdminResources::LANGUAGE, AccessManager::CREATE)) return $response;

        $createForm = new LangCreateForm($this->getRequest());

        $error_msg = false;

        try {
            $form = $this->validateForm($createForm);

            $createEvent = new LangCreateEvent();
            $createEvent = $this->hydrateEvent($createEvent, $form);

            $this->dispatch(TheliaEvents::LANG_CREATE, $createEvent);

            if (false === $createEvent->hasLang()) {
                throw new \LogicException(
                    $this->getTranslator()->trans("No %obj was updated.", array('%obj', 'Lang')));
            }

            $createdObject = $createEvent->getLang();
            $this->adminLogAppend(sprintf("%s %s (ID %s) created", 'Lang', $createdObject->getTitle(), $createdObject->getId()));

            $this->redirectToRoute('admin.configuration.languages');

        } catch (FormValidationException $ex) {
            // Form cannot be validated
            $error_msg = $this->createStandardFormValidationErrorMessage($ex);
        } catch (\Exception $ex) {
            // Any other error
            $error_msg = $ex->getMessage();
        }

        $this->setupFormErrorContext(
            $this->getTranslator()->trans("%obj creation", array('%obj' => 'Lang')), $error_msg, $createForm, $ex);

        // At this point, the form has error, and should be redisplayed.
        return $this->renderDefault();

    }

    public function deleteAction()
    {
        if (null !== $response = $this->checkAuth(AdminResources::LANGUAGE, AccessManager::DELETE)) return $response;

        $error_msg = false;

        try {

            $deleteEvent = new LangDeleteEvent($this->getRequest()->get('language_id', 0));

            $this->dispatch(TheliaEvents::LANG_DELETE, $deleteEvent);

            $this->redirectToRoute('admin.configuration.languages');
        } catch (\Exception $ex) {
            \Thelia\Log\Tlog::getInstance()->error(sprintf("error during language removal with message : %s", $ex->getMessage()));
            $error_msg = $ex->getMessage();
        }

        return $this->renderDefault(array(
           'error_delete_message' => $error_msg
        ));

    }

    public function defaultBehaviorAction()
    {
        if (null !== $response = $this->checkAuth(AdminResources::LANGUAGE, AccessManager::UPDATE)) return $response;

        $error_msg = false;

        $behaviorForm = new LangDefaultBehaviorForm($this->getRequest());

        try {
            $form = $this->validateForm($behaviorForm);

            $event = new LangDefaultBehaviorEvent($form->get('behavior')->getData());

            $this->dispatch(TheliaEvents::LANG_DEFAULTBEHAVIOR, $event);

            $this->redirectToRoute('admin.configuration.languages');

        } catch (FormValidationException $ex) {
            // Form cannot be validated
            $error_msg = $this->createStandardFormValidationErrorMessage($ex);
        } catch (\Exception $ex) {
            // Any other error
            $error_msg = $ex->getMessage();
        }

        $this->setupFormErrorContext(
            $this->getTranslator()->trans("%obj creation", array('%obj' => 'Lang')), $error_msg, $behaviorForm, $ex);

        // At this point, the form has error, and should be redisplayed.
        return $this->renderDefault();
    }

    public function domainAction()
    {
        if (null !== $response = $this->checkAuth(AdminResources::LANGUAGE, AccessManager::UPDATE)) return $response;

        $error_msg = false;
        $langUrlForm = new LangUrlForm($this->getRequest());

        try {
            $form = $this->validateForm($langUrlForm);

            $data = $form->getData();
            $event = new LangUrlEvent();
            foreach ($data as $key => $value) {
                $pos= strpos($key, LangUrlForm::LANG_PREFIX);
                if(false !== strpos($key, LangUrlForm::LANG_PREFIX)) {
                    $event->addUrl(substr($key,strlen(LangUrlForm::LANG_PREFIX)), $value);
                }
            }

            $this->dispatch(TheliaEvents::LANG_URL, $event);

            $this->redirectToRoute('admin.configuration.languages');
        } catch (FormValidationException $ex) {
            // Form cannot be validated
            $error_msg = $this->createStandardFormValidationErrorMessage($ex);
        } catch (\Exception $ex) {
            // Any other error
            $error_msg = $ex->getMessage();
        }

        $this->setupFormErrorContext(
            $this->getTranslator()->trans("%obj creation", array('%obj' => 'Lang')), $error_msg, $langUrlForm, $ex);

        // At this point, the form has error, and should be redisplayed.
        return $this->renderDefault();
    }

    public function activateDomainAction()
    {
        $this->domainActivation(1);
    }

    public function deactivateDomainAction()
    {
        $this->domainActivation(0);
    }

    private function domainActivation($activate)
    {
        if (null !== $response = $this->checkAuth(AdminResources::LANGUAGE, AccessManager::UPDATE)) return $response;

        $error_msg = false;

        ConfigQuery::create()
            ->filterByName('one_domain_foreach_lang')
            ->update(array('Value' => $activate));

        $this->redirectToRoute('admin.configuration.languages');
    }
}