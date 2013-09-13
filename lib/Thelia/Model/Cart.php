<?php

namespace Thelia\Model;

use Propel\Runtime\ActiveQuery\Criteria;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Thelia\Model\Base\Cart as BaseCart;
use Thelia\Model\ProductSaleElementsQuery;
use Thelia\Model\ProductPriceQuery;
use Thelia\Model\CartItemQuery;

class Cart extends BaseCart
{
    public function duplicate($token, Customer $customer = null)
    {
        $cartItems = $this->getCartItems();

        $cart = new Cart();
        $cart->setAddressDeliveryId($this->getAddressDeliveryId());
        $cart->setAddressInvoiceId($this->getAddressInvoiceId());
        $cart->setToken($token);
        // TODO : set current Currency
        $cart->setCurrencyId($this->getCurrencyId());

        if ($customer){
            $cart->setCustomer($customer);
        }

        $cart->save();
        $currentDateTime = new \DateTime();
        foreach ($cartItems as $cartItem){

            $product = $cartItem->getProduct();
            $productSaleElements = $cartItem->getProductSaleElements();
            if ($product &&
                $productSaleElements &&
                $product->getVisible() == 1 &&
                ($productSaleElements->getQuantity() > $cartItem->getQuantity() || ! ConfigQuery::read("verifyStock", 1)))
            {

                $item = new CartItem();
                $item->setCart($cart);
                $item->setProductId($cartItem->getProductId());
                $item->setQuantity($cartItem->getQuantity());
                $item->setProductSaleElements($productSaleElements);
                if ($currentDateTime <= $cartItem->getPriceEndOfLife()) {
                    $item->setPrice($cartItem->getPrice());
                    $item->setPromoPrice($cartItem->getPromoPrice());
                    // TODO : new price EOF or duplicate current priceEOF from $cartItem ?
                    $item->setPriceEndOfLife($cartItem->getPriceEndOfLife());
                } else {
                    $productPrices = ProductPriceQuery::create()->filterByProductSaleElements($productSaleElements)->findOne();

                    $item->setPrice($productPrices->getPrice());
                    $item->setPromoPrice($productPrices->getPromoPrice());
                    $item->setPriceEndOfLife(time() + ConfigQuery::read("cart.priceEOF", 60*60*24*30));
                }
                $item->save();
            }

        }

        return $cart;
    }

    public function getLastCartItemAdded()
    {
        $items = CartItemQuery::create()
            ->filterByCartId($this->getId())
            ->orderByCreatedAt(Criteria::DESC)
            ->findOne()
        ;
    }

    public function getTaxedAmount()
    {

    }

    public function getTotalAmount()
    {
        $total = 0;

        foreach($this->getCartItems() as $cartItem) {
            $total += $cartItem->getPrice()-$cartItem->getDiscount();
        }

        $total -= $this->getDiscount();

        return $total;
    }
}
