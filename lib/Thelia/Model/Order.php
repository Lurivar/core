<?php

namespace Thelia\Model;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Connection\ConnectionInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Thelia\Core\Event\Order\OrderEvent;
use Thelia\Core\Event\Payment\ManageStockOnCreationEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Exception\TheliaProcessException;
use Thelia\Model\Base\Order as BaseOrder;
use Thelia\Model\Map\OrderProductTableMap;
use Thelia\Model\Map\OrderProductTaxTableMap;
use Thelia\Model\Tools\ModelEventDispatcherTrait;

class Order extends BaseOrder
{
    use ModelEventDispatcherTrait;

    /** @var int|null  */
    protected $choosenDeliveryAddress = null;
    /** @var int|null  */
    protected $choosenInvoiceAddress = null;

    protected $disableVersioning = false;

    /**
     * @param int $choosenDeliveryAddress the choosen delivery address ID
     * @return $this
     */
    public function setChoosenDeliveryAddress($choosenDeliveryAddress)
    {
        $this->choosenDeliveryAddress = $choosenDeliveryAddress;

        return $this;
    }

    /**
     * @param boolean $disableVersioning
     * @return $this
     */
    public function setDisableVersioning($disableVersioning)
    {
        $this->disableVersioning = (bool) $disableVersioning;

        return $this;
    }

    public function isVersioningDisable()
    {
        return $this->disableVersioning;
    }

    public function isVersioningNecessary($con = null)
    {
        if ($this->isVersioningDisable()) {
            return false;
        } else {
            return parent::isVersioningNecessary($con);
        }
    }

    /**
     * @return int|null the choosen delivery address ID
     */
    public function getChoosenDeliveryAddress()
    {
        return $this->choosenDeliveryAddress;
    }

    /**
     * @param int  $choosenInvoiceAddress the choosen invoice address
     * @return $this
     */
    public function setChoosenInvoiceAddress($choosenInvoiceAddress)
    {
        $this->choosenInvoiceAddress = $choosenInvoiceAddress;

        return $this;
    }

    /**
     * @return int|null the choosen invoice address ID
     */
    public function getChoosenInvoiceAddress()
    {
        return $this->choosenInvoiceAddress;
    }

    /**
     * {@inheritDoc}
     */
    public function preSave(ConnectionInterface $con = null)
    {
        if ($this->isPaid(false) && null === $this->getInvoiceDate()) {
            $this
                ->setInvoiceDate(time());
        }

        return parent::preSave($con);
    }

    /**
     * {@inheritDoc}
     */
    public function preInsert(ConnectionInterface $con = null)
    {
        $this->dispatchEvent(TheliaEvents::ORDER_BEFORE_CREATE, new OrderEvent($this));

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function postInsert(ConnectionInterface $con = null)
    {
        $this->setRef($this->generateRef())
            ->setDisableVersioning(true)
            ->save($con);
        $this->dispatchEvent(TheliaEvents::ORDER_AFTER_CREATE, new OrderEvent($this));
    }

    public function generateRef()
    {
        return sprintf('ORD%s', str_pad($this->getId(), 12, 0, STR_PAD_LEFT));
    }

    /**
     * Compute this order amount.
     *
     * The order amount is only available once the order is persisted in database.
     * During invoice process, use all cart methods instead of order methods (the order doest not exists at this moment)
     *
     * @param  float|int $tax             (output only) returns the tax amount for this order
     * @param  bool      $includePostage  if true, the postage cost is included to the total
     * @param  bool      $includeDiscount if true, the discount will be included to the total
     * @return float
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function getTotalAmount(&$tax = 0, $includePostage = true, $includeDiscount = true)
    {
        $orderInfo = OrderProductQuery::create()
            ->filterByOrderId($this->getId())
            ->leftJoinOrderProductTax()
            ->withColumn('SUM(
                ' . OrderProductTableMap::COL_QUANTITY . '
                * IF('.OrderProductTableMap::COL_WAS_IN_PROMO.' = 1, '.OrderProductTaxTableMap::COL_PROMO_AMOUNT.', '.OrderProductTaxTableMap::COL_AMOUNT.')
            )', 'total_tax')
            ->withColumn('SUM(
                ' . OrderProductTableMap::COL_QUANTITY . '
                * IF('.OrderProductTableMap::COL_WAS_IN_PROMO.' = 1, '.OrderProductTableMap::COL_PROMO_PRICE.', '.OrderProductTableMap::COL_PRICE.')
            )', 'total_amount')
            ->select([ 'total_tax', 'total_amount' ])
            ->findOne();

        $tax = $orderInfo['total_tax'];
        $amount = $orderInfo['total_amount'];

        $total = $amount + $tax;

        // @todo : manage discount : free postage ?
        if (true === $includeDiscount) {
            $total -= $this->getDiscount();

            if ($total<0) {
                $total = 0;
            } else {
                $total = round($total, 2);
            }
        }

        if (false !== $includePostage) {
            $total += $this->getPostage();
            $tax += $this->getPostageTax();
        }

        return $total;
    }

    /**
     * Compute this order weight.
     *
     * The order weight is only available once the order is persisted in database.
     * During invoice process, use all cart methods instead of order methods (the order doest not exists at this moment)
     *
     * @return float
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function getWeight()
    {
        $weight = 0;

        /* browse all products */
        foreach ($this->getOrderProducts() as $orderProduct) {
            $weight += $orderProduct->getQuantity() * (double)$orderProduct->getWeight();
        }

        return $weight;
    }

