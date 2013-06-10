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
/*	    along with this program. If not, see <http://www.gnu.org/licenses/>.     */
/*                                                                                   */
/*************************************************************************************/
namespace Thelia\Admin;

use \Smarty;

// smarty configuration
class Template extends Smarty
{
    function __construct()
    {
        parent::__construct();

        $compile_dir = THELIA_LOCAL_DIR . 'cache/smarty/compile';
        if (! is_dir($compile_dir)) @mkdir($compile_dir, 0777, true);

        $cache_dir = THELIA_LOCAL_DIR . 'cache/smarty/cache';
        if (! is_dir($cache_dir)) @mkdir($cache_dir, 0777, true);

        $this->setTemplateDir(__DIR__.'/template/');
        $this->setCompileDir($compile_dir);
        $this->setCacheDir($cache_dir);

        /*
        $this->setConfigDir(GUESTBOOK_DIR . 'configs');
        $this->setCacheDir(GUESTBOOK_DIR . 'cache');
        */
    }

    function render($templateName, $parameters) {

        $realTemplateName = $templateName . '.tpl';

         /**
         * Assign variables/objects to the templates.
         *
         * Description
         *  void assign(mixed var);
         *  void assign(string varname, mixed var, bool nocache);
         *
         * You can explicitly pass name/value pairs, or associative arrays
         * containing the name/value pairs.
         *
         * If you pass the optional third nocache parameter of TRUE, the
         * variable is assigned as nocache variable. See {@link http://www.smarty.net/docs/en/caching.cacheable.tpl#cacheability.variables} for details.
         *
         * Too learn more see {@link http://www.smarty.net/docs/en/api.assign.tpl}
         */
         $this->smarty->assign($parameters);

        /**
         * This returns the template output instead of displaying it. Supply a
         * valid template resource type and path. As an optional second
         * parameter, you can pass a $cache id, see the caching section for more
         * information.
         *
         * As an optional third parameter, you can pass a $compile_id. This is
         * in the event that you want to compile different versions of the same
         * template, such as having separate templates compiled for different
         * languages. You can also set the $compile_id variable once instead of
         * passing this to each call to this function.
         *
         * Too learn more see {@link http://www.smarty.net/docs/en/api.fetch.tpl}
         */

         return $this->smarty->fetch($realTemplateName);
     }
}
?>