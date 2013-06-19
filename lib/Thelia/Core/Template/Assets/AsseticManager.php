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

namespace Thelia\Core\Template\Assets;

use Assetic\AssetManager;
use Assetic\FilterManager;
use Assetic\Filter;
use Assetic\Factory\AssetFactory;
use Assetic\Factory\Worker\CacheBustingWorker;
use Assetic\AssetWriter;
use Assetic\Asset\AssetCache;
use Assetic\Cache\FilesystemCache;

class AsseticManager {

    protected $options;

    public function __construct($_options = array()) {

        $this->options = $_options;
    }

    /**
     * Generates assets from $asset_path in $output_path, using $filters.
     *
     * @param string $asset_path the full path to the asset file (or file collection)
     * @param unknown $output_path the full disk path to the output directory (shoud be visible to web server)
     * @param unknown $output_url the URL to the generated asset directory
     * @param unknown $asset_type the asset type: css, js, ... The generated files will have this extension. Pass an empty string to use the asset source extension.
     * @param unknown $filters a list of filters, as defined below (see switch($filter_name) ...)
     * @param unknown $debug true / false
     * @throws \Exception
     * @return string The URL to the generated asset file.
     */
    public function asseticize($asset_path, $output_path, $output_url, $asset_type, $filters, $debug) {

        $asset_name = basename($asset_path);
        $asset_dir = dirname($asset_path);

        $am = new AssetManager();
        $fm = new FilterManager();

        if (! empty($filters)) {
            $filter_list = explode(',', $filters);

            foreach($filter_list as $filter_name) {

                $filter_name = trim($filter_name);

                switch($filter_name) {
                    case 'less' :
                        $fm->set('less', new Filter\LessphpFilter());
                        break;

                    case 'sass' :
                        $fm->set('less', new Filter\Sass\SassFilter());
                        break;

                    case 'cssembed' :
                        $fm->set('cssembed', new Filter\PhpCssEmbedFilter());
                        break;

                    case 'cssrewrite':
                        $fm->set('cssrewrite', new Filter\CssRewriteFilter());
                        break;

                    case 'cssimport':
                        $fm->set('cssimport', new Filter\CssImportFilter());
                        break;

                    default :
                        throw new \Exception("Unsupported Assetic filter: '$filter_name'");
                        break;
                }
            }
        }
        else {
            $filter_list = array();
        }

        // Factory setup
        $factory = new AssetFactory($asset_dir);

        $factory->setAssetManager($am);
        $factory->setFilterManager($fm);

        $factory->setDefaultOutput('*'.(! empty($asset_type) ? '.' : '').$asset_type);

        $factory->setDebug($debug);

        $factory->addWorker(new CacheBustingWorker());

        // Prepare the assets writer
        $writer = new AssetWriter($output_path);

        $asset = $factory->createAsset($asset_name, $filter_list);

        $cache = new AssetCache($asset, new FilesystemCache($output_path));

        $writer->writeAsset($cache);

        return rtrim($output_url, '/').'/'.$asset->getTargetPath();
    }
}