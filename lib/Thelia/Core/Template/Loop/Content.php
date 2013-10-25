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

namespace Thelia\Core\Template\Loop;

use Propel\Runtime\ActiveQuery\Criteria;
use Thelia\Core\Template\Element\BaseI18nLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;

use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use Thelia\Core\Template\Loop\Argument\Argument;

use Thelia\Model\FolderQuery;
use Thelia\Model\Map\ContentTableMap;
use Thelia\Model\ContentQuery;
use Thelia\Type\TypeCollection;
use Thelia\Type;
use Thelia\Type\BooleanOrBothType;

/**
 *
 * Content loop
 *
 *
 * Class Content
 * @package Thelia\Core\Template\Loop
 * @author Etienne Roudeix <eroudeix@openstudio.fr>
 */
class Content extends BaseI18nLoop
{
    public $timestampable = true;
    public $versionable = true;

    /**
     * @return ArgumentCollection
     */
    protected function getArgDefinitions()
    {
        return new ArgumentCollection(
            Argument::createIntListTypeArgument('id'),
            Argument::createIntListTypeArgument('folder'),
            Argument::createIntListTypeArgument('folder_default'),
            Argument::createBooleanTypeArgument('current'),
            Argument::createBooleanTypeArgument('current_folder'),
            Argument::createIntTypeArgument('depth', 1),
            Argument::createBooleanOrBothTypeArgument('visible', 1),
            new Argument(
                'order',
                new TypeCollection(
                    new Type\EnumListType(array('alpha', 'alpha-reverse', 'manual', 'manual_reverse', 'random', 'given_id'))
                ),
                'alpha'
            ),
            Argument::createIntListTypeArgument('exclude'),
            Argument::createIntListTypeArgument('exclude_folder')
        );
    }

   /**
     * @param $pagination
     *
     * @return LoopResult
     * @throws \InvalidArgumentException
     */
    public function exec(&$pagination)
    {

        $search = ContentQuery::create();

        /* manage translations */
        $locale = $this->configureI18nProcessing($search);

        $id = $this->getId();

        if (!is_null($id)) {
            $search->filterById($id, Criteria::IN);
        }

        $folder = $this->getFolder();
        $folderDefault = $this->getFolderDefault();

        if (!is_null($folder) || !is_null($folderDefault)) {

            $foldersIds = array();
            if (!is_array($folder)) {
                $folder = array();
            }
            if (!is_array($folderDefault)) {
                $folderDefault = array();
            }

            $foldersIds = array_merge($foldersIds, $folder, $folderDefault);
            $folders =FolderQuery::create()->filterById($foldersIds, Criteria::IN)->find();

            $depth = $this->getDepth();

            if (null !== $depth) {
                foreach (FolderQuery::findAllChild($folder, $depth) as $subFolder) {
                    $folders->prepend($subFolder);
                }
            }

            $search->filterByFolder(
                $folders,
                Criteria::IN
            );
        }

        $current = $this->getCurrent();

        if ($current === true) {
            $search->filterById($this->request->get("content_id"));
        } elseif ($current === false) {
            $search->filterById($this->request->get("content_id"), Criteria::NOT_IN);
        }

        $current_folder = $this->getCurrent_folder();

        if ($current_folder === true) {
            $current = ContentQuery::create()->findPk($this->request->get("content_id"));

            $search->filterByFolder($current->getFolders(), Criteria::IN);

        } elseif ($current_folder === false) {

            $current = ContentQuery::create()->findPk($this->request->get("content_id"));

            $search->filterByFolder($current->getFolders(), Criteria::NOT_IN);
        }

        $visible = $this->getVisible();

        if ($visible !== BooleanOrBothType::ANY) $search->filterByVisible($visible ? 1 : 0);


        $orders  = $this->getOrder();

        foreach ($orders as $order) {
            switch ($order) {
                case "alpha":
                    $search->addAscendingOrderByColumn('i18n_TITLE');
                    break;
                case "alpha-reverse":
                    $search->addDescendingOrderByColumn('i18n_TITLE');
                    break;
                case "manual":
                    if(null === $foldersIds || count($foldersIds) != 1)
                        throw new \InvalidArgumentException('Manual order cannot be set without single folder argument');
                    $search->orderByPosition(Criteria::ASC);
                    break;
                case "manual_reverse":
                    if(null === $foldersIds || count($foldersIds) != 1)
                        throw new \InvalidArgumentException('Manual order cannot be set without single folder argument');
                    $search->orderByPosition(Criteria::DESC);
                    break;
                case "given_id":
                    if(null === $id)
                        throw new \InvalidArgumentException('Given_id order cannot be set without `id` argument');
                    foreach ($id as $singleId) {
                        $givenIdMatched = 'given_id_matched_' . $singleId;
                        $search->withColumn(ContentTableMap::ID . "='$singleId'", $givenIdMatched);
                        $search->orderBy($givenIdMatched, Criteria::DESC);
                    }
                    break;
                case "random":
                    $search->clearOrderByColumns();
                    $search->addAscendingOrderByColumn('RAND()');
                    break(2);
            }
        }

        $exclude = $this->getExclude();

        if (!is_null($exclude)) {
            $search->filterById($exclude, Criteria::NOT_IN);
        }

        $exclude_folder = $this->getExclude_folder();

        if (!is_null($exclude_folder)) {
            $search->filterByFolder(
                FolderQuery::create()->filterById($exclude_folder, Criteria::IN)->find(),
                Criteria::NOT_IN
            );
        }

        /* perform search */
        $search->groupBy(ContentTableMap::ID);

        $contents = $this->search($search, $pagination);

        $loopResult = new LoopResult($contents);

        foreach ($contents as $content) {
            $loopResultRow = new LoopResultRow($loopResult, $content, $this->versionable, $this->timestampable, $this->countable);

            $loopResultRow->set("ID", $content->getId())
                ->set("IS_TRANSLATED",$content->getVirtualColumn('IS_TRANSLATED'))
                ->set("LOCALE",$locale)
                ->set("TITLE",$content->getVirtualColumn('i18n_TITLE'))
                ->set("CHAPO", $content->getVirtualColumn('i18n_CHAPO'))
                ->set("DESCRIPTION", $content->getVirtualColumn('i18n_DESCRIPTION'))
                ->set("POSTSCRIPTUM", $content->getVirtualColumn('i18n_POSTSCRIPTUM'))
                ->set("POSITION", $content->getPosition())
                ->set("DEFAULT_FOLDER", $content->getDefaultFolderId())
                ->set("URL", $content->getUrl($locale))
                ->set("VISIBLE", $content->getVisible())
            ;

            $loopResult->addRow($loopResultRow);
        }

        return $loopResult;
    }

}
