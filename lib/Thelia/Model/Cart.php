<?php

namespace Thelia\Model;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Thelia\Model\Base\Cart as BaseCart;
use Thelia\Model\ProductSaleElementsQuery;
use Thelia\Model\ProductPriceQuery;

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
            if ($product && $productSaleElements && $product->getVisible() == 1 && $productSaleElements->getQuantity() > $cartItem->getQuantity()) {

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
}
