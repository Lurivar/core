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
namespace Thelia\Cart;

use Thelia\Model\ProductPrice;
use Thelia\Model\ProductPriceQuery;
use Thelia\Model\CartItem;
use Thelia\Model\CartItemQuery;
use Thelia\Model\CartQuery;
use Thelia\Model\Cart as CartModel;
use Thelia\Model\ConfigQuery;
use Thelia\Model\Customer;
use Symfony\Component\HttpFoundation\Request;
use Thelia\Core\HttpFoundation\Session\Session;
use Thelia\Core\Event\CartEvent;
use Thelia\Core\Event\TheliaEvents;

trait CartTrait {
    /**
     *
     * search if cart already exists in session. If not try to create a new one or duplicate an old one.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Thelia\Model\Cart
     */
    public function getCart(Request $request)
    {

        if(null !== $cart = $request->getSession()->getCart()){
            return $cart;
        }

        if ($request->cookies->has("thelia_cart")) {
            //le cookie de panier existe, on le récupère
            $token = $request->cookies->get("thelia_cart");

            $cart = CartQuery::create()->findOneByToken($token);

            if ($cart) {
                //le panier existe en base
                $customer = $request->getSession()->getCustomerUser();

                if ($customer) {
                    if($cart->getCustomerId() != $customer->getId()) {
                        //le customer du panier n'est pas le mm que celui connecté, il faut cloner le panier sans le customer_id
                        $cart = $this->duplicateCart($cart, $request->getSession(), $customer);
                    }
                } else {
                    if ($cart->getCustomerId() != null) {
                        //il faut dupliquer le panier sans le customer_id
                        $cart = $this->duplicateCart($cart, $request->getSession());
                    }
                }

            } else {
                $cart = $this->createCart($request->getSession());
            }
        } else {
            //le cookie de panier n'existe pas, il va falloir le créer et faire un enregistrement en base.
            $cart = $this->createCart($request->getSession());
        }

        return $cart;
    }

    /**
     * @param \Thelia\Core\HttpFoundation\Session\Session $session
     * @return \Thelia\Model\Cart
     */
    protected function createCart(Session $session)
    {
        $cart = new CartModel();
        $cart->setToken($this->generateCookie());

        if(null !== $customer = $session->getCustomerUser()) {
            $cart->setCustomer($customer);
        }

        $cart->save();

        $session->setCart($cart->getId());

        return $cart;
    }


    /**
     * try to duplicate existing Cart. Customer is here to determine if this cart belong to him.
     *
     * @param \Thelia\Model\Cart $cart
     * @param \Thelia\Core\HttpFoundation\Session\Session $session
     * @param \Thelia\Model\Customer $customer
     * @return \Thelia\Model\Cart
     */
    protected function duplicateCart(CartModel $cart, Session $session, Customer $customer = null)
    {
        $newCart = $cart->duplicate($this->generateCookie(), $customer);
        $session->setCart($newCart->getId());

        $cartEvent = new CartEvent($newCart);
        $this->getDispatcher()->dispatch(TheliaEvents::CART_DUPLICATE, $cartEvent);

        return $cartEvent->cart;
    }

    protected function generateCookie()
    {
        $id = null;
        if (ConfigQuery::read("cart.session_only", 0) == 0) {
            $id = uniqid('', true);
            setcookie("thelia_cart", $id, time()+ConfigQuery::read("cart.cookie_lifetime", 60*60*24*365));

        }

        return $id;

    }
}