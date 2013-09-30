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
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use Thelia\Model\FolderQuery;
use Thelia\Type\BooleanOrBothType;


/**
 * Class FolderPath
 * @package Thelia\Core\Template\Loop
 * @author Manuel Raynaud <mraynaud@openstudio.fr>
 */
class FolderPath extends BaseI18nLoop
{

    /**
     *
     * define all args used in your loop
     *
     *
     * example :
     *
     * public function getArgDefinitions()
     * {
     *  return new ArgumentCollection(
     *       Argument::createIntListTypeArgument('id'),
     *           new Argument(
     *           'ref',
     *           new TypeCollection(
     *               new Type\AlphaNumStringListType()
     *           )
     *       ),
     *       Argument::createIntListTypeArgument('category'),
     *       Argument::createBooleanTypeArgument('new'),
     *       Argument::createBooleanTypeArgument('promo'),
     *       Argument::createFloatTypeArgument('min_price'),
     *       Argument::createFloatTypeArgument('max_price'),
     *       Argument::createIntTypeArgument('min_stock'),
     *       Argument::createFloatTypeArgument('min_weight'),
     *       Argument::createFloatTypeArgument('max_weight'),
     *       Argument::createBooleanTypeArgument('current'),
     *
     *   );
     * }
     *
     * @return \Thelia\Core\Template\Loop\Argument\ArgumentCollection
     */
    protected function getArgDefinitions()
    {
        return new ArgumentCollection(
            Argument::createIntTypeArgument('folder', null, true),
            Argument::createIntTypeArgument('depth'),
            Argument::createIntTypeArgument('level'),
            Argument::createBooleanOrBothTypeArgument('visible', true, false)
        );
    }

    /**
     *
     * this function have to be implement in your own loop class.
     *
     * All loops parameters can be accessible via getter.
     *
     * for example, ref parameter is accessible through getRef method
     *
     * @param $pagination
     *
     * @return mixed
     */
    public function exec(&$pagination)
    {
        $id = $this->getFolder();
        $visible = $this->getVisible();

        $search = FolderQuery::create();

        $locale = $this->configureI18nProcessing($search, array('TITLE'));

        $search->filterById($id);
        if ($visible != BooleanOrBothType::ANY) $search->filterByVisible($visible);

        $results = array();

        $ids = array();

        do {
            $folder = $search->findOne();

            if ($folder != null) {

                $loopResultRow = new LoopResultRow();

                $loopResultRow
                    ->set("TITLE",$folder->getVirtualColumn('i18n_TITLE'))
                    ->set("URL", $folder->getUrl($locale))
                    ->set("ID", $folder->getId())
                    ->set("LOCALE",$locale)
                ;

                $results[] = $loopResultRow;

                $parent = $folder->getParent();

                if ($parent > 0) {

                    // Prevent circular refererences
                    if (in_array($parent, $ids)) {
                        throw new \LogicException(sprintf("Circular reference detected in folder ID=%d hierarchy (folder ID=%d appears more than one times in path)", $id, $parent));
                    }

                    $ids[] = $parent;

                    $search = FolderQuery::create();

                    $this->configureI18nProcessing($search, array('TITLE'));

                    $search->filterById($parent);
                    if ($visible != BooleanOrBothType::ANY) $search->filterByVisible($visible);
                }
            }
        } while ($folder != null && $parent > 0);

        // Reverse list and build the final result
        $results = array_reverse($results);

        $loopResult = new LoopResult();

        foreach($results as $result) $loopResult->addRow($result);

        return $loopResult;
    }


}