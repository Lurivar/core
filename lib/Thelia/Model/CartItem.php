<?php

namespace Thelia\Model;

use Propel\Runtime\Connection\ConnectionInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Model\Base\CartItem as BaseCartItem;
use Thelia\Model\ConfigQuery;
use Thelia\Core\Event\CartEvent;
use Thelia\TaxEngine\Calculator;

class CartItem extends BaseCartItem
{
    protected $dispatcher;

    public function setDisptacher(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function postInsert(ConnectionInterface $con = null)
    {
        if ($this->dispatcher) {
            $cartEvent = new CartEvent($this->getCart());

            $this->dispatcher->dispatch(TheliaEvents::AFTER_CARTADDITEM, $cartEvent);
        }
    }

    public function postUpdate(ConnectionInterface $con = null)
    {
        if ($this->dispatcher) {
            $cartEvent = new CartEvent($this->getCart());

            $this->dispatcher->dispatch(TheliaEvents::AFTER_CARTUPDATEITEM, $cartEvent);
        }
    }


    /**
     * @param $value
     * @return $this
     */
    public function updateQuantity($value)
    {
        $currentQuantity = $this->getQuantity();

        if($value <= 0)
        {
            $value = $currentQuantity;
        }

        if(ConfigQuery::read("verifyStock", 1) == 1)
        {
            $productSaleElements = $this->getProductSaleElements();

            if($productSaleElements->getQuantity() < $value) {
                $value = $currentQuantity;
            }
        }

        $this->setQuantity($value);

        return $this;
    }

    public function getTaxedPrice(Country $country)
    {
        $taxCalculator = new Calculator();
        return round($taxCalculator->load($this->getProduct(), $country)->getTaxedPrice($this->getPrice()), 2);
    }

    public function getTaxedPromoPrice(Country $country)
    {
        $taxCalculator = new Calculator();
        return round($taxCalculator->load($this->getProduct(), $country)->getTaxedPrice($this->getPromoPrice()), 2);
    }
}
