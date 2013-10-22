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

use Thelia\Model\ProfileQuery;
use Thelia\Type;
use Thelia\Type\BooleanOrBothType;

/**
 *
 * Profile loop
 *
 *
 * Class Profile
 * @package Thelia\Core\Template\Loop
 * @author Etienne Roudeix <eroudeix@openstudio.fr>
 */
class Profile extends BaseI18nLoop
{
    public $timestampable = true;

    /**
     * @return ArgumentCollection
     */
    protected function getArgDefinitions()
    {
        return new ArgumentCollection(
            Argument::createIntListTypeArgument('id')
        );
    }

    /**
     * @param $pagination
     *
     * @return \Thelia\Core\Template\Element\LoopResult
     */
    public function exec(&$pagination)
    {
        $search = ProfileQuery::create();

        /* manage translations */
        $locale = $this->configureI18nProcessing($search);

        $id = $this->getId();

        if (null !== $id) {
            $search->filterById($id, Criteria::IN);
        }

        $search->orderById(Criteria::ASC);

        /* perform search */
        $profiles = $this->search($search, $pagination);

        $loopResult = new LoopResult($profiles);

        foreach ($profiles as $profile) {
            $loopResultRow = new LoopResultRow($loopResult, $profile, $this->versionable, $this->timestampable, $this->countable);
            $loopResultRow->set("ID", $profile->getId())
                ->set("IS_TRANSLATED",$profile->getVirtualColumn('IS_TRANSLATED'))
                ->set("LOCALE",$locale)
                ->set("CODE",$profile->getCode())
                ->set("TITLE",$profile->getVirtualColumn('i18n_TITLE'))
                ->set("CHAPO", $profile->getVirtualColumn('i18n_CHAPO'))
                ->set("DESCRIPTION", $profile->getVirtualColumn('i18n_DESCRIPTION'))
                ->set("POSTSCRIPTUM", $profile->getVirtualColumn('i18n_POSTSCRIPTUM'))
            ;

            $loopResult->addRow($loopResultRow);
        }

        return $loopResult;
    }
}
