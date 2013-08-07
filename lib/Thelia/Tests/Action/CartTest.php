<?php
/*************************************************************************************/
/*                                                                                   */
/*      Thelia	                                                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*	    email : info@thelia.net                                                      */
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
namespace Thelia\Tests\Action;

use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Thelia\Core\Event\DefaultActionEvent;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Core\HttpFoundation\Session\Session;
use Thelia\Model\Cart;
use Thelia\Model\Customer;
use Thelia\Model\ProductQuery;

class CartTest extends \PHPUnit_Framework_TestCase
{

    protected $session;

    protected $request;

    protected $actionCart;

    protected $uniqid;



    public function setUp()
    {
        $this->session = new Session(new MockArraySessionStorage());
        $this->request = new Request();

        $this->request->setSession($this->session);

        $this->uniqid = uniqid('', true);

        $dispatcher = $this->getMock("Symfony\Component\EventDispatcher\EventDispatcherInterface");

        $this->actionCart = $this->getMock(
            "\Thelia\Action\Cart",
            array("generateCookie", "redirect"),
            array($dispatcher)

        );

        $this->actionCart
            ->expects($this->any())
            ->method("generateCookie")
            ->will($this->returnValue($this->uniqid));

        $this->actionCart
            ->expects($this->any())
            ->method("redirect");
    }

    /**
     * no cart present in session and cart_id no yet exists in cookies.
     *
     * In this case, a new cart instance must be create
     */
    public function testGetCartWithoutCustomerAndWithoutExistingCart()
    {
        $actionCart = $this->actionCart;

        $cart = $actionCart->getCart($this->request);

        $this->assertInstanceOf("Thelia\Model\Cart", $cart, '$cart must be an instance of cart model Thelia\Model\Cart');
        $this->assertNull($cart->getCustomerId());
        $this->assertNull($cart->getAddressDeliveryId());
        $this->assertNull($cart->getAddressInvoiceId());
        $this->assertEquals($this->uniqid, $cart->getToken());

    }

    /**
     * Customer is connected but his cart does not exists yet
     *
     * Cart must be created and associated to the current connected Customer
     */
    public function testGetCartWithCustomerAndWithoutExistingCart()
    {
        $actionCart = $this->actionCart;

        $request = $this->request;

        //create a fake customer just for test. If not persists test fails !
        $customer = new Customer();
        $customer->setFirstname("john");
        $customer->setLastname("doe");
        $customer->setTitleId(1);
        $customer->save();

        $request->getSession()->setCustomerUser($customer);

        $cart = $actionCart->getCart($request);
        $this->assertInstanceOf("Thelia\Model\Cart", $cart, '$cart must be an instance of cart model Thelia\Model\Cart');
        $this->assertNotNull($cart->getCustomerId());
        $this->assertEquals($customer->getId(), $cart->getCustomerId());
        $this->assertNull($cart->getAddressDeliveryId());
        $this->assertNull($cart->getAddressInvoiceId());
        $this->assertEquals($this->uniqid, $cart->getToken());

    }

    /**
     * Cart exists and his id put in cookies.
     *
     * Must return the same cart instance
     */
    public function testGetCartWithoutCustomerAndWithExistingCart()
    {
        $actionCart = $this->actionCart;

        $request = $this->request;
        $uniqid = uniqid("test1", true);
        //create a fake cart in database;
        $cart = new Cart();
        $cart->setToken($uniqid);
        $cart->save();

        $request->cookies->set("thelia_cart", $uniqid);

        $getCart = $actionCart->getCart($request);
        $this->assertInstanceOf("Thelia\Model\Cart", $getCart, '$cart must be an instance of cart model Thelia\Model\Cart');
        $this->assertNull($getCart->getCustomerId());
        $this->assertNull($getCart->getAddressDeliveryId());
        $this->assertNull($getCart->getAddressInvoiceId());
        $this->assertEquals($cart->getToken(), $getCart->getToken());
    }

    /**
     * a cart id exists in cookies but this id does not exists yet in databases
     *
     * a new cart must be created (different token)
     */
    public function testGetCartWithExistingCartButNotGoodCookies()
    {
        $actionCart = $this->actionCart;

        $request = $this->request;

        $token = "WrongToken";
        $request->cookies->set("thelia_cart", $token);

        $cart = $actionCart->getCart($request);
        $this->assertInstanceOf("Thelia\Model\Cart", $cart, '$cart must be an instance of cart model Thelia\Model\Cart');
        $this->assertNull($cart->getCustomerId());
        $this->assertNull($cart->getAddressDeliveryId());
        $this->assertNull($cart->getAddressInvoiceId());
        $this->assertNotEquals($token, $cart->getToken());
    }

    /**
     * cart and customer already exists. Cart and customer are linked.
     *
     * cart in session must be return
     */
    public function testGetCartWithExistingCartAndCustomer()
    {
        $actionCart = $this->actionCart;

        $request = $this->request;


        //create a fake customer just for test. If not persists test fails !
        $customer = new Customer();
        $customer->setFirstname("john");
        $customer->setLastname("doe");
        $customer->setTitleId(1);
        $customer->save();

        $uniqid = uniqid("test2", true);
        //create a fake cart in database;
        $cart = new Cart();
        $cart->setToken($uniqid);
        $cart->setCustomer($customer);
        $cart->save();

        $request->cookies->set("thelia_cart", $uniqid);

        $request->getSession()->setCustomerUser($customer);

        $getCart = $actionCart->getCart($request);
        $this->assertInstanceOf("Thelia\Model\Cart", $getCart, '$cart must be an instance of cart model Thelia\Model\Cart');
        $this->assertNotNull($getCart->getCustomerId());
        $this->assertNull($getCart->getAddressDeliveryId());
        $this->assertNull($getCart->getAddressInvoiceId());
        $this->assertEquals($cart->getToken(), $getCart->getToken(), "token must be the same");
        $this->assertEquals($customer->getId(), $getCart->getCustomerId());
    }

    /**
     * Customer is connected but cart not associated to him
     *
     * A new cart must be created (duplicated) containing customer id
     */
    public function testGetCartWithExistingCartAndCustomerButNotSameCustomerId()
    {
        $actionCart = $this->actionCart;

        $request = $this->request;


        //create a fake customer just for test. If not persists test fails !
        $customer = new Customer();
        $customer->setFirstname("john");
        $customer->setLastname("doe");
        $customer->setTitleId(1);
        $customer->save();

        $uniqid = uniqid("test3", true);
        //create a fake cart in database;
        $cart = new Cart();
        $cart->setToken($uniqid);

        $cart->save();

        $request->cookies->set("thelia_cart", $uniqid);

        $request->getSession()->setCustomerUser($customer);

        $getCart = $actionCart->getCart($request);
        $this->assertInstanceOf("Thelia\Model\Cart", $getCart, '$cart must be an instance of cart model Thelia\Model\Cart');
        $this->assertNotNull($getCart->getCustomerId());
        $this->assertNull($getCart->getAddressDeliveryId());
        $this->assertNull($getCart->getAddressInvoiceId());
        $this->assertNotEquals($cart->getToken(), $getCart->getToken(), "token must be different");
        $this->assertEquals($customer->getId(), $getCart->getCustomerId());
    }


    /**
     * AddArticle action without data in the request, the form must not be valid
     */
    public function testAddArticleWithError()
    {
        $actionEvent = new DefaultActionEvent($this->request, "AddArticle");

        $this->actionCart->addArticle($actionEvent);

        $this->assertTrue($actionEvent->hasErrorForm(), "no data in the request, so the action must failed and a form error must be present");

    }



}