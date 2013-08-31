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

use Thelia\Core\Template\Element\BaseI18nLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;

use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use Thelia\Core\Template\Loop\Argument\Argument;

use Thelia\Model\CategoryQuery;
use Thelia\Type;
use Thelia\Type\BooleanOrBothType;

/**
 *
 * Category path loop, to get the path to a given category.
 *
 * - category is the category id
 * - depth is the maximum depth to go, default unlimited
 * - level is the exact level to return. Example: if level = 2 and the path is c1 -> c2 -> c3 -> c4, the loop will return c2
 * - visible if true or missing, only visible categories will be displayed. If false, all categories (visible or not) are returned.
 *
 * example :
 *
 * <THELIA_cat type="category-path" category="3">
 *      <a href="#URL">#TITLE</a>
 * </THELIA_cat>
 *
 *
 * Class CategoryPath
 * @package Thelia\Core\Template\Loop
 * @author Franck Allimant <franck@cqfdev.fr>
 */
class CategoryPath extends BaseI18nLoop
{
    /**
     * @return ArgumentCollection
     */
    protected function getArgDefinitions()
    {
        return new ArgumentCollection(
            Argument::createIntTypeArgument('category', null, true),
            Argument::createIntTypeArgument('depth'),
            Argument::createIntTypeArgument('level'),
            Argument::createBooleanOrBothTypeArgument('visible', true, false)
        );
    }

    /**
     * @param $pagination (ignored)
     *
     * @return \Thelia\Core\Template\Element\LoopResult
     */
    public function exec(&$pagination)
    {
        $id = $this->getCategory();
        $visible = $this->getVisible();

        $search = CategoryQuery::create();

        $this->configureI18nProcessing($search, array('TITLE'));

        $search->filterById($id);
        if ($visible != BooleanOrBothType::ANY) $search->filterByVisible($visible);

        $results = array();

        $ids = array();

        do {
            $category = $search->findOne();

            if ($category != null) {

                $loopResultRow = new LoopResultRow();

                $loopResultRow
                    ->set("TITLE",$category->getVirtualColumn('i18n_TITLE'))
                    ->set("URL", $category->getUrl())
                    ->set("ID", $category->getId())
                ;

                $results[] = $loopResultRow;

                $parent = $category->getParent();

                if ($parent > 0) {

                    // Prevent circular refererences
                    if (in_array($parent, $ids)) {
                        throw new \LogicException(sprintf("Circular reference detected in category ID=%d hierarchy (category ID=%d appears more than one times in path)", $id, $parent));
                    }

                    $ids[] = $parent;

                    $search = CategoryQuery::create();

                    $this->configureI18nProcessing($search, array('TITLE'));

                    $search->filterById($parent);
                    if ($visible != BooleanOrBothType::ANY) $search->filterByVisible($visible);
                }
            }
        } while ($category != null && $parent > 0);

        // Reverse list and build the final result
        $results = array_reverse($results);

        $loopResult = new LoopResult();

        foreach($results as $result) $loopResult->addRow($result);

        return $loopResult;
    }
}
