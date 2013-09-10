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
/*	    along with this program. If not, see <http://www.gnu.org/licenses/>.         */
/*                                                                                   */
/*************************************************************************************/

namespace Thelia\Core;

/**
 * Root class of Thelia
 *
 * It extends Symfony\Component\HttpKernel\Kernel for changing some features
 *
 *
 * @author Manuel Raynaud <mraynaud@openstudio.fr>
 */

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Validator\Tests\Fixtures\Reference;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

use Thelia\Core\Bundle;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Log\Tlog;
use Thelia\Config\DatabaseConfiguration;
use Thelia\Config\DefinePropel;
use Thelia\Core\TheliaContainerBuilder;
use Thelia\Core\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Config\FileLocator;

use Propel\Runtime\Propel;
use Propel\Runtime\Connection\ConnectionManagerSingle;

class Thelia extends Kernel
{

    const THELIA_VERSION = 0.1;

    protected $tpexConfig;

    public function init()
    {
        parent::init();
        if ($this->debug) {
            ini_set('display_errors', 1);
        }
        $this->initPropel();
    }

    protected function initPropel()
    {
        if (file_exists(THELIA_ROOT . '/local/config/database.yml') === false) {
            return ;
        }

        $definePropel = new DefinePropel(new DatabaseConfiguration(),
            Yaml::parse(THELIA_ROOT . '/local/config/database.yml'));
        $serviceContainer = Propel::getServiceContainer();
        $serviceContainer->setAdapterClass('thelia', 'mysql');
        $manager = new ConnectionManagerSingle();
        $manager->setConfiguration($definePropel->getConfig());
        $serviceContainer->setConnectionManager('thelia', $manager);

        if ($this->isDebug()) {
            $serviceContainer->setLogger('defaultLogger', Tlog::getInstance());
            $con = Propel::getConnection(\Thelia\Model\Map\ProductTableMap::DATABASE_NAME);
            $con->useDebug(true);
        }
    }

    /**
     * dispatch an event when application is boot
     */
    public function boot()
    {
        parent::boot();

        $this->getContainer()->get("event_dispatcher")->dispatch(TheliaEvents::BOOT);
    }

    /**
     *
     * Load some configuration
     * Initialize all plugins
     *
     */
    protected function loadConfiguration(ContainerBuilder $container)
    {

        $loader = new XmlFileLoader($container, new FileLocator(THELIA_ROOT . "/core/lib/Thelia/Config/Resources"));
        $loader->load("config.xml");
        $loader->load("routing.xml");
        $loader->load("action.xml");
        if (defined("THELIA_INSTALL_MODE") === false) {
            $modules = \Thelia\Model\ModuleQuery::getActivated();

            foreach ($modules as $module) {

                try {

                    $defintion = new Definition();
                    $defintion->setClass($module->getFullNamespace());
                    $defintion->addMethodCall("setContainer", array('service_container'));

                    $container->setDefinition(
                        "module.".$module->getCode(),
                        $defintion
                    );

                    $loader = new XmlFileLoader($container, new FileLocator(THELIA_MODULE_DIR . "/" . ucfirst($module->getCode()) . "/Config"));
                    $loader->load("config.xml");
                } catch (\InvalidArgumentException $e) {
                    // FIXME: process module configuration exception
                }
            }
        }
    }

    /**
     *
     * initialize session in Request object
     *
     * All param must be change in Config table
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     */

    /**
     * Gets a new ContainerBuilder instance used to build the service container.
     *
     * @return ContainerBuilder
     */
    protected function getContainerBuilder()
    {
        return new TheliaContainerBuilder(new ParameterBag($this->getKernelParameters()));
    }

    /**
     * Builds the service container.
     *
     * @return ContainerBuilder The compiled service container
     *
     * @throws \RuntimeException
     */
    protected function buildContainer()
    {
        $container = parent::buildContainer();

        $this->loadConfiguration($container);
        $container->customCompile();

        return $container;
    }

    /**
     * Gets the cache directory.
     *
     * @return string The cache directory
     *
     * @api
     */
    public function getCacheDir()
    {
        if (defined('THELIA_ROOT')) {
            return THELIA_ROOT.'cache/'.$this->environment;
        } else {
            return parent::getCacheDir();
        }

    }

    /**
     * Gets the log directory.
     *
     * @return string The log directory
     *
     * @api
     */
    public function getLogDir()
    {
        if (defined('THELIA_ROOT')) {
            return THELIA_ROOT.'log/';
        } else {
            return parent::getLogDir();
        }
    }

    /**
     * Returns the kernel parameters.
     *
     * @return array An array of kernel parameters
     */
    protected function getKernelParameters()
    {
        $parameters = parent::getKernelParameters();

        $parameters["thelia.root_dir"] = THELIA_ROOT;
        $parameters["thelia.core_dir"] = THELIA_ROOT . "core/lib/Thelia";
        $parameters["thelia.module_dir"] = THELIA_MODULE_DIR;

        return $parameters;
    }

    /**
     * return available bundle
     *
     * Part of Symfony\Component\HttpKernel\KernelInterface
     *
     * @return array An array of bundle instances.
     *
     */
    public function registerBundles()
    {
        $bundles = array(
            /* TheliaBundle contain all the dependency injection description */
            new Bundle\TheliaBundle(),
        );

        /**
         * OTHER CORE BUNDLE CAN BE DECLARE HERE AND INITIALIZE WITH SPECIFIC CONFIGURATION
         *
         * HOW TO DECLARE OTHER BUNDLE ? ETC
         */

        return $bundles;

    }

    /**
     * Loads the container configuration
     *
     * part of Symfony\Component\HttpKernel\KernelInterface
     *
     * @param LoaderInterface $loader A LoaderInterface instance
     *
     * @api
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        //Nothing is load here but it's possible to load container configuration here.
        //exemple in sf2 : $loader->load(__DIR__.'/config/config_'.$this->getEnvironment().'.yml');
    }

}
