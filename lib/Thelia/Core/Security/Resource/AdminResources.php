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

namespace Thelia\Core\Security\Resource;

use Thelia\Core\Security\Exception\ResourceException;

/**
 *
 * This class contains all Thelia admin resources
 *
 * @author Etienne roudeix <eroudeix@openstudio.fr>
 */
class AdminResources
{
    const SUPERADMINISTRATOR = "SUPERADMINISTRATOR";

    const ADDRESS = "admin.address";

    const ADMINISTRATOR = "admin.configuration.administrator";

    const ADVANCED_CONFIGURATION = "admin.configuration.advanced";

    const AREA = "admin.configuration.area";

    const ATTRIBUTE = "admin.configuration.attribute";

    const BRAND = "admin.brand";

    const CATEGORY = "admin.category";

    const CONFIG = "admin.configuration";

    const CONTENT = "admin.content";

    const COUNTRY = "admin.configuration.country";

    const STATE = "admin.configuration.state";

    const COUPON = "admin.coupon";

    const CURRENCY = "admin.configuration.currency";

    const CUSTOMER = "admin.customer";

    const FEATURE = "admin.configuration.feature";

    const FOLDER = "admin.folder";

    const HOME = "admin.home";

    const LANGUAGE = "admin.configuration.language";

    const MAILING_SYSTEM = "admin.configuration.mailing-system";

    const MESSAGE = "admin.configuration.message";

    const MODULE = "admin.module";

    const HOOK = "admin.hook";

    const MODULE_HOOK = "admin.module-hook";

    const ORDER = "admin.order";

    const PRODUCT = "admin.product";

    const PROFILE = "admin.configuration.profile";

    const SHIPPING_ZONE = "admin.configuration.shipping-zone";

    const TAX = "admin.configuration.tax";

    const TEMPLATE = "admin.configuration.template";

    const SYSTEM_LOG = "admin.configuration.system-logs";

    const ADMIN_LOG = "admin.configuration.admin-logs";

    const STORE = "admin.configuration.store";

    const TRANSLATIONS = "admin.configuration.translations";

    const UPDATE = "admin.configuration.update";

    const EXPORT = "admin.export";

    const IMPORT = "admin.import";

    const TOOLS = "admin.tools";

    const SALES = "admin.sales";

    const API = "admin.configuration.api";

    const TITLE = "admin.customer.title";

    /**
     * Stock all resources by modules
     * Exemple :
     * [
     *      "thelia" => [
     *          "ADDRESS" => "admin.address",
     *          ...
     *      ],
     *      "Front" => [
     *          ...
     *      ]
     * ]
     * @var Array $resources
     */
    protected $resources;

    /**
     * Create a new AdminRessources instance.
     *
     * @param array $resources with format module => [ KEY => value ].
     */
    public function __construct($resources)
    {
        $this->resources = $resources;
    }

    /**
     * @param string $name
     * @param string $module
     * @return string
     */
    public function getResource($name, $module = "thelia")
    {
        $constantName = strtoupper($name);

        if (isset($this->resources[$module])) {
            if (isset($this->resources[$module][$constantName])) {
                return $this->resources[$module][$constantName];
            } else {
                throw new ResourceException(sprintf('Resource `%s` not found', $module),
                    ResourceException::RESOURCE_NOT_FOUND);
            }
        } else {
            throw new ResourceException(sprintf('Module `%s` not found', $module),
                ResourceException::RESOURCE_NOT_FOUND);
        }
    }
}
