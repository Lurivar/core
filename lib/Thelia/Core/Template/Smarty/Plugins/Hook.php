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

namespace Thelia\Core\Template\Smarty\Plugins;

use Thelia\Core\Event\Hook\HookEvent;
use Thelia\Core\Template\Smarty\SmartyPluginDescriptor;
use Thelia\Core\Template\Smarty\AbstractSmartyPlugin;
use Thelia\Core\Security\SecurityContext;
use Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Thelia\Log\Tlog;


/**
 * Class Hook
 * @package Thelia\Core\Template\Smarty\Plugins
 * @author Julien Chanséaume <jchanseaume@openstudio.fr>
 */
class Hook extends AbstractSmartyPlugin
{

    private $dispatcher;

    public function __construct(ContainerAwareEventDispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Generates the content of the hook
     *
     * @param  array   $params
     * @param  unknown $smarty
     * @return string  no text is returned.
     */
    public function processHook($params, &$smarty)
    {
        // The current order of the table
        $hookName = "hook." . $this->getParam($params, 'name');
        Tlog::getInstance()->addDebug("_HOOK_ process hook : " . $hookName);

        $event = new HookEvent($hookName);

        $event = $this->getDispatcher()->dispatch($hookName, $event);

        $content = "";
        foreach ($event->getFragments() as $fragment){
            $content .= $fragment->getContent();
        }

        return $content;
    }

    /**
     * Define the various smarty plugins handled by this class
     *
     * @return an array of smarty plugin descriptors
     */
    public function getPluginDescriptors()
    {
        return array(
            new SmartyPluginDescriptor('function', 'hook', $this, 'processHook')
        );
    }


    /**
     * Return the event dispatcher,
     *
     * @return \Symfony\Component\EventDispatcher\EventDispatcher
     */
    public function getDispatcher()
    {
        return $this->dispatcher;
    }

}