    /**
     * Return the postage without tax
     * @return float|int
     */
    public function getUntaxedPostage()
    {
        if (0 < $this->getPostageTax()) {
            $untaxedPostage =  round($this->getPostage() - $this->getPostageTax(), 2);
        } else {
            $untaxedPostage = $this->getPostage();
        }

        return $untaxedPostage;
    }

    /**
     * Check if the current order contains at less 1 virtual product with a file to download
     *
     * @return bool true if this order have at less 1 file to download, false otherwise.
     */
    public function hasVirtualProduct()
    {
        $virtualProductCount = OrderProductQuery::create()
            ->filterByOrderId($this->getId())
            ->filterByVirtual(1, Criteria::EQUAL)
            ->count()
        ;

        return ($virtualProductCount !== 0);
    }

    /**
     * Set the status of the current order to NOT PAID
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function setNotPaid()
    {
        $this->setStatusHelper(OrderStatus::CODE_NOT_PAID);
    }

    /**
     * Check if the current status of this order is NOT PAID
     *
     * @param bool $exact if true, the status should be the exact required status, not a derived one.
     * @return bool true if this order is NOT PAID, false otherwise.
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function isNotPaid($exact = true)
    {
        return $this->getOrderStatus()->isNotPaid($exact);
    }

    /**
     * Set the status of the current order to PAID
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function setPaid()
    {
        $this->setStatusHelper(OrderStatus::CODE_PAID);
    }

    /**
     * Check if the current status of this order is PAID
     *
     * @param bool $exact if true, the status should be the exact required status, not a derived one.
     * @return bool true if this order is PAID, false otherwise.
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function isPaid($exact = true)
    {
        return $this->getOrderStatus()->isPaid($exact);
    }

    /**
     * Set the status of the current order to PROCESSING
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function setProcessing()
    {
        $this->setStatusHelper(OrderStatus::CODE_PROCESSING);
    }

    /**
     * Check if the current status of this order is PROCESSING
     *
     * @param bool $exact if true, the status should be the exact required status, not a derived one.
     * @return bool true if this order is PROCESSING, false otherwise.
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function isProcessing($exact = true)
    {
        return $this->getOrderStatus()->isProcessing($exact);
    }

    /**
     * Set the status of the current order to SENT
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function setSent()
    {
        $this->setStatusHelper(OrderStatus::CODE_SENT);
    }

    /**
     * Check if the current status of this order is SENT
     *
     * @param bool $exact if true, the status should be the exact required status, not a derived one.
     * @return bool true if this order is SENT, false otherwise.
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function isSent($exact = true)
    {
        return $this->getOrderStatus()->isSent($exact);
    }

    /**
     * Set the status of the current order to CANCELED
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function setCancelled()
    {
        $this->setStatusHelper(OrderStatus::CODE_CANCELED);
    }

    /**
     * Check if the current status of this order is CANCELED
     *
     * @param bool $exact if true, the status should be the exact required status, not a derived one.
     * @return bool true if this order is CANCELED, false otherwise.
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function isCancelled($exact = true)
    {
        return $this->getOrderStatus()->isCancelled($exact);
    }

    /**
     * Set the status of the current order to REFUNDED
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function setRefunded()
    {
        $this->setStatusHelper(OrderStatus::CODE_REFUNDED);
    }

    /**
     * Check if the current status of this order is REFUNDED
     *
     * @param bool $exact if true, the status should be the exact required status, not a derived one.
     * @return bool true if this order is REFUNDED, false otherwise.
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function isRefunded($exact = true)
    {
        return $this->getOrderStatus()->isRefunded($exact);
    }

    /**
     * Set the status of the current order to the provided status
     *
     * @param string $statusCode the status code, one of OrderStatus::CODE_xxx constants.
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function setStatusHelper($statusCode)
    {
        if (null !== $ordeStatus = OrderStatusQuery::create()->findOneByCode($statusCode)) {
            $this->setOrderStatus($ordeStatus)->save();
        }
    }

    /**
     * Get an instance of the payment module
     *
     * @return \Thelia\Module\PaymentModuleInterface
     * @throws TheliaProcessException
     */
    public function getPaymentModuleInstance()
    {
        if (null === $paymentModule = ModuleQuery::create()->findPk($this->getPaymentModuleId())) {
            throw new TheliaProcessException("Payment module ID=" . $this->getPaymentModuleId() . " was not found.");
        }

        return $paymentModule->createInstance();
    }

    /**
     * Get an instance of the delivery module
     *
     * @return \Thelia\Module\DeliveryModuleInterface
     * @throws TheliaProcessException
     */
    public function getDeliveryModuleInstance()
    {
        if (null === $deliveryModule = ModuleQuery::create()->findPk($this->getDeliveryModuleId())) {
            throw new TheliaProcessException("Delivery module ID=" . $this->getDeliveryModuleId() . " was not found.");
        }

        return $deliveryModule->createInstance();
    }

    /**
     * Check if stock was decreased at stock creation for this order.
     * TODO : we definitely have to store modules in an order_modules table juste like order_product and other order related information.
     *
     * @param EventDispatcherInterface $dispatcher
     * @return bool true if the stock was decreased at order creation, false otherwise
     */
    public function isStockManagedOnOrderCreation(EventDispatcherInterface $dispatcher)
    {
        $paymentModule = $this->getPaymentModuleInstance();

        $event = new ManageStockOnCreationEvent($paymentModule);

        $dispatcher->dispatch(
            TheliaEvents::getModuleEvent(
                TheliaEvents::MODULE_PAYMENT_MANAGE_STOCK,
                $paymentModule->getCode()
            )
        );

        return (null !== $event->getManageStock())
            ? $event->getManageStock()
            : $paymentModule->manageStockOnCreation();
    }
}
