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

namespace Thelia\Core\Template\Loop;

use Propel\Runtime\ActiveQuery\Criteria;
use Thelia\Core\Template\Element\BaseLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;

use Thelia\Core\Template\Element\PropelSearchLoopInterface;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use Thelia\Core\Template\Loop\Argument\Argument;

use Thelia\Exception\TaxEngineException;
use Thelia\Model\Base\ProductSaleElementsQuery;
use Thelia\Model\CurrencyQuery;
use Thelia\Model\Map\ProductSaleElementsTableMap;
use Thelia\TaxEngine\TaxEngine;
use Thelia\Type\TypeCollection;
use Thelia\Type;

/**
 *
 * Product Sale Elements loop
 *
 * @todo : manage attribute_availability ?
 *
 * Class ProductSaleElements
 * @package Thelia\Core\Template\Loop
 * @author Etienne Roudeix <eroudeix@openstudio.fr>
 */
class ProductSaleElements extends BaseLoop implements PropelSearchLoopInterface
{
    protected $timestampable = true;

    /**
     * @return ArgumentCollection
     */
    protected function getArgDefinitions()
    {
        return new ArgumentCollection(
            Argument::createIntTypeArgument('currency'),
            Argument::createIntTypeArgument('product', null, true),
            new Argument(
                'attribute_availability',
                new TypeCollection(
                    new Type\IntToCombinedIntsListType()
                )
            ),
            new Argument(
                'order',
                new TypeCollection(
                    new Type\EnumListType(array('min_price', 'max_price', 'promo', 'new', 'random'))
                ),
                'random'
            )
        );
    }

    public function buildModelCriteria()
    {
        $search = ProductSaleElementsQuery::create();

        $product = $this->getProduct();

        $search->filterByProductId($product, Criteria::EQUAL);

        $orders  = $this->getOrder();

        foreach ($orders as $order) {
            switch ($order) {
                case "min_price":
                    $search->addAscendingOrderByColumn('price_FINAL_PRICE', Criteria::ASC);
                    break;
                case "max_price":
                    $search->addDescendingOrderByColumn('price_FINAL_PRICE');
                    break;
                case "promo":
                    $search->orderByPromo(Criteria::DESC);
                    break;
                case "new":
                    $search->orderByNewness(Criteria::DESC);
                    break;
                case "random":
                    $search->clearOrderByColumns();
                    $search->addAscendingOrderByColumn('RAND()');
                    break(2);
            }
        }

        $currencyId = $this->getCurrency();
        if (null !== $currencyId) {
            $currency = CurrencyQuery::create()->findPk($currencyId);
            if (null === $currency) {
                throw new \InvalidArgumentException('Cannot found currency id: `' . $currency . '` in product_sale_elements loop');
            }
        } else {
            $currency = $this->request->getSession()->getCurrency();
        }

        $defaultCurrency = CurrencyQuery::create()->findOneByByDefault(1);
        $defaultCurrencySuffix = '_default_currency';

        $search->joinProductPrice('price', Criteria::LEFT_JOIN)
            ->addJoinCondition('price', '`price`.`currency_id` = ?', $currency->getId(), null, \PDO::PARAM_INT);

        $search->joinProductPrice('price' . $defaultCurrencySuffix, Criteria::LEFT_JOIN)
            ->addJoinCondition('price_default_currency', '`price' . $defaultCurrencySuffix . '`.`currency_id` = ?', $defaultCurrency->getId(), null, \PDO::PARAM_INT);

        /**
         * rate value is checked as a float in overloaded getRate method.
         */
        $priceSelectorAsSQL = 'ROUND(CASE WHEN ISNULL(`price`.PRICE) OR `price`.FROM_DEFAULT_CURRENCY = 1 THEN `price_default_currency`.PRICE * ' . $currency->getRate() . ' ELSE `price`.PRICE END, 2)';
        $promoPriceSelectorAsSQL = 'ROUND(CASE WHEN ISNULL(`price`.PRICE) OR `price`.FROM_DEFAULT_CURRENCY = 1 THEN `price_default_currency`.PROMO_PRICE  * ' . $currency->getRate() . ' ELSE `price`.PROMO_PRICE END, 2)';
        $search->withColumn($priceSelectorAsSQL, 'price_PRICE')
            ->withColumn($promoPriceSelectorAsSQL, 'price_PROMO_PRICE')
            ->withColumn('CASE WHEN ' . ProductSaleElementsTableMap::PROMO . ' = 1 THEN ' . $promoPriceSelectorAsSQL . ' ELSE ' . $priceSelectorAsSQL . ' END', 'price_FINAL_PRICE');

        $search->groupById();

        return $search;

    }

    public function parseResults(LoopResult $loopResult)
    {
        $taxCountry = $this->container->get('thelia.taxEngine')->getDeliveryCountry();

        foreach ($loopResult->getResultDataCollection() as $PSEValue) {
            $loopResultRow = new LoopResultRow($PSEValue);

            $price = $PSEValue->getPrice();
            try {
                $taxedPrice = $PSEValue->getTaxedPrice(
                    $taxCountry
                );
            } catch (TaxEngineException $e) {
                $taxedPrice = null;
            }
            $promoPrice = $PSEValue->getPromoPrice();
            try {
                $taxedPromoPrice = $PSEValue->getTaxedPromoPrice(
                    $taxCountry
                );
            } catch (TaxEngineException $e) {
                $taxedPromoPrice = null;
            }

            $loopResultRow
                ->set("ID"                , $PSEValue->getId())
                ->set("QUANTITY"          , $PSEValue->getQuantity())
                ->set("IS_PROMO"          , $PSEValue->getPromo() === 1 ? 1 : 0)
                ->set("IS_NEW"            , $PSEValue->getNewness() === 1 ? 1 : 0)
                ->set("IS_DEFAULT"        , $PSEValue->getIsDefault() === 1 ? 1 : 0)
                ->set("WEIGHT"            , $PSEValue->getWeight())
                ->set("REF"               , $PSEValue->getRef())
                ->set("EAN_CODE"          , $PSEValue->getEanCode())
                ->set("PRICE"             , $price)
                ->set("PRICE_TAX"         , $taxedPrice - $price)
                ->set("TAXED_PRICE"       , $taxedPrice)
                ->set("PROMO_PRICE"       , $promoPrice)
                ->set("PROMO_PRICE_TAX"   , $taxedPromoPrice - $promoPrice)
                ->set("TAXED_PROMO_PRICE" , $taxedPromoPrice);

            $loopResult->addRow($loopResultRow);
        }

        return $loopResult;

    }
}
