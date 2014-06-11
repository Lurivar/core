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

namespace Thelia\Model\Breadcrumb;


use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Router;
use Thelia\Core\Template\Loop\FolderPath;
use Thelia\Core\Translation\Translator;

trait FolderBreadcrumbTrait
{

    public function getBaseBreadcrumb(Router $router, ContainerInterface $container, $folderId, &$locale)
    {
        $translator = Translator::getInstance();
        $catalogUrl = $router->generate('admin.catalog', [], Router::ABSOLUTE_URL);
        $breadcrumb = [
            $translator->trans('Home', [], 'bo.default') => $router->generate('admin.home.view', [], Router::ABSOLUTE_URL),
            $translator->trans('Folder', [], 'bo.default') => $catalogUrl,
        ];

        $folderPath = new FolderPath($container);
        $folderPath->initializeArgs([
                'folder' => $folderId,
                'visible' => '*'
            ]);

        $results = $folderPath->buildArray();

        foreach ($results as $result) {
            $breadcrumb[$result['TITLE']] = sprintf("%s?folder_id=%d",
                $router->generate('admin.folders.default',[], Router::ABSOLUTE_URL),
                $result['ID']
            );
        }

        $locale = $result['LOCALE'];

        return $breadcrumb;
    }

    public function getFolderBreadcrumb($router, $container, $tab)
    {
        $locale = null;
        $folder = $this->getFolder();
        $breadcrumb = $this->getBaseBreadcrumb($router, $container, $this->getParentId(), $locale);

        $folder->setLocale($locale);

        $breadcrumb[$folder->getTitle()] = sprintf("%s?current_tab=%s",
            $router->generate('admin.folders.update',['folder_id' => $folder->getId()], Router::ABSOLUTE_URL),
            $tab
        );

        return $breadcrumb;
    }

    public function getContentBreadcrumb(Router $router, ContainerInterface $container, $tab)
    {
        $content = $this->getContent();
        $locale = null;

        $breadcrumb = $this->getBaseBreadcrumb($router, $container, $content->getDefaultFolderId(), $locale);

        $content->setLocale($locale);

        $breadcrumb[$content->getTitle()] = sprintf("%s?current_tab=%s",
            $router->generate('admin.content.update', ['content_id' => $content->getId()], Router::ABSOLUTE_URL),
            $tab
        );

        return $breadcrumb;
    }
}