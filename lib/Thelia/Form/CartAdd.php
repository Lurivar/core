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
use Symfony\Component\Validator\ExecutionContextInterface;
use Thelia\Form\Exception\StockNotFoundException;
use Thelia\Form\Exception\ProductNotFoundException;
use Thelia\Model\ProductSaleElementsQuery;
use Thelia\Model\ConfigQuery;
use Thelia\Model\ProductQuery;

class CartAdd extends BaseForm
{

    /**
     *
     * in this function you add all the fields you need for your Form.
     * Form this you have to call add method on $this->formBuilder attribute :
     *
     * $this->formBuilder->add("name", "text")
     *   ->add("email", "email", array(
     *           "attr" => array(
     *               "class" => "field"
     *           ),
     *           "label" => "email",
     *           "constraints" => array(
     *               new \Symfony\Component\Validator\Constraints\NotBlank()
     *           )
     *       )
     *   )
     *   ->add('age', 'integer');
     *
     * @return null
     */
    protected function buildForm()
    {
        $this->formBuilder
            ->add("product", "text", array(
                "constraints" => array(
                    new Constraints\NotBlank(),
                    new Constraints\Callback(array("methods" => array(
                            array($this, "checkProduct")
                    )))
                )
            ))
            ->add("product_sale_elements_id", "text", array(
                "constraints" => array(
                    new Constraints\Callback(array("methods" => array(
                            array($this, "checkStockAvailability")
                    )))
                )

            ))
            ->add("quantity", "text", array(
                "constraints" => array(
                    new Constraints\NotBlank(),
                    new Constraints\Callback(array("methods" => array(
                            array($this, "checkStock")
                    ))),
                    new Constraints\GreaterThanOrEqual(array(
                        "value" => 0
                    ))
                )
            ))
            ->add("append", "hidden")
            ->add("newness", "hidden")
        ;
    }

    public function checkProduct($value, ExecutionContextInterface $context)
    {
        $product = ProductQuery::create()->findPk($value);

        if (is_null($product) || $product->getVisible() == 0) {
            throw new ProductNotFoundException(sprintf("this product id does not exists : %d", $value));
        }
    }

    public function checkStockAvailability($value, ExecutionContextInterface $context)
    {
        if ($value) {
            $data = $context->getRoot()->getData();

            $productSaleElements = ProductSaleElementsQuery::create()
                ->filterById($value)
                ->filterByProductId($data["product"])
                ->count();

            if ($productSaleElements == 0) {
                throw new StockNotFoundException(sprintf("This product_sale_elements_id does not exists for this product : %d", $value));
            }
        }
    }

    public function checkStock($value, ExecutionContextInterface $context)
    {
        $data = $context->getRoot()->getData();

        $productSaleElements = ProductSaleElementsQuery::create()
            ->filterById($data["product_sale_elements_id"])
            ->filterByProductId($data["product"])
            ->findOne();

        if ($productSaleElements->getQuantity() < $value && ConfigQuery::read("verifyStock", 1) == 1) {
            $context->addViolation("quantity value is not valid");
        }
    }

    /**
     * @return string the name of you form. This name must be unique
     */
    public function getName()
    {
        return "thelia_cart_add";
    }
}
