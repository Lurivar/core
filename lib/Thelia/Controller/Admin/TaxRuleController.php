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

use Thelia\Core\Event\Tax\TaxRuleEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Form\TaxRuleCreationForm;
use Thelia\Form\TaxRuleModificationForm;
use Thelia\Form\TaxRuleTaxListUpdateForm;
use Thelia\Model\CountryQuery;
use Thelia\Model\TaxRuleQuery;

class TaxRuleController extends AbstractCrudController
{
    public function __construct()
    {
        parent::__construct(
            'taxrule',
            'manual',
            'order',

            'admin.configuration.taxrule.view',
            'admin.configuration.taxrule.create',
            'admin.configuration.taxrule.update',
            'admin.configuration.taxrule.delete',

            TheliaEvents::TAX_RULE_CREATE,
            TheliaEvents::TAX_RULE_UPDATE,
            TheliaEvents::TAX_RULE_DELETE
        );
    }

    protected function getCreationForm()
    {
        return new TaxRuleCreationForm($this->getRequest());
    }

    protected function getUpdateForm()
    {
        return new TaxRuleModificationForm($this->getRequest());
    }

    protected function getCreationEvent($formData)
    {
        $event = new TaxRuleEvent();

        $event->setLocale($formData['locale']);
        $event->setTitle($formData['title']);
        $event->setDescription($formData['description']);

        return $event;
    }

    protected function getUpdateEvent($formData)
    {
        $event = new TaxRuleEvent();

        $event->setLocale($formData['locale']);
        $event->setId($formData['id']);
        $event->setTitle($formData['title']);
        $event->setDescription($formData['description']);

        return $event;
    }

    protected function getUpdateTaxListEvent($formData)
    {
        $event = new TaxRuleEvent();

        $event->setId($formData['id']);
        $event->setTaxList($formData['tax_list']);
        $event->setCountryList($formData['country_list']);

        return $event;
    }

    protected function getDeleteEvent()
    {
        $event = new TaxRuleEvent();

        $event->setId(
            $this->getRequest()->get('tax_rule_id', 0)
        );

        return $event;
    }

    protected function eventContainsObject($event)
    {
        return $event->hasTaxRule();
    }

    protected function hydrateObjectForm($object)
    {
        $data = array(
            'id'           => $object->getId(),
            'locale'       => $object->getLocale(),
            'title'        => $object->getTitle(),
            'description'  => $object->getDescription(),
        );

        // Setup the object form
        return new TaxRuleModificationForm($this->getRequest(), "form", $data);
    }

    protected function hydrateTaxUpdateForm($object)
    {
        $data = array(
            'id'           => $object->getId(),
        );

        // Setup the object form
        return new TaxRuleTaxListUpdateForm($this->getRequest(), "form", $data);
    }

    protected function getObjectFromEvent($event)
    {
        return $event->hasTaxRule() ? $event->getTaxRule() : null;
    }

    protected function getExistingObject()
    {
        return TaxRuleQuery::create()
            ->joinWithI18n($this->getCurrentEditionLocale())
            ->findOneById($this->getRequest()->get('tax_rule_id'));
    }

    protected function getObjectLabel($object)
    {
        return $object->getTitle();
    }

    protected function getObjectId($object)
    {
        return $object->getId();
    }

    protected function getViewArguments($country = null, $tab = null)
    {
        return array(
            'tab' => $tab === null ? $this->getRequest()->get('tab', 'data') : $tab,
            'country' => $country === null ? $this->getRequest()->get('country', CountryQuery::create()->findOneByByDefault(1)->getId()) : $country,
        );
    }

    protected function getRouteArguments($tax_rule_id = null)
    {
        return array(
            'tax_rule_id' => $tax_rule_id === null ? $this->getRequest()->get('tax_rule_id') : $tax_rule_id,
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
        return $this->render('tax-rule-edit', array_merge($this->getViewArguments(), $this->getRouteArguments()));
    }

    protected function redirectToEditionTemplate($request = null, $country = null)
    {
        // We always return to the feature edition form
        $this->redirectToRoute(
            "admin.configuration.taxes-rules.update",
            $this->getViewArguments($country),
            $this->getRouteArguments()
        );
    }

    /**
     * Put in this method post object creation processing if required.
     *
     * @param  TaxRuleEvent $createEvent the create event
     * @return Response     a response, or null to continue normal processing
     */
    protected function performAdditionalCreateAction($createEvent)
    {
        $this->redirectToRoute(
            "admin.configuration.taxes-rules.update",
            $this->getViewArguments(),
            $this->getRouteArguments($createEvent->getTaxRule()->getId())
        );
    }

    protected function redirectToListTemplate()
    {
        $this->redirectToRoute(
            "admin.configuration.taxes-rules.list"
        );
    }

    public function updateAction()
    {
        if (null !== $response = $this->checkAuth($this->updatePermissionIdentifier)) return $response;

        $object = $this->getExistingObject();

        if ($object != null) {

            // Hydrate the form abd pass it to the parser
            $changeTaxesForm = $this->hydrateTaxUpdateForm($object);

            // Pass it to the parser
            $this->getParserContext()->addForm($changeTaxesForm);
        }

        return parent::updateAction();
    }

    public function setDefaultAction()
    {
        if (null !== $response = $this->checkAuth($this->updatePermissionIdentifier)) return $response;

        $setDefaultEvent = new TaxRuleEvent();

        $taxRuleId = $this->getRequest()->attributes->get('tax_rule_id');

        $setDefaultEvent->setId(
            $taxRuleId
        );

        $this->dispatch(TheliaEvents::TAX_RULE_SET_DEFAULT, $setDefaultEvent);

        $this->redirectToListTemplate();
    }

    public function processUpdateTaxesAction()
    {
        // Check current user authorization
        if (null !== $response = $this->checkAuth('admin.configuration.taxrule.update')) return $response;

        $error_msg = false;

        // Create the form from the request
        $changeForm = new TaxRuleTaxListUpdateForm($this->getRequest());

        try {
            // Check the form against constraints violations
            $form = $this->validateForm($changeForm, "POST");

            // Get the form field values
            $data = $form->getData();

            $changeEvent = $this->getUpdateTaxListEvent($data);

            $this->dispatch(TheliaEvents::TAX_RULE_TAXES_UPDATE, $changeEvent);

            if (! $this->eventContainsObject($changeEvent))
                throw new \LogicException(
                    $this->getTranslator()->trans("No %obj was updated.", array('%obj', $this->objectName)));

            // Log object modification
            if (null !== $changedObject = $this->getObjectFromEvent($changeEvent)) {
                $this->adminLogAppend(sprintf("%s %s (ID %s) modified", ucfirst($this->objectName), $this->getObjectLabel($changedObject), $this->getObjectId($changedObject)));
            }

            if ($response == null) {
                $this->redirectToEditionTemplate($this->getRequest(), isset($data['country_list'][0]) ? $data['country_list'][0] : null);
            } else {
                return $response;
            }
        } catch (FormValidationException $ex) {
            // Form cannot be validated
            $error_msg = $this->createStandardFormValidationErrorMessage($ex);
        } catch (\Exception $ex) {
            // Any other error
            $error_msg = $ex->getMessage();
        }

        $this->setupFormErrorContext($this->getTranslator()->trans("%obj modification", array('%obj' => 'taxrule')), $error_msg, $changeForm, $ex);

        // At this point, the form has errors, and should be redisplayed.
        return $this->renderEditionTemplate();
    }
}
