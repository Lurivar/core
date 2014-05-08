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


namespace Thelia\Action;

use Propel\Runtime\Propel;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Filesystem\Filesystem;
use Thelia\Core\Event\Cache\CacheEvent;
use Thelia\Core\Event\Module\ModuleDeleteEvent;
use Thelia\Core\Event\Module\ModuleEvent;
use Thelia\Core\Event\Module\ModuleToggleActivationEvent;
use Thelia\Core\Event\Order\OrderPaymentEvent;

use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\Event\UpdatePositionEvent;
use Thelia\Core\Translation\Translator;
use Thelia\Log\Tlog;
use Thelia\Model\Map\ModuleTableMap;
use Thelia\Model\ModuleQuery;
use Thelia\Module\BaseModule;


/**
 * Class ModuleHook
 * @package Thelia\Action
 * @author Julien Chanséaume <jchanseaume@openstudio.fr>
 */
class ModuleHook extends BaseAction implements EventSubscriberInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function toggleModuleActivation(ModuleToggleActivationEvent $event)
    {
        // Todo update module hook for the module
        return $event;
    }

    public function deleteModuleActivation(ModuleDeleteEvent $event)
    {
        // Todo update module hook for the module
        return $event;
    }

    public function updateModuleHookPosition(ModuleDeleteEvent $event)
    {
        // Todo update module hook for the module hook
        return $event;
    }

    protected function cacheClear(EventDispatcherInterface $dispatcher)
    {
        $cacheEvent = new CacheEvent(
            $this->container->getParameter('kernel.cache_dir')
        );

        $dispatcher->dispatch(TheliaEvents::CACHE_CLEAR, $cacheEvent);
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     *
     * @api
     */
    public static function getSubscribedEvents()
    {
        return array(
            TheliaEvents::MODULE_TOGGLE_ACTIVATION => array('toggleModuleActivation', 128),
            TheliaEvents::MODULE_DELETE => array('deleteModuleActivation', 128),
            TheliaEvents::MODULE_HOOK_UPDATE_POSITION => array('updateModuleHookPosition', 128),
            // TheliaEvents::MODULE_HOOK_UPDATE_CONTEXT => array('updateModuleHookContext', 128),
            // TheliaEvents::MODULE_HOOK_TOGGLE_ACTIVATION => array('toggleModuleHookActivation', 128),
        );
    }
}
