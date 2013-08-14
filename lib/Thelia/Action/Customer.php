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
use Thelia\Core\Event\TheliaEvents;
use Thelia\Form\CustomerCreation;
use Thelia\Form\CustomerModification;
use Thelia\Model\Customer as CustomerModel;
use Thelia\Log\Tlog;
use Thelia\Model\CustomerQuery;
use Thelia\Form\CustomerLogin;
use Thelia\Core\Security\Authentication\CustomerUsernamePasswordFormAuthenticator;
use Symfony\Component\Validator\Exception\ValidatorException;
use Thelia\Core\Security\Exception\AuthenticationException;
use Thelia\Core\Security\Exception\UsernameNotFoundException;
use Propel\Runtime\Exception\PropelException;
use Thelia\Action\Exception\FormValidationException;

class Customer extends BaseAction implements EventSubscriberInterface
{

    public function create(ActionEvent $event)
    {
        $request = $event->getRequest();

        try {
              $customerCreationForm = new CustomerCreation($request);

            $form = $this->validateForm($customerCreationForm, "POST");

            $data = $form->getData();
              $customer = new CustomerModel();
            $customer->setDispatcher($event->getDispatcher());

            $customer->createOrUpdate(
                $data["title"],
                $data["firstname"],
                $data["lastname"],
                $data["address1"],
                $data["address2"],
                $data["address3"],
                $data["phone"],
                $data["cellphone"],
                $data["zipcode"],
                $data["country"],
                $data["email"],
                $data["password"],
                $request->getSession()->getLang()
            );

            $event->customer = $customer;

        } catch (PropelException $e) {

            Tlog::getInstance()->error(sprintf('error during creating customer on action/createCustomer with message "%s"', $e->getMessage()));

            $message = "Failed to create your account, please try again.";
        } catch (FormValidationException $e) {

           $message = $e->getMessage();
        }

        // The form has errors, propagate it.
        $this->propagateFormError($customerCreationForm, $message, $event);
    }

    public function modify(ActionEvent $event)
    {
        $request = $event->getRequest();

        try {
              $customerModification = new CustomerModification($request);

            $form = $this->validateForm($customerModification, "POST");

            $data = $form->getData();

            $customer = CustomerQuery::create()->findPk(1);

            $data = $form->getData();

            $customer->createOrUpdate(
                        $data["title"],
                        $data["firstname"],
                        $data["lastname"],
                        $data["address1"],
                        $data["address2"],
                        $data["address3"],
                        $data["phone"],
                        $data["cellphone"],
                        $data["zipcode"],
                        $data["country"]
              );

            // Update the logged-in user, and redirect to the success URL (exits)
            // We don-t send the login event, as the customer si already logged.
            $this->processSuccessfullLogin($event, $customer, $customerModification);
        } catch (PropelException $e) {

              Tlog::getInstance()->error(sprintf('error during modifying customer on action/modifyCustomer with message "%s"', $e->getMessage()));

            $message = "Failed to change your account, please try again.";
        } catch (FormValidationException $e) {

           $message = $e->getMessage();
        }

        // The form has errors, propagate it.
        $this->propagateFormError($customerModification, $message, $event);
    }

    /**
     * Perform user logout. The user is redirected to the provided view, if any.
     *
     * @param ActionEvent $event
     */
    public function logout(ActionEvent $event)
    {
        $event->getDispatcher()->dispatch(TheliaEvents::CUSTOMER_LOGOUT, $event);

          $this->getFrontSecurityContext()->clear();
    }

    /**
     * Perform user login. On a successful login, the user is redirected to the URL
     * found in the success_url form parameter, or / if none was found.
     *
     * If login is not successfull, the same view is dispolyed again.
     *
     * @param ActionEvent $event
     */
    public function login(ActionEvent $event)
    {
        $request = $event->getRequest();

          $customerLoginForm = new CustomerLogin($request);

          $authenticator = new CustomerUsernamePasswordFormAuthenticator($request, $customerLoginForm);

          try {
            $user = $authenticator->getAuthentifiedUser();

              $event->customer = $customer;

          } catch (ValidatorException $ex) {
            $message = "Missing or invalid information. Please check your input.";
          } catch (UsernameNotFoundException $ex) {
            $message = "This email address was not found.";
          } catch (AuthenticationException $ex) {
            $message = "Login failed. Please check your username and password.";
          } catch (\Exception $ex) {
            $message = sprintf("Unable to process your request. Please try again (%s in %s).", $ex->getMessage(), $ex->getFile());
          }

          // The for has an error
          $customerLoginForm->setError(true);
          $customerLoginForm->setErrorMessage($message);

          // Dispatch the errored form
          $event->setErrorForm($customerLoginForm);

      // A this point, the same view is displayed again.
    }

    public function changePassword(ActionEvent $event)
    {
    // TODO
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
            "action.createCustomer" => array("create", 128),
            "action.modifyCustomer" => array("modify", 128),
            "action.loginCustomer"  => array("login", 128),
            "action.logoutCustomer" => array("logout", 128),
        );
    }
}
