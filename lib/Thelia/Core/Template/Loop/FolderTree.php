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
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;

use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use Thelia\Core\Template\Loop\Argument\Argument;

use Thelia\Model\FolderQuery;
use Thelia\Type;
use Thelia\Type\BooleanOrBothType;
use Thelia\Core\Template\Element\BaseI18nLoop;

/**
 *
 * Folder tree loop, to get a folder tree from a given folder to a given depth.
 *
 * - folder is the folder id
 * - depth is the maximum depth to go, default unlimited
 * - visible if true or missing, only visible categories will be displayed. If false, all categories (visible or not) are returned.
 *
 * @package Thelia\Core\Template\Loop
 * @author Franck Allimant <franck@cqfdev.fr>
 */
class FolderTree extends BaseI18nLoop
{
    /**
     * @return ArgumentCollection
     */
    protected function getArgDefinitions()
    {
        return new ArgumentCollection(
                Argument::createIntTypeArgument('folder', null, true),
                Argument::createIntTypeArgument('depth', PHP_INT_MAX),
                Argument::createBooleanOrBothTypeArgument('visible', true, false),
                Argument::createIntListTypeArgument('exclude', array())
        );
    }

    // changement de rubrique
    protected function buildFolderTree($parent, $visible, $level, $max_level, $exclude, LoopResult &$loopResult)
    {
        if ($level > $max_level) return;

        $search = FolderQuery::create();

        $locale = $this->configureI18nProcessing($search, array(
                    'TITLE'
                ));

        $search->filterByParent($parent);

        if ($visible != BooleanOrBothType::ANY) $search->filterByVisible($visible);

        if ($exclude != null) $search->filterById($exclude, Criteria::NOT_IN);

        $search->orderByPosition(Criteria::ASC);

        $results = $search->find();

        foreach ($results as $result) {

            $loopResultRow = new LoopResultRow();

            $loopResultRow
                ->set("ID", $result->getId())->set("TITLE", $result->getVirtualColumn('i18n_TITLE'))
                ->set("PARENT", $result->getParent())->set("URL", $result->getUrl($locale))
                ->set("VISIBLE", $result->getVisible() ? "1" : "0")->set("LEVEL", $level)
            ;

            $loopResult->addRow($loopResultRow);

            $this->buildFolderTree($result->getId(), $visible, 1 + $level, $max_level, $exclude, $loopResult);
        }
    }

    /**
     * @param $pagination (ignored)
     *
     * @return \Thelia\Core\Template\Element\LoopResult
     */
    public function exec(&$pagination)
    {
        $id = $this->getFolder();
        $depth = $this->getDepth();
        $visible = $this->getVisible();
        $exclude = $this->getExclude();

        $loopResult = new LoopResult();

        $this->buildFolderTree($id, $visible, 0, $depth, $exclude, $loopResult);

        return $loopResult;
    }
}
