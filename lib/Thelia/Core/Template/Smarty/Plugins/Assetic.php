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
use Thelia\Core\Template\Smarty\Assets\SmartyAssetsManager;
use Thelia\Model\ConfigQuery;

class Assetic extends AbstractSmartyPlugin
{
    public $assetManager;

    public function __construct()
    {
        $web_root  = THELIA_WEB_DIR;

        $asset_dir_from_web_root = ConfigQuery::read('asset_dir_from_web_root', 'assets');

        $this->assetManager = new SmartyAssetsManager($web_root, $asset_dir_from_web_root);
    }

    public function blockJavascripts($params, $content, \Smarty_Internal_Template $template, &$repeat)
    {
        return $this->assetManager->processSmartyPluginCall('js', $params, $content, $template, $repeat);
    }

    public function blockImages($params, $content, \Smarty_Internal_Template $template, &$repeat)
    {
        return $this->assetManager->processSmartyPluginCall(SmartyAssetsManager::ASSET_TYPE_AUTO, $params, $content, $template, $repeat);
    }

    public function blockStylesheets($params, $content, \Smarty_Internal_Template $template, &$repeat)
    {
        return $this->assetManager->processSmartyPluginCall('css', $params, $content, $template, $repeat);
    }

    public function functionImage($params, \Smarty_Internal_Template $template)
    {
        return $this->assetManager->computeAssetUrl(SmartyAssetsManager::ASSET_TYPE_AUTO, $params, $template);
    }

    /**
     * Define the various smarty plugins hendled by this class
     *
     * @return an array of smarty plugin descriptors
     */
    public function getPluginDescriptors()
    {
        return array(
            new SmartyPluginDescriptor('block'   , 'stylesheets', $this, 'blockStylesheets'),
            new SmartyPluginDescriptor('block'   , 'javascripts', $this, 'blockJavascripts'),
            new SmartyPluginDescriptor('block'   , 'images'     , $this, 'blockImages'),
            new SmartyPluginDescriptor('function', 'image'      , $this, 'functionImage')
        );
    }
}
