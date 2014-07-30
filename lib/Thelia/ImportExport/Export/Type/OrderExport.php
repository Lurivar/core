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

namespace Thelia\ImportExport\Export\Type;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\Join;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Thelia\Core\FileFormat\FormatType;
use Thelia\Core\Template\Element\BaseLoop;
use Thelia\ImportExport\Export\ExportHandler;
use Thelia\Model\Lang;
use Thelia\Model\Map\CountryI18nTableMap;
use Thelia\Model\Map\CurrencyTableMap;
use Thelia\Model\Map\CustomerTableMap;
use Thelia\Model\Map\CustomerTitleI18nTableMap;
use Thelia\Model\Map\ModuleTableMap;
use Thelia\Model\Map\OrderAddressTableMap;
use Thelia\Model\Map\OrderCouponTableMap;
use Thelia\Model\Map\OrderProductTableMap;
use Thelia\Model\Map\OrderProductTaxTableMap;
use Thelia\Model\Map\OrderStatusI18nTableMap;
use Thelia\Model\Map\OrderTableMap;
use Thelia\Model\OrderQuery;

/**
 * Class OrderExport
 * @package Thelia\ImportExport\Export\Type
 * @author Benjamin Perche <bperche@openstudio.fr>
 */
class OrderExport extends ExportHandler
{
    /**
     * @return string|array
     *
     * Define all the type of formatters that this can handle
     * return a string if it handle a single type ( specific exports ),
     * or an array if multiple.
     *
     * Thelia types are defined in \Thelia\Core\FileFormat\FormatType
     *
     * example:
     * return array(
     *     FormatType::TABLE,
     *     FormatType::UNBOUNDED,
     * );
     */
    public function getHandledTypes()
    {
        return array(
            FormatType::TABLE,
            FormatType::UNBOUNDED,
        );
    }

