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

use Thelia\Model\CategoryQuery;
use Thelia\Type\TypeCollection;
use Thelia\Type;
use Thelia\Type\BooleanOrBothType;
use Thelia\Model\ProductQuery;

/**
 *
 * Category loop, all params available :
 *
 * - id : can be an id (eq : 3) or a "string list" (eg: 3, 4, 5)
 * - parent : categories having this parent id
 * - current : current id is used if you are on a category page
 * - not_empty : if value is 1, category and subcategories must have at least 1 product
 * - visible : default 1, if you want category not visible put 0
 * - order : all value available :  'alpha', 'alpha_reverse', 'manual' (default), 'manual_reverse', 'random'
 * - exclude : all category id you want to exclude (as for id, an integer or a "string list" can be used)
 *
 * example :
 *
 * <THELIA_cat type="category" parent="3" limit="4">
 *      #TITLE : #ID
 * </THELIA_cat>
 *
 *
 * Class Category
 * @package Thelia\Core\Template\Loop
 * @author Manuel Raynaud <mraynaud@openstudio.fr>
 * @author Etienne Roudeix <eroudeix@openstudio.fr>
 */
class Category extends BaseI18nLoop
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
            Argument::createIntTypeArgument('parent'),
            Argument::createIntTypeArgument('product'),
            Argument::createIntTypeArgument('exclude_product'),
            Argument::createBooleanTypeArgument('current'),
            Argument::createBooleanTypeArgument('not_empty', 0),
            Argument::createBooleanOrBothTypeArgument('visible', 1),
            new Argument(
                'order',
                new TypeCollection(
                    new Type\EnumListType(array('id', 'id_reverse', 'alpha', 'alpha_reverse', 'manual', 'manual_reverse', 'visible', 'visible_reverse', 'random'))
                ),
                'manual'
            ),
            Argument::createIntListTypeArgument('exclude')
        );
    }

    /**
     * @param $pagination
     *
     * @return \Thelia\Core\Template\Element\LoopResult
     */
    public function exec(&$pagination)
    {
        $search = CategoryQuery::create();

        /* manage translations */
        $locale = $this->configureI18nProcessing($search);

        $id = $this->getId();

        if (!is_null($id)) {
            $search->filterById($id, Criteria::IN);
        }

        $parent = $this->getParent();

        if (!is_null($parent)) {
            $search->filterByParent($parent);
        }

        $current = $this->getCurrent();

        if ($current === true) {
            $search->filterById($this->request->get("category_id"));
        } elseif ($current === false) {
            $search->filterById($this->request->get("category_id"), Criteria::NOT_IN);
        }

         $exclude = $this->getExclude();

        if (!is_null($exclude)) {
            $search->filterById($exclude, Criteria::NOT_IN);
        }

        if ($this->getVisible() != BooleanOrBothType::ANY)
            $search->filterByVisible($this->getVisible() ? 1 : 0);

        $product = $this->getProduct();

        if ($product != null) {
            $obj = ProductQuery::create()->findPk($product);

            if ($obj != null) $search->filterByProduct($obj, Criteria::IN);
        }

        $exclude_product = $this->getExclude_product();

        if ($exclude_product != null) {
            $obj = ProductQuery::create()->findPk($exclude_product);

            if ($obj != null) $search->filterByProduct($obj, Criteria::NOT_IN);
        }

        $orders  = $this->getOrder();

        foreach ($orders as $order) {
            switch ($order) {
                case "id":
                    $search->orderById(Criteria::ASC);
                    break;
                case "id_reverse":
                    $search->orderById(Criteria::DESC);
                    break;
                case "alpha":
                    $search->addAscendingOrderByColumn('i18n_TITLE');
                    break;
                case "alpha_reverse":
                    $search->addDescendingOrderByColumn('i18n_TITLE');
                    break;
                case "manual_reverse":
                    $search->orderByPosition(Criteria::DESC);
                    break;
                case "manual":
                    $search->orderByPosition(Criteria::ASC);
                    break;
                case "visible":
                    $search->orderByVisible(Criteria::ASC);
                    break;
                case "visible_reverse":
                    $search->orderByVisible(Criteria::DESC);
                    break;
                case "random":
                    $search->clearOrderByColumns();
                    $search->addAscendingOrderByColumn('RAND()');
                    break(2);
                    break;
            }
        }

        /* perform search */
        $categories = $this->search($search, $pagination);

        /* @todo */
        $notEmpty  = $this->getNot_empty();

        $loopResult = new LoopResult($categories);

        foreach ($categories as $category) {

            // Find previous and next category
            $previous = CategoryQuery::create()
                ->filterByParent($category->getParent())
                ->filterByPosition($category->getPosition(), Criteria::LESS_THAN)
                ->orderByPosition(Criteria::DESC)
                ->findOne()
            ;

            $next = CategoryQuery::create()
                ->filterByParent($category->getParent())
                ->filterByPosition($category->getPosition(), Criteria::GREATER_THAN)
                ->orderByPosition(Criteria::ASC)
                ->findOne()
            ;

            /*
             * no cause pagination lost :
             * if ($this->getNotEmpty() && $category->countAllProducts() == 0) continue;
             */

            $loopResultRow = new LoopResultRow($loopResult, $category, $this->versionable, $this->timestampable, $this->countable);

            $loopResultRow
                ->set("ID", $category->getId())
                ->set("IS_TRANSLATED",$category->getVirtualColumn('IS_TRANSLATED'))
                ->set("LOCALE",$locale)
                ->set("TITLE", $category->getVirtualColumn('i18n_TITLE'))
                ->set("CHAPO", $category->getVirtualColumn('i18n_CHAPO'))
                ->set("DESCRIPTION", $category->getVirtualColumn('i18n_DESCRIPTION'))
                ->set("POSTSCRIPTUM", $category->getVirtualColumn('i18n_POSTSCRIPTUM'))
                ->set("PARENT", $category->getParent())
                ->set("URL", $category->getUrl($locale))
                ->set("PRODUCT_COUNT", $category->countAllProducts())
                ->set("CHILD_COUNT", $category->countChild())
                ->set("VISIBLE", $category->getVisible() ? "1" : "0")
                ->set("POSITION", $category->getPosition())

                ->set("HAS_PREVIOUS", $previous != null ? 1 : 0)
                ->set("HAS_NEXT"    , $next != null ? 1 : 0)

                ->set("PREVIOUS", $previous != null ? $previous->getId() : -1)
                ->set("NEXT"    , $next != null ? $next->getId() : -1)
                ;

            $loopResult->addRow($loopResultRow);
        }

        return $loopResult;
    }
}
