<?php
/**********************************************************************************/
/*                                                                                */
/*      Thelia	                                                                  */
/*                                                                                */
/*      Copyright (c) OpenStudio                                                  */
/*      email : info@thelia.net                                                   */
/*      web : http://www.thelia.net                                               */
/*                                                                                */
/*      This program is free software; you can redistribute it and/or modify      */
/*      it under the terms of the GNU General Public License as published by      */
/*      the Free Software Foundation; either version 3 of the License             */
/*                                                                                */
/*      This program is distributed in the hope that it will be useful,           */
/*      but WITHOUT ANY WARRANTY; without even the implied warranty of            */
/*      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the             */
/*      GNU General Public License for more details.                              */
/*                                                                                */
/*      You should have received a copy of the GNU General Public License         */
/*	    along with this program. If not, see <http://www.gnu.org/licenses/>.      */
/*                                                                                */
/**********************************************************************************/

namespace Thelia\Constraint;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Thelia\Constraint\Rule\AvailableForXArticles;
use Thelia\Constraint\Validator\PriceParam;
use Thelia\Constraint\Validator\RuleValidator;
use Thelia\Constraint\Rule\AvailableForTotalAmount;
use Thelia\Constraint\Rule\Operators;
use Thelia\Coupon\CouponBaseAdapter;
use Thelia\Coupon\CouponBaseAdapterTest;
use Thelia\Coupon\CouponRuleCollection;
use Thelia\Coupon\Type\CouponInterface;
use Thelia\Coupon\Type\RemoveXAmount;
use Thelia\Tools\PhpUnitUtils;

/**
 * Created by JetBrains PhpStorm.
 * Date: 8/19/13
 * Time: 3:24 PM
 *
 * Unit Test ConstraintManager Class
 *
 * @package Constraint
 * @author  Guillaume MOREL <gmorel@openstudio.fr>
 *
 */
class ConstraintManagerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
    }

    /**
     * Check the if the Constraint Manager is able to check RuleValidators
     */
    public function testIsMatching()
    {
        $stubTranslator = $this->getMockBuilder('\Thelia\Core\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();

        $stubAdapter = $this->getMockBuilder('\Thelia\Coupon\CouponBaseAdapter')
            ->disableOriginalConstructor()
            ->getMock();

        $stubAdapter->expects($this->any())
            ->method('getTranslator')
            ->will($this->returnValue($stubTranslator));

        $stubAdapter->expects($this->any())
            ->method('getCartTotalPrice')
            ->will($this->returnValue(321.98));

        $stubAdapter->expects($this->any())
            ->method('getCheckoutCurrency')
            ->will($this->returnValue('USD'));

        $rule1 = new AvailableForTotalAmount($stubAdapter);
        $operators = array(AvailableForTotalAmount::PARAM1_PRICE => Operators::SUPERIOR);
        $values = array(
            AvailableForTotalAmount::PARAM1_PRICE => 40.00,
            AvailableForTotalAmount::PARAM1_CURRENCY => 'USD'
        );
        $rule1->populateFromForm($operators, $values);

        $rule2 = new AvailableForTotalAmount($stubAdapter);
        $operators = array(AvailableForTotalAmount::PARAM1_PRICE => Operators::INFERIOR);
        $values = array(
            AvailableForTotalAmount::PARAM1_PRICE => 400.00,
            AvailableForTotalAmount::PARAM1_CURRENCY => 'USD'
        );
        $rule2->populateFromForm($operators, $values);

        $rules = new CouponRuleCollection();
        $rules->add($rule1);
        $rules->add($rule2);

        /** @var ConstraintManager $constraintManager */
        $constraintManager = new ConstraintManager($this->getContainer());

        $expected = true;
        $actual = $constraintManager->isMatching($rules);

        $this->assertEquals($expected, $actual, 'The ConstraintManager is no more able to check if a Rule is matching');
    }

    /**
     * Check the Rules serialization module
     */
    public function testRuleSerialisation()
    {
        $stubTranslator = $this->getMockBuilder('\Thelia\Core\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();

        $stubAdapter = $this->getMockBuilder('\Thelia\Coupon\CouponBaseAdapter')
            ->disableOriginalConstructor()
            ->getMock();

        $stubAdapter->expects($this->any())
            ->method('getTranslator')
            ->will($this->returnValue($stubTranslator));

        $rule1 = new AvailableForTotalAmount($stubAdapter);
        $operators = array(AvailableForTotalAmount::PARAM1_PRICE => Operators::SUPERIOR);
        $values = array(
            AvailableForTotalAmount::PARAM1_PRICE => 40.00,
            AvailableForTotalAmount::PARAM1_CURRENCY => 'EUR'
        );
        $rule1->populateFromForm($operators, $values);

        $rule2 = new AvailableForTotalAmount($stubAdapter);
        $operators = array(AvailableForTotalAmount::PARAM1_PRICE => Operators::INFERIOR);
        $values = array(
            AvailableForTotalAmount::PARAM1_PRICE => 400.00,
            AvailableForTotalAmount::PARAM1_CURRENCY => 'EUR'
        );
        $rule2->populateFromForm($operators, $values);

        $rules = new CouponRuleCollection();
        $rules->add($rule1);
        $rules->add($rule2);

        /** @var ConstraintManager $constraintManager */
        $constraintManager = new ConstraintManager($this->getContainer());

        $serializedRules = $constraintManager->serializeCouponRuleCollection($rules);
        $unserializedRules = $constraintManager->unserializeCouponRuleCollection($serializedRules);

        $expected = (string)$rules;
        $actual = (string)$unserializedRules;

        $this->assertEquals($expected, $actual);
    }

    /**
     * Get Mocked Container with 2 Rules
     *
     * @return ContainerBuilder
     */
    public function getContainer()
    {
        $container = new ContainerBuilder();

        $stubTranslator = $this->getMockBuilder('\Thelia\Core\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();

        $stubAdapter = $this->getMockBuilder('\Thelia\Coupon\CouponBaseAdapter')
            ->disableOriginalConstructor()
            ->getMock();

        $stubAdapter->expects($this->any())
            ->method('getTranslator')
            ->will($this->returnValue($stubTranslator));

        $rule1 = new AvailableForTotalAmount($stubAdapter);
        $rule2 = new AvailableForXArticles($stubAdapter);

        $adapter = new CouponBaseAdapter($container);

        $container->set('thelia.constraint.rule.available_for_total_amount', $rule1);
        $container->set('thelia.constraint.rule.available_for_x_articles', $rule2);
        $container->set('thelia.adapter', $adapter);

        return $container;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }
}
