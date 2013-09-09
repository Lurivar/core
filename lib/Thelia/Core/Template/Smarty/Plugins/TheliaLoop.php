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

namespace Thelia\Core\Template\Smarty\Plugins;

use Thelia\Core\Template\Element\BaseLoop;
use Thelia\Core\Template\Smarty\AbstractSmartyPlugin;
use Thelia\Core\Template\Smarty\SmartyPluginDescriptor;

use Thelia\Core\Template\Element\Exception\ElementNotFoundException;
use Thelia\Core\Template\Element\Exception\InvalidElementException;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Thelia\Core\Security\SecurityContext;

class TheliaLoop extends AbstractSmartyPlugin
{
    protected static $pagination = null;

    protected $loopDefinition = array();

    protected $request;
    protected $dispatcher;
    protected $securityContext;

    protected $loopstack = array();
    protected $varstack = array();

    public function __construct(Request $request, EventDispatcherInterface $dispatcher, SecurityContext $securityContext)
    {
        $this->request = $request;
        $this->dispatcher = $dispatcher;
        $this->securityContext = $securityContext;
    }

    /**
     * @param $loopId
     *
     * @return \PropelModelPager
     */
    public static function getPagination($loopId)
    {
        if(!empty(self::$pagination[$loopId])) {
            return self::$pagination[$loopId];
        } else {
            return null;
        }
    }

    /**
     * Process the count function: executes a loop and return the number of items found
     */
    public function theliaCount($params, $template)
    {
        $type = $this->getParam($params, 'type');

    	if (null == $type) {
            throw new \InvalidArgumentException("Missing 'type' parameter in count arguments");
        }

        $loop = $this->createLoopInstance($params);

        $dummy = null;

        $loopResults = $loop->exec($dummy);

        return $loopResults->valid() ? $loopResults->getCount() : 0;
    }

    /**
     * Process {loop name="loop name" type="loop type" ... } ... {/loop} block
     *
     * @param  unknown                   $params
     * @param  unknown                   $content
     * @param  unknown                   $template
     * @param  unknown                   $repeat
     * @throws \InvalidArgumentException
     * @return string
     */
    public function theliaLoop($params, $content, $template, &$repeat)
    {
        $name = $this->getParam($params, 'name');

        if (null == $name) {
            throw new \InvalidArgumentException("Missing 'name' parameter in loop arguments");
        }

        $type = $this->getParam($params, 'type');

        if (null == $type) {
            throw new \InvalidArgumentException("Missing 'type' parameter in loop arguments");
        }

        if ($content === null) {
            // Check if a loop with the same name exists in the current scope, and abort if it's the case.
            if (array_key_exists($name, $this->varstack)) {
                throw new \InvalidArgumentException("A loop named '$name' already exists in the current scope.");
            }

            $loop = $this->createLoopInstance($params);

            self::$pagination[$name] = null;

            $loopResults = $loop->exec(self::$pagination[$name]);

            $this->loopstack[$name] = $loopResults;

            // Pas de résultat ? la boucle est terminée, ne pas évaluer le contenu.
            if ($loopResults->isEmpty()) $repeat = false;

        } else {
            $loopResults = $this->loopstack[$name];

            $loopResults->next();
        }

        if ($loopResults->valid()) {

            $loopResultRow = $loopResults->current();

            // On first iteration, save variables that may be overwritten by this loop
            if (! isset($this->varstack[$name])) {

                $saved_vars = array();

                $varlist = $loopResultRow->getVars();

                foreach ($varlist as $var) {
                    $saved_vars[$var] = $template->getTemplateVars($var);
                }

                $this->varstack[$name] = $saved_vars;
            }

            foreach ($loopResultRow->getVarVal() as $var => $val) {
                $template->assign($var, $val);
            }

            $repeat = true;
        }

        // Loop is terminated. Cleanup.
        if (! $repeat) {
            // Restore previous variables values before terminating
            if (isset($this->varstack[$name])) {
                foreach ($this->varstack[$name] as $var => $value) {
                    $template->assign($var, $value);
                }

                unset($this->varstack[$name]);
            }
        }

        if ($content !== null) {
            if ($loopResults->isEmpty()) {
                $content = "";
            }

            return $content;
        }
    }

    /**
     * Process {elseloop rel="loopname"} ... {/elseloop} block
     *
     * @param  unknown  $params
     * @param  unknown  $content
     * @param  unknown  $template
     * @param  unknown  $repeat
     * @return Ambigous <string, unknown>
     */
    public function theliaElseloop($params, $content, $template, &$repeat)
    {

        // When encoutering close tag, check if loop has results.
        if ($repeat === false) {
            return $this->checkEmptyLoop($params, $template) ? $content : '';
        }
    }

    /**
     * Process {ifloop rel="loopname"} ... {/ifloop} block
     *
     * @param  unknown  $params
     * @param  unknown  $content
     * @param  unknown  $template
     * @param  unknown  $repeat
     * @return Ambigous <string, unknown>
     */
    public function theliaIfLoop($params, $content, $template, &$repeat)
    {
        // When encountering close tag, check if loop has results.
        if ($repeat === false) {
            return $this->checkEmptyLoop($params, $template) ? '' : $content;
        }
    }

    /**
     * Process {pageloop rel="loopname"} ... {/pageloop} block
     *
     * @param $params
     * @param $content
     * @param $template
     * @param $repeat
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    public function theliaPageLoop($params, $content, $template, &$repeat)
    {
        $loopName = $this->getParam($params, 'rel');

        if (null == $loopName)
            throw new \InvalidArgumentException("Missing 'rel' parameter in page loop");

        // Find loop results in the current template vars
        /* $loopResults = $template->getTemplateVars($loopName);
        if (empty($loopResults)) {
            throw new \InvalidArgumentException("Loop $loopName is not defined.");
        }*/

        // Find pagination
        $pagination = self::getPagination($loopName);
        if ($pagination === null) {
            throw new \InvalidArgumentException("Loop $loopName  is not defined");
        }

        if ($pagination->getNbResults() == 0) {
            return '';
        }

        if ($content === null) {
            $page = 1;
        } else {
            $page = $template->getTemplateVars('PAGE');
            $page++;
        }

        if ($page <= $pagination->getLastPage()) {
            $template->assign('PAGE', $page);
            $template->assign('CURRENT', $pagination->getPage());
            $template->assign('LAST', $pagination->getLastPage());

            $repeat = true;
        }

        if ($content !== null) {
            return $content;
        }
    }

    /**
     * Check if a loop has returned results. The loop shoud have been executed before, or an
     * InvalidArgumentException is thrown
     *
     * @param  unknown                   $params
     * @param  unknown                   $template
     * @throws \InvalidArgumentException
     */
    protected function checkEmptyLoop($params, $template)
    {
        $loopName = $this->getParam($params, 'rel');

        if (null == $loopName)
             throw new \InvalidArgumentException("Missing 'rel' parameter in ifloop/elseloop arguments");

        if (! isset($this->loopstack[$loopName])) {
            throw new \InvalidArgumentException("Loop $loopName is not defined.");
        }

        return $this->loopstack[$loopName]->isEmpty();
    }

    /**
     * @param $smartyParams
     *
     * @return object
     * @throws \Thelia\Core\Template\Element\Exception\InvalidElementException
     * @throws \Thelia\Core\Template\Element\Exception\ElementNotFoundException
     */
    protected function createLoopInstance($smartyParams)
    {
        $type = strtolower($smartyParams['type']);

        if (! isset($this->loopDefinition[$type])) {
            throw new ElementNotFoundException(sprintf("'%s' loop type does not exists", $type));
        }

        $class = new \ReflectionClass($this->loopDefinition[$type]);

        if ($class->isSubclassOf("Thelia\Core\Template\Element\BaseLoop") === false) {
            throw new InvalidElementException(sprintf("'%s' Loop class should extends Thelia\Core\Template\Element\BaseLoop",
                    $type));
        }

        $loop = $class->newInstance(
                $this->request,
                $this->dispatcher,
                $this->securityContext
        );

        $loop->initializeArgs($smartyParams);

        return $loop;
    }

    /**
     *
     * Injects an associative array containing information for loop execution
     *
     * key is loop name
     * value is the class implementing/extending base loop classes
     *
     * ex :
     *
     * $loop = array(
     *  "product" => "Thelia\Loop\Product",
     *  "category" => "Thelia\Loop\Category",
     *  "myLoop" => "My\Own\Loop"
     * );
     *
     * @param  array                     $loopDefinition
     * @throws \InvalidArgumentException if loop name already exists
     */
    public function setLoopList(array $loopDefinition)
    {
        foreach ($loopDefinition as $name => $className) {
            if (array_key_exists($name, $this->loopDefinition)) {
                throw new \InvalidArgumentException(sprintf("%s loop name already exists for %s class name", $name, $className));
            }

            $this->loopDefinition[$name] = $className;
        }
    }

    /**
     * Defines the various smarty plugins hendled by this class
     *
     * @return an array of smarty plugin descriptors
     */
    public function getPluginDescriptors()
    {
        return array(

            new SmartyPluginDescriptor('function', 'count'    , $this, 'theliaCount'),
            new SmartyPluginDescriptor('block'   , 'loop'     , $this, 'theliaLoop'),
            new SmartyPluginDescriptor('block'   , 'elseloop' , $this, 'theliaElseloop'),
            new SmartyPluginDescriptor('block'   , 'ifloop'   , $this, 'theliaIfLoop'),
            new SmartyPluginDescriptor('block'   , 'pageloop' , $this, 'theliaPageLoop'),
        );
     }
}
