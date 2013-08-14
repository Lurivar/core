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
use Thelia\Core\Security\SecurityContext;
use Thelia\Core\Security\Exception\AuthenticationException;

class Security extends AbstractSmartyPlugin
{
    private $securityContext;

    public function __construct(SecurityContext $securityContext)
    {
        $this->securityContext = $securityContext;
    }

    /**
     * Process security check function
     *
     * @param  array   $params
     * @param  unknown $smarty
     * @return string  no text is returned.
     */
    public function checkAuthFunction($params, &$smarty)
    {
        // Context: 'front' or 'admin'
           $context = $this->getNormalizedParam($params, 'context');

           $this->securityContext->setContext($context);

           $roles = $this->_explode($this->getParam($params, 'roles'));
           $permissions = $this->_explode($this->getParam($params, 'permissions'));

           if (! $this->securityContext->isGranted($roles, $permissions)) {

               $ex = new AuthenticationException(
                       sprintf("User not granted for roles '%s', permissions '%s' in context '%s'.",
                               implode(',', $roles), implode(',', $permissions), $context
                       )
               );

               $loginTpl = $this->getParam($params, 'login_tpl');

               if (null != $loginTpl) {
                   $ex->setLoginTemplate($loginTpl);
               }

               throw $ex;
           }

           return '';
     }

    /**
     * Define the various smarty plugins handled by this class
     *
     * @return an array of smarty plugin descriptors
     */
    public function getPluginDescriptors()
    {
        return array(
            new SmartyPluginDescriptor('function', 'check_auth', $this, 'checkAuthFunction')
        );
    }
}
