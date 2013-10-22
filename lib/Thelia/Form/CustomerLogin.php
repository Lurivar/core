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
namespace Thelia\Form;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ExecutionContextInterface;
use Thelia\Core\Translation\Translator;
use Thelia\Model\Base\CustomerQuery;

/**
 * Class CustomerLogin
 * @package Thelia\Form
 * @author  Manuel Raynaud <mraynaud@openstudio.fr>
 */
class CustomerLogin extends BaseForm
{
    protected function buildForm()
    {
        $this->formBuilder
            ->add("email", "email", array(
                "constraints" => array(
                    new Constraints\NotBlank(),
                    new Constraints\Email(),
                    new Constraints\Callback(array(
                        "methods" => array(
                            array($this, "verifyExistingEmail")
                        )
                    ))
                ),
                "label" => Translator::getInstance()->trans("Please enter your email address"),
                "label_attr" => array(
                    "for" => "email"
                )
            ))
            ->add("account", "choice", array(
                "constraints" => array(
                    new Constraints\Callback(array(
                        "methods" => array(
                            array($this, "verifyAccount")
                        )
                    ))
                ),
                "choices" => array(
                    0 => Translator::getInstance()->trans("No, I am a new customer."),
                    1 => Translator::getInstance()->trans("Yes, I have a password :")
                ),
                "label_attr" => array(
                    "for" => "account"
                ),
                "data" => 0
            ))
            ->add("password", "password", array(
                "constraints" => array(
                    new Constraints\NotBlank(array(
                        'groups' => array('existing_customer'),
                    ))
                ),
                "label" => Translator::getInstance()->trans("Please enter your password"),
                "label_attr" => array(
                    "for" => "password"
                ),
                "required"    => false
            ));
    }

    /**
     * If the user select "Yes, I have a password", we check the password.
     */
    public function verifyAccount($value, ExecutionContextInterface $context)
    {
        if ($value == 1) {
            $data = $context->getRoot()->getData();
            if (false === $data['password'] || (empty($data['password']) && '0' != $data['password'])) {

                $context->getViolations()->add(new ConstraintViolation(
                    'This value should not be blank.',
                    'account_password',
                    array(),
                    $context->getRoot(),
                    'children[password].data',
                    'propertyPath'
                ));

            }
        }
    }

    /**
     * If the user select "I'am a new customer", we make sure is email address does not exit in the database.
     */
    public function verifyExistingEmail($value, ExecutionContextInterface $context)
    {
        $data = $context->getRoot()->getData();
        if ($data["account"] == 0) {
            $customer = CustomerQuery::create()->findOneByEmail($value);
            if ($customer) {
                $context->addViolation("A user already exists with this email address. Please login or if you've forgotten your password, go to Reset Your Password.");
            }
        }
    }

    public function getName()
    {
        return "thelia_customer_login";
    }

}
