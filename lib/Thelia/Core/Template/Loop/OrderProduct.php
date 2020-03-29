<?php
/*************************************************************************************/
/*      This file is part of the Thelia package.                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : dev@thelia.net                                                       */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE.txt  */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

namespace Thelia\Core\Template\Loop;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\Join;
use Thelia\Core\Template\Element\BaseLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Element\PropelSearchLoopInterface;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Model\ConfigQuery;
use Thelia\Model\Map\OrderProductTableMap;
use Thelia\Model\Map\ProductSaleElementsTableMap;
use Thelia\Model\OrderProductQuery;
use Thelia\Type\BooleanOrBothType;

/**
 *
 * OrderProduct loop
 *
 * Class OrderProduct
 * @package Thelia\Core\Template\Loop
 * @author Etienne Roudeix <eroudeix@openstudio.fr>
 *
 * {@inheritdoc}
 * @method int getOrder()
 * @method int[] getId()
 * @method bool|string getVirtual()
 */
class OrderProduct extends BaseLoop implements PropelSearchLoopInterface
{
    protected $timestampable = true;

    /**
     * @return ArgumentCollection
     */
    protected function getArgDefinitions()
    {
        return new ArgumentCollection(
            Argument::createIntTypeArgument('order', null, true),
            Argument::createIntListTypeArgument('id'),
            Argument::createBooleanOrBothTypeArgument('virtual', BooleanOrBothType::ANY)
        );
    }

    public function buildModelCriteria()
    {
        $search = OrderProductQuery::create();

        $search->joinOrderProductTax('opt', Criteria::LEFT_JOIN)
            ->withColumn('SUM(`opt`.AMOUNT)', 'TOTAL_TAX')
            ->withColumn('SUM(`opt`.PROMO_AMOUNT)', 'TOTAL_PROMO_TAX')
            ->groupById();


        // new join to get the product id if it exists
        $pseJoin = new Join(
            OrderProductTableMap::COL_PRODUCT_SALE_ELEMENTS_ID,
            ProductSaleElementsTableMap::COL_ID,
            Criteria::LEFT_JOIN
        );
        $search
            ->addJoinObject($pseJoin)
            ->addAsColumn(
                'product_id',
                ProductSaleElementsTableMap::COL_PRODUCT_ID
            )
        ;

        $order = $this->getOrder();

        $search->filterByOrderId($order, Criteria::EQUAL);

        $virtual = $this->getVirtual();
        if ($virtual !== BooleanOrBothType::ANY) {
            if ($virtual) {
                $search
                    ->filterByVirtual(1, Criteria::EQUAL)
                    ->filterByVirtualDocument(null, Criteria::NOT_EQUAL);
            } else {
                $search
                    ->filterByVirtual(0);
            }
        }

        if (null !== $this->getId()) {
            $search->filterById($this->getId(), Criteria::IN);
        }

        $search->orderById(Criteria::ASC);

        return $search;
    }

