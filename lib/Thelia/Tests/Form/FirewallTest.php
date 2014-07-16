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

namespace Thelia\Tests\Form;
use Symfony\Component\DependencyInjection\Container;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Core\HttpFoundation\Session\Session;
use Thelia\Core\Translation\Translator;
use Thelia\Model\ConfigQuery;
use Thelia\Model\FormFirewallQuery;
use Thelia\Model\Map\FormFirewallTableMap;

/**
 * Class FirewallTest
 * @package Thelia\Tests\Form
 * @author Benjamin Perche <bperche@openstudio.fr>
 */
class FirewallTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Thelia\Core\HttpFoundation\Request */
    protected $request;

    /** @var  \PHPUnit_Framework_MockObject_MockObject */
    protected $form;

    public function setUp()
    {
        $session = new Session();

        new Translator(new Container());

        $this->request = new Request();
        $this->request->setSession($session);

        /**
         * Get an example form. We
         */
        $this->form = $this->getMock(
            "\Thelia\Form\FirewallForm",
            [
                "buildForm",
                "getName",
            ],
            [
                $this->request,
            ]
        );
        $this->form
            ->expects($this->any())
                ->method('getName')
                    ->will($this->returnValue("test_form_firewall"))
        ;

        /**
         * Be sure that the firewall is active
         */
        ConfigQuery::write("form_firewall_active", 1);
        ConfigQuery::write("form_firewall_time_to_wait", 60);
        ConfigQuery::write("form_firewall_attempts", 6);

        /**
         * Empty the firewall blacklist between each test
         */
        FormFirewallQuery::create()->find()->delete();
    }

    public function testBlock()
    {
        for ($i = 1; $i <= 10; ++$i) {

            if ($i > 6) {
                $this->assertFalse(
                    $this->form->isFirewallOk()
                );
            } else {
                $this->assertTrue(
                    $this->form->isFirewallOk()
                );
            }

            $attempts = FormFirewallQuery::create()
                ->select(FormFirewallTableMap::ATTEMPTS)
                ->findOne()
            ;

            $this->assertEquals($i > 6 ? 6 : $i, $attempts);

        }
    }

    public function testFormatTime()
    {
        $this->assertEquals(
            "1 hour(s)",
            $this->form->getWaitingTime()
        );

        ConfigQuery::write("form_firewall_time_to_wait", 61);

        $this->assertEquals(
            "1 hour(s) 1 minute(s)",
            $this->form->getWaitingTime()
        );

        ConfigQuery::write("form_firewall_time_to_wait", 59);

        $this->assertEquals(
            "59 minute(s)",
            $this->form->getWaitingTime()
        );

        ConfigQuery::write("form_firewall_time_to_wait", 5);

        $this->assertEquals(
            "5 minute(s)",
            $this->form->getWaitingTime()
        );

        ConfigQuery::write("form_firewall_time_to_wait", 132);

        $this->assertEquals(
            "2 hour(s) 12 minute(s)",
            $this->form->getWaitingTime()
        );
    }
} 