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

use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Constraints\NotBlank;
use Thelia\Core\Translation\Translator;

class AdminProfileCreationForm extends BaseForm
{
    protected function buildForm()
    {
        $this->formBuilder
            ->add("wording"   , "text"  , array(
                "constraints" => array(
                    new NotBlank()
                ),
                "label" => Translator::getInstance()->trans("Wording *"),
                "label_attr" => array(
                    "for" => "wording"
                ))
            )
            ->add("name"   , "text"  , array(
                "constraints" => array(
                    new NotBlank()
                ),
                "label" => Translator::getInstance()->trans("Name *"),
                "label_attr" => array(
                    "for" => "name"
                ))
            )
            ->add("description"   , "text"  , array(
                "label" => Translator::getInstance()->trans("Description"),
                "label_attr" => array(
                    "for" => "description"
                ))
            )
        ;
    }

    public function getName()
    {
        return "thelia_admin_profile_creation";
    }
}