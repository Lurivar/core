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

namespace Thelia\Core\DependencyInjection\Compiler;

use Propel\Runtime\Propel;
use ReflectionException;
use ReflectionMethod;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Definition;
use Thelia\Core\Template\TemplateDefinition;
use Thelia\Log\Tlog;
use Thelia\Model\HookQuery;
use Thelia\Model\ModuleHookQuery;
use Thelia\Model\ModuleHook;
use Thelia\Model\ModuleQuery;

/**
 * Class RegisterListenersPass
 * @package Thelia\Core\DependencyInjection\Compiler
 *
 * Source code come from Symfony\Bundle\FrameworkBundle\DependencyInjection\Compiler\RegisterKernelListenersPass class
 * @author Manuel Raynaud <mraynaud@openstudio.fr>
 */
class RegisterListenersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('event_dispatcher')) {
            return;
        }

        $definition = $container->getDefinition('event_dispatcher');

        foreach ($container->findTaggedServiceIds('kernel.event_listener') as $id => $events) {
            foreach ($events as $event) {
                $priority = isset($event['priority']) ? $event['priority'] : 0;

                if (!isset($event['event'])) {
                    throw new \InvalidArgumentException(sprintf('Service "%s" must define the "event" attribute on "kernel.event_listener" tags.', $id));
                }

                if (!isset($event['method'])) {
                    $event['method'] = 'on'.preg_replace(array(
                        '/(?<=\b)[a-z]/ie',
                        '/[^a-z0-9]/i'
                    ), array('strtoupper("\\0")', ''), $event['event']);
                }

                $definition->addMethodCall('addListenerService', array($event['event'], array($id, $event['method']), $priority));
            }
        }

        foreach ($container->findTaggedServiceIds('kernel.event_subscriber') as $id => $attributes) {
            // We must assume that the class value has been correctly filled, even if the service is created by a factory
            $class = $container->getDefinition($id)->getClass();

            $refClass = new \ReflectionClass($class);
            $interface = 'Symfony\Component\EventDispatcher\EventSubscriberInterface';
            if (!$refClass->implementsInterface($interface)) {
                throw new \InvalidArgumentException(sprintf('Service "%s" must implement interface "%s".', $id, $interface));
            }

            $definition->addMethodCall('addSubscriberService', array($id, $class));
        }

        // We have to check if Propel is initialized before registering hooks
        $managers = Propel::getServiceContainer()->getConnectionManagers();
        if (! array_key_exists('thelia', $managers)) {
            return;
        }

        foreach ($container->findTaggedServiceIds('hook.event_listener') as $id => $events) {

            $class = $container->getDefinition($id)->getClass();

            // the class must extends BaseHook
            $implementClass = 'Thelia\Core\Hook\BaseHook';
            if (! is_subclass_of($class, $implementClass)) {
                throw new \InvalidArgumentException(sprintf('Hook class "%s" must extends class "%s".', $class, $implementClass));
            }

            // retrieve the module id
            $properties = $container->getDefinition($id)->getProperties();
            $module = null;
            if (array_key_exists('module', $properties)) {
                $moduleCode = explode(".", $properties['module'])[1];
                if (null !== $module = ModuleQuery::create()->findOneByCode($moduleCode)) {
                    $module = $module->getId();
                }
            }

            foreach ($events as $event) {

                $priority = isset($event['priority']) ? $event['priority'] : 0;

                if (!isset($event['event'])) {
                    throw new \InvalidArgumentException(sprintf('Service "%s" must define the "event" attribute on "hook.event_listener" tags.', $id));
                }

                $type = (isset($event['type'])) ? $this->getHookType($event['type']) : TemplateDefinition::FRONT_OFFICE;

                $hook = HookQuery::create()
                    ->filterByCode($event['event'])
                    ->filterByType($type)
                    ->findOne();
                if (null === $hook) {
                    Tlog::getInstance()->addAlert(sprintf("Hook %s is unknown.", $event['event']));
                    continue;
                }
                if (! $hook->getActivate()) {
                    Tlog::getInstance()->addAlert(sprintf("Hook %s is not activated.", $event['event']));
                    continue;
                }

                if (!isset($event['method'])) {
                    $event['method'] = 'on'.preg_replace(array(
                            '/(?<=\b)[a-z]/ie',
                            '/[^a-z0-9]/i'
                        ), array('strtoupper("\\0")', ''), $event['event']);
                }

                // test if method exists
                if (! $this->isValidHookMethod($class, $event['method'], $hook->getBlock())) {
                    continue;
                }

                // test if hook is already registered in ModuleHook
                $moduleHook = ModuleHookQuery::create()
                    ->filterByModuleId($module)
                    ->filterByHook($hook)
                    ->filterByMethod($event['method'])
                    ->findOne();

                if (null === $moduleHook) {
                    // hook for module doesn't exist, we add it with default registered values
                    $moduleHook = new ModuleHook();
                    //$moduleHook->setModuleId();
                    $moduleHook->setHook($hook)
                        ->setModuleId($module)
                        ->setClassname($id)
                        ->setMethod($event['method'])
                        ->setActive(true)
                        ->setHookActive(true)
                        ->setModuleActive(true)
                        ->setPosition(ModuleHook::MAX_POSITION)
                        ->save();
                }
            }
        }

        // now we can add listeners for active hooks and active module
        $this->addHooksMethodCall($definition);

    }

    protected function getHookType($name)
    {
        $type = TemplateDefinition::FRONT_OFFICE;

        if (null !== $name && is_string($name)) {
            $name = preg_replace("[^a-z]", "", strtolower(trim($name)));
            if (in_array($name, array('bo', 'back', 'backoffice'))) {
                $type = TemplateDefinition::BACK_OFFICE;
            } elseif (in_array($name, array('email'))) {
                $type = TemplateDefinition::EMAIL;
            } elseif (in_array($name, array('pdf'))) {
                $type = TemplateDefinition::PDF;
            }
        }

        return $type;
    }


    protected function isValidHookMethod($className, $methodName, $block)
    {
        try {
            $method = new ReflectionMethod($className, $methodName);

            $parameters = $method->getParameters();
            if (count($parameters) !== 1) {
                Tlog::getInstance()->addAlert(sprintf("Method %s does not exist in %s : %s", $methodName, $className, $ex));

                return false;
            }

            $eventType = ($block) ?
                'Thelia\Core\Event\Hook\HookRenderBlockEvent' :
                'Thelia\Core\Event\Hook\HookRenderEvent';
            if ($parameters[0]->getClass()->getName() !== $eventType) {
                Tlog::getInstance()->addAlert(sprintf("Method %s should use an event of type %s", $methodName, $eventType));

                return false;;
            }
        } catch (ReflectionException $ex) {
            Tlog::getInstance()->addAlert(sprintf("Method %s does not exist in %s", $methodName, $className));

            return false;;
        }

        return true;
    }

    protected function addHooksMethodCall(Definition $definition)
    {
        $moduleHooks = ModuleHookQuery::create()
            //->filterByActive(true)
            //->filterByModuleActive(true)
            ->orderByHookId()
            ->orderByPosition()
            ->orderById()
            ->find();

        $modulePosition = 0;
        $hookId = 0;
        /** @var ModuleHook $moduleHook */
        foreach ($moduleHooks as $moduleHook) {
            // manage module hook position for new hook
            if ($hookId !== $moduleHook->getHookId()) {
                $hookId = $moduleHook->getHookId();
                $modulePosition = 1;
            } else {
                $modulePosition++;
            }
            if ($moduleHook->getPosition() === ModuleHook::MAX_POSITION) {
                // new module hook, we set it at the end of the queue for this event
                $moduleHook->setPosition($modulePosition)->save();
            } else {
                $modulePosition = $moduleHook->getPosition($modulePosition);
            }
            // Add the the new listener for active hooks, we have to reverse the priority and the position
            if ($moduleHook->getActive() && $moduleHook->getModuleActive()) {
                $hook = $moduleHook->getHook();
                $eventName = sprintf('hook.%s.%s', $hook->getType(), $hook->getCode());

                // we a register an event which is relative to a specific module
                if ($hook->getByModule()) {
                    $eventName .= '.' . $moduleHook->getModuleId();
                }

                $definition->addMethodCall('addListenerService',
                    array(
                        $eventName,
                        array($moduleHook->getClassname(), $moduleHook->getMethod()),
                        ModuleHook::MAX_POSITION - $moduleHook->getPosition()
                    )
                );
            }
        }

    }
}