    /**
     * @param  Lang $lang
     * @return ModelCriteria|array|BaseLoop
     */
    public function buildDataSet(Lang $lang)
    {
        $locale = $lang->getLocale();

        $query = OrderQuery::create()
            ->useCurrencyQuery()
                ->addAsColumn("currency_CODE", CurrencyTableMap::CODE)
            ->endUse()
            ->useCustomerQuery()
                ->addAsColumn("customer_REF", CustomerTableMap::REF)
            ->endUse()
            ->useOrderProductQuery()
                ->useOrderProductTaxQuery(null, Criteria::LEFT_JOIN)
                    ->addAsColumn(
                        "product_TAX",
                        "IF(".OrderProductTableMap::WAS_IN_PROMO.",".
                            "SUM(".OrderProductTaxTableMap::PROMO_AMOUNT."),".
                            "SUM(".OrderProductTaxTableMap::AMOUNT.")".
                        ")"
                    )
                    ->addAsColumn("tax_TITLE", OrderProductTaxTableMap::TITLE)
                ->endUse()
                ->addAsColumn("product_TITLE", OrderProductTableMap::TITLE)
                ->addAsColumn(
                    "product_PRICE",
                    "IF(".OrderProductTableMap::WAS_IN_PROMO.",".
                        OrderProductTableMap::PROMO_PRICE .",".
                        OrderProductTableMap::PRICE .")"
                    )
                ->addAsColumn("product_QUANTITY", OrderProductTableMap::QUANTITY)
                ->addAsColumn("product_WAS_IN_PROMO", OrderProductTableMap::WAS_IN_PROMO)
                ->groupById()
            ->endUse()
            ->orderById()
            ->groupById()
            ->useOrderCouponQuery(null, Criteria::LEFT_JOIN)
                ->addAsColumn("coupon_COUPONS", "GROUP_CONCAT(".OrderCouponTableMap::TITLE.")")
                ->groupBy(OrderCouponTableMap::ORDER_ID)
            ->endUse()
            ->useModuleRelatedByPaymentModuleIdQuery("payment_module")
                ->addAsColumn("payment_module_TITLE", "`payment_module`.CODE")
            ->endUse()
            ->useModuleRelatedByDeliveryModuleIdQuery("delivery_module")
                ->addAsColumn("delivery_module_TITLE", "`delivery_module`.CODE")
            ->endUse()
            ->useOrderAddressRelatedByDeliveryOrderAddressIdQuery("delivery_address_join")
                ->useCustomerTitleQuery("delivery_address_customer_title_join")
                    ->useCustomerTitleI18nQuery("delivery_address_customer_title_i18n_join")
                        ->addAsColumn("delivery_address_TITLE", "`delivery_address_customer_title_i18n_join`.SHORT")
                    ->endUse()
                ->endUse()
                ->useCountryQuery("delivery_address_country_join")
                    ->useCountryI18nQuery("delivery_address_country_i18n_join")
                        ->addAsColumn("delivery_address_country_TITLE", "`delivery_address_country_i18n_join`.TITLE")
                    ->endUse()
                ->endUse()
                ->addAsColumn("delivery_address_COMPANY", "`delivery_address_join`.COMPANY")
                ->addAsColumn("delivery_address_FIRSTNAME", "`delivery_address_join`.FIRSTNAME")
                ->addAsColumn("delivery_address_LASTNAME", "`delivery_address_join`.LASTNAME")
                ->addAsColumn("delivery_address_ADDRESS1", "`delivery_address_join`.ADDRESS1")
                ->addAsColumn("delivery_address_ADDRESS2", "`delivery_address_join`.ADDRESS2")
                ->addAsColumn("delivery_address_ADDRESS3", "`delivery_address_join`.ADDRESS3")
                ->addAsColumn("delivery_address_ZIPCODE", "`delivery_address_join`.ZIPCODE")
                ->addAsColumn("delivery_address_CITY", "`delivery_address_join`.CITY")
                ->addAsColumn("delivery_address_PHONE", "`delivery_address_join`.PHONE")
            ->endUse()
            ->useOrderAddressRelatedByInvoiceOrderAddressIdQuery("invoice_address_join")
                ->useCustomerTitleQuery("invoice_address_customer_title_join")
                    ->useCustomerTitleI18nQuery("invoice_address_customer_title_i18n_join")
                        ->addAsColumn("invoice_address_TITLE", "`invoice_address_customer_title_i18n_join`.SHORT")
                    ->endUse()
                ->endUse()
                ->useCountryQuery("invoice_address_country_join")
                    ->useCountryI18nQuery("invoice_address_country_i18n_join")
                        ->addAsColumn("invoice_address_country_TITLE", "`invoice_address_country_i18n_join`.TITLE")
                    ->endUse()
                ->endUse()
                ->addAsColumn("invoice_address_COMPANY", "`invoice_address_join`.COMPANY")
                ->addAsColumn("invoice_address_FIRSTNAME", "`invoice_address_join`.FIRSTNAME")
                ->addAsColumn("invoice_address_LASTNAME", "`invoice_address_join`.LASTNAME")
                ->addAsColumn("invoice_address_ADDRESS1", "`invoice_address_join`.ADDRESS1")
                ->addAsColumn("invoice_address_ADDRESS2", "`invoice_address_join`.ADDRESS2")
                ->addAsColumn("invoice_address_ADDRESS3", "`invoice_address_join`.ADDRESS3")
                ->addAsColumn("invoice_address_ZIPCODE", "`invoice_address_join`.ZIPCODE")
                ->addAsColumn("invoice_address_CITY", "`invoice_address_join`.CITY")
                ->addAsColumn("invoice_address_PHONE", "`invoice_address_join`.PHONE")
            ->endUse()
            ->useOrderStatusQuery()
                ->useOrderStatusI18nQuery()
                    ->addAsColumn("order_status_TITLE", OrderStatusI18nTableMap::TITLE)
                ->endUse()
            ->endUse()
            ->select([
                OrderTableMap::REF,
                "customer_REF",
                "product_TITLE",
                "product_PRICE",
                "product_TAX",
                "tax_TITLE",
                // PRODUCT_TTC_PRICE
                "product_QUANTITY",
                "product_WAS_IN_PROMO",
                // ORDER_TOTAL_TTC
                OrderTableMap::DISCOUNT,
                "coupon_COUPONS",
                // TOTAL_WITH_DISCOUNT
                OrderTableMap::POSTAGE,
                // total ttc +postage
                "payment_module_TITLE",
                OrderTableMap::INVOICE_REF,
                "delivery_module_TITLE",
                "delivery_address_TITLE",
                "delivery_address_COMPANY",
                "delivery_address_FIRSTNAME",
                "delivery_address_LASTNAME",
                "delivery_address_ADDRESS1",
                "delivery_address_ADDRESS2",
                "delivery_address_ADDRESS3",
                "delivery_address_ZIPCODE",
                "delivery_address_CITY",
                "delivery_address_country_TITLE",
                "delivery_address_PHONE",
                "invoice_address_TITLE",
                "invoice_address_COMPANY",
                "invoice_address_FIRSTNAME",
                "invoice_address_LASTNAME",
                "invoice_address_ADDRESS1",
                "invoice_address_ADDRESS2",
                "invoice_address_ADDRESS3",
                "invoice_address_ZIPCODE",
                "invoice_address_CITY",
                "invoice_address_country_TITLE",
                "invoice_address_PHONE",
                "order_status_TITLE",
                "currency_CODE"
            ])
        ;

        $this->addI18nCondition(
            $query,
            CustomerTitleI18nTableMap::TABLE_NAME,
            "`delivery_address_customer_title_join`.ID",
            CustomerTitleI18nTableMap::ID,
            "`delivery_address_customer_title_i18n_join`.LOCALE",
            $locale
        );

        $this->addI18nCondition(
            $query,
            CustomerTitleI18nTableMap::TABLE_NAME,
            "`invoice_address_customer_title_join`.ID",
            CustomerTitleI18nTableMap::ID,
            "`invoice_address_customer_title_i18n_join`.LOCALE",
            $locale
        );

        $this->addI18nCondition(
            $query,
            CountryI18nTableMap::TABLE_NAME,
            "`delivery_address_country_join`.ID",
            CountryI18nTableMap::ID,
            "`delivery_address_country_i18n_join`.LOCALE",
            $locale
        );

        $this->addI18nCondition(
            $query,
            CountryI18nTableMap::TABLE_NAME,
            "`invoice_address_country_join`.ID",
            CountryI18nTableMap::ID,
            "`invoice_address_country_i18n_join`.LOCALE",
            $locale
        );

        $dataSet = $query
            ->find()
            ->toArray()
        ;

        $orders = OrderQuery::create()
            ->orderById()
            ->find()
        ;

        $current = 0;
        $previous = null;

        foreach ($dataSet as &$line) {
            /**
             * Add computed columns
             */
            $line["order_TOTAL_TTC"] = "";
            $line["order_TOTAL_WITH_DISCOUNT"] = "";
            $line["order_TOTAL_WITH_DISCOUNT_AND_POSTAGE"] = "";


            if (null === $previous || $previous !== $line[OrderTableMap::REF]) {
                $previous = $line[OrderTableMap::REF];

                /** @var \Thelia\Model\Order $order */
                $order = $orders->get($current);

                $line["order_TOTAL_TTC"] = $order->getTotalAmount($tax, false, false);
                $line["order_TOTAL_WITH_DISCOUNT"] = $order->getTotalAmount($tax, false, true);
                $line["order_TOTAL_WITH_DISCOUNT_AND_POSTAGE"] = $order->getTotalAmount($tax, true, true);

                $current++;
            } else {
                /**
                 * Remove all the information of the order
                 * for each line that only contains a product.
                 */
                $line["customer_REF"]  = "";
                $line[OrderTableMap::DISCOUNT]  = "";
                $line["coupon_COUPONS"]  = "";
                $line[OrderTableMap::POSTAGE]  = "";
                $line["payment_module_TITLE"]  = "";
                $line[OrderTableMap::INVOICE_REF]  = "";
                $line["delivery_module_TITLE"]  = "";
                $line["delivery_address_TITLE"]  = "";
                $line["delivery_address_COMPANY"]  = "";
                $line["delivery_address_FIRSTNAME"]  = "";
                $line["delivery_address_LASTNAME"]  = "";
                $line["delivery_address_ADDRESS1"]  = "";
                $line["delivery_address_ADDRESS2"]  = "";
                $line["delivery_address_ADDRESS3"]  = "";
                $line["delivery_address_ZIPCODE"]  = "";
                $line["delivery_address_CITY"]  = "";
                $line["delivery_address_country_TITLE"]  = "";
                $line["delivery_address_PHONE"]  = "";
                $line["invoice_address_TITLE"]  = "";
                $line["invoice_address_COMPANY"]  = "";
                $line["invoice_address_FIRSTNAME"]  = "";
                $line["invoice_address_LASTNAME"]  = "";
                $line["invoice_address_ADDRESS1"]  = "";
                $line["invoice_address_ADDRESS2"]  = "";
                $line["invoice_address_ADDRESS3"]  = "";
                $line["invoice_address_ZIPCODE"]  = "";
                $line["invoice_address_CITY"]  = "";
                $line["invoice_address_country_TITLE"]  = "";
                $line["invoice_address_PHONE"]  = "";
                $line["order_status_TITLE"]  = "";
            }

            $line["product_TAXED_PRICE"] = $line["product_PRICE"] + $line["product_TAX"];
        }

        return $dataSet;
    }

} 