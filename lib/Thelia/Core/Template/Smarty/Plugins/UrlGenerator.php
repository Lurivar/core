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

use Thelia\Core\Template\Smarty\SmartyPluginDescriptor;
use Thelia\Core\Template\Smarty\AbstractSmartyPlugin;
use Thelia\Tools\URL;
use Thelia\Core\HttpFoundation\Request;

class UrlGenerator extends AbstractSmartyPlugin
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Process url generator function
     *
     * @param  array   $params
     * @param  unknown $smarty
     * @return string  no text is returned.
     */
    public function generateUrlFunction($params, &$smarty)
    {
        // the path to process
        $path = $this->getParam($params, 'path');

        $target = $this->getParam($params, 'target', null);

        $url = URL::getInstance()->absoluteUrl($path, $this->getArgsFromParam($params, array('path', 'target')));

        if ($target != null) $url .= '#'.$target;

        return $url;
     }

     /**
      * Process view url generator function
      *
      * @param  array $params
      * @param  unknown $smarty
      * @return string no text is returned.
      */
     public function generateFrontViewUrlFunction($params, &$smarty)
     {
         return $this->generateViewUrlFunction($params, false);
     }

     /**
      * Process administration view url generator function
      *
      * @param  array $params
      * @param  unknown $smarty
      * @return string no text is returned.
      */
     public function generateAdminViewUrlFunction($params, &$smarty)
     {
         return $this->generateViewUrlFunction($params, true);
     }

    public function navigateToUrlFunction($params, &$smarty)
     {
         $to = $this->getParam($params, 'to', null);

         $toMethod = $this->getNavigateToMethod($to);

         return $this->$toMethod();
     }

     protected function generateViewUrlFunction($params, $forAdmin)
     {
         // the view name (without .html)
         $view = $this->getParam($params,'view');

          // the related action (optionale)
         $action = $this->getParam($params, 'action');

         $args = $this->getArgsFromParam($params, array('view', 'action', 'target'));

         if (! empty($action)) $args['action'] = $action;
         return $forAdmin ? URL::getInstance()->adminViewUrl($view, $args) : URL::getInstance()->viewUrl($view, $args);
     }

     /**
      * Get URL parameters array from parameters.
      *
      * @param array $params Smarty function params
      * @return array the parameters array (either emply, of valued)
      */
     private function getArgsFromParam($params, $exclude = array())
     {
         $pairs = array();

           foreach ($params as $name => $value) {

               if (in_array($name, $exclude)) continue;

               $pairs[$name] = $value;
           }

           return $pairs;
     }

    /**
     * Define the various smarty plugins handled by this class
     *
     * @return an array of smarty plugin descriptors
     */
    public function getPluginDescriptors()
    {
        return array(
            new SmartyPluginDescriptor('function', 'url', $this, 'generateUrlFunction'),
            new SmartyPluginDescriptor('function', 'viewurl', $this, 'generateFrontViewUrlFunction'),
            new SmartyPluginDescriptor('function', 'admin_viewurl', $this, 'generateAdminViewUrlFunction'),
            new SmartyPluginDescriptor('function', 'navigate', $this, 'navigateToUrlFunction'),
        );
    }

    /**
     * @return array sur le format "to_value" => "method_name"
     */
    protected function getNavigateToValues()
    {
        return array(
            "current"   => "getCurrentUrl",
            "return_to" => "getReturnToUrl",
            "index"     => "getIndexUrl",
        );
    }

    protected function getNavigateToMethod($to)
    {
        if($to === null) {
            throw new \InvalidArgumentException("Missing 'to' parameter in `navigate` substitution.");
        }

        $navigateToValues = $this->getNavigateToValues();

        if(!array_key_exists($to, $navigateToValues)) {
            throw new \InvalidArgumentException("Incorrect value for parameter `to` in `navigate` substitution.");
        }

        return $navigateToValues[$to];
    }

    protected function getCurrentUrl()
    {
        return URL::getInstance()->retrieveCurrent()->toString();
    }

    protected function getReturnToUrl()
    {
        return URL::getInstance()->absoluteUrl($this->request->getSession()->getReturnToUrl());
    }

    protected function getIndexUrl()
    {
        return URL::getInstance()->getIndexPage();
    }
}