    /**
     * @param LoopResult $loopResult
     * @return LoopResult
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function parseResults(LoopResult $loopResult)
    {
        $lastLegacyOrderId = ConfigQuery::read('last_legacy_order_id', 0);

        /** @var \Thelia\Model\OrderProduct $orderProduct */
        foreach ($loopResult->getResultDataCollection() as $orderProduct) {
            $loopResultRow = new LoopResultRow($orderProduct);

            $tax = $orderProduct->getVirtualColumn('TOTAL_TAX');
            $promoTax = $orderProduct->getVirtualColumn('TOTAL_PROMO_TAX');

            $totalTax = round($tax * $orderProduct->getQuantity(), 2);
            $totalPromoTax = round($promoTax * $orderProduct->getQuantity(), 2);

            // To prevent price changes in pre-2.4 orders, use the legacy calculation method
            if ($orderProduct->getOrderId() <= $lastLegacyOrderId) {
                $taxedPrice = $orderProduct->getPrice() + $orderProduct->getVirtualColumn('TOTAL_TAX');
                $taxedPromoPrice = $orderProduct->getPromoPrice() + $orderProduct->getVirtualColumn('TOTAL_PROMO_TAX');

                $totalPrice = $orderProduct->getPrice()*$orderProduct->getQuantity();
                $totalPromoPrice = $orderProduct->getPromoPrice()*$orderProduct->getQuantity();
            } else {
                $taxedPrice = $orderProduct->getPrice() + $tax;
                $taxedPromoPrice = $orderProduct->getPromoPrice() + $promoTax;

                // Price calculation should use the same rounding method as in CartItem::getTotalTaxedPromoPrice()
                // For each order line, we first round the taxed price, then we multiply by the quantity.
                $totalPrice = round($orderProduct->getPrice() * $orderProduct->getQuantity(), 2);
                $totalPromoPrice = round($orderProduct->getPromoPrice() * $orderProduct->getQuantity(), 2);
            }

            $totalTaxedPrice = round($taxedPrice * $orderProduct->getQuantity(), 2);
            $totalTaxedPromoPrice = round($taxedPromoPrice * $orderProduct->getQuantity(), 2);

            $loopResultRow->set('ID', $orderProduct->getId())
                ->set('REF', $orderProduct->getProductRef())
                ->set('PRODUCT_ID', $orderProduct->getVirtualColumn('product_id'))
                ->set('PRODUCT_SALE_ELEMENTS_ID', $orderProduct->getProductSaleElementsId())
                ->set('PRODUCT_SALE_ELEMENTS_REF', $orderProduct->getProductSaleElementsRef())
                ->set('WAS_NEW', $orderProduct->getWasNew() === 1 ? 1 : 0)
                ->set('WAS_IN_PROMO', $orderProduct->getWasInPromo() === 1 ? 1 : 0)
                ->set('WEIGHT', $orderProduct->getWeight())
                ->set('TITLE', $orderProduct->getTitle())
                ->set('CHAPO', $orderProduct->getChapo())
                ->set('DESCRIPTION', $orderProduct->getDescription())
                ->set('POSTSCRIPTUM', $orderProduct->getPostscriptum())
                ->set('VIRTUAL', $orderProduct->getVirtual())
                ->set('VIRTUAL_DOCUMENT', $orderProduct->getVirtualDocument())
                ->set('QUANTITY', $orderProduct->getQuantity())

                ->set('PRICE', $orderProduct->getPrice())
                ->set('PRICE_TAX', $tax)
                ->set('TAXED_PRICE', $taxedPrice)
                ->set('PROMO_PRICE', $orderProduct->getPromoPrice())
                ->set('PROMO_PRICE_TAX', $promoTax)
                ->set('TAXED_PROMO_PRICE', $taxedPromoPrice)
                ->set('TOTAL_PRICE', $totalPrice)
                ->set('TOTAL_TAXED_PRICE', $totalTaxedPrice)
                ->set('TOTAL_PROMO_PRICE', $totalPromoPrice)
                ->set('TOTAL_TAXED_PROMO_PRICE', $totalTaxedPromoPrice)

                ->set('TAX_RULE_TITLE', $orderProduct->getTaxRuleTitle())
                ->set('TAX_RULE_DESCRIPTION', $orderProduct->getTaxRuledescription())
                ->set('PARENT', $orderProduct->getParent())
                ->set('EAN_CODE', $orderProduct->getEanCode())
                ->set('CART_ITEM_ID', $orderProduct->getCartItemId())

                ->set('REAL_PRICE', $orderProduct->getWasInPromo() ? $orderProduct->getPromoPrice() : $orderProduct->getPrice())
                ->set('REAL_TAXED_PRICE', $orderProduct->getWasInPromo() ? $taxedPromoPrice : $taxedPrice)
                ->set('REAL_PRICE_TAX', $orderProduct->getWasInPromo() ? $promoTax : $tax)

                ->set('REAL_TOTAL_PRICE', $orderProduct->getWasInPromo() ? $totalPromoPrice : $totalPrice)
                ->set('REAL_TOTAL_TAXED_PRICE', $orderProduct->getWasInPromo() ? $totalTaxedPromoPrice : $totalTaxedPrice)
                ->set('REAL_TOTAL_PRICE_TAX', $orderProduct->getWasInPromo() ? $totalPromoTax : $totalTax)

            ;
            $this->addOutputFields($loopResultRow, $orderProduct);

            $loopResult->addRow($loopResultRow);
        }

        return $loopResult;
    }
}
