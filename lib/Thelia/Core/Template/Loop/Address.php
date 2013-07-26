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
use Thelia\Core\Template\Element\BaseLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;

use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Log\Tlog;

use Thelia\Model\AddressQuery;
use Thelia\Model\ConfigQuery;
use Thelia\Type\TypeCollection;
use Thelia\Type;

/**
 *
 * Address loop
 *
 *
 * Class Address
 * @package Thelia\Core\Template\Loop
 * @author Etienne Roudeix <eroudeix@openstudio.fr>
 */
class Address extends BaseLoop
{
    /**
     * @return ArgumentCollection
     */
    protected function getArgDefinitions()
    {
        return new ArgumentCollection(
            Argument::createIntListTypeArgument('id'),
            new Argument(
                'customer',
                new TypeCollection(
                    new Type\IntType(),
                    new Type\EnumType(array('current'))
                ),
                'current'
            ),
            Argument::createBooleanTypeArgument('default'),
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
        $search = AddressQuery::create();

		$id = $this->getId();

        if (null !== $id) {
            $search->filterById($id, Criteria::IN);
        }

        $customer = $this->getCustomer();

        if ($customer === 'current') {
            $currentCustomer = $this->request->getSession()->getCustomerUser();
            if($currentCustomer === null) {
                return new LoopResult();
            } else {
                $search->filterByCustomerId($currentCustomer->getId(), Criteria::EQUAL);
            }
        } else {
            $search->filterByCustomerId($customer, Criteria::EQUAL);
        }

        $default = $this->getDefault();

        if ($default === true) {
            $search->filterByIsDefault(1, Criteria::EQUAL);
        } elseif($default === false) {
            $search->filterByIsDefault(1, Criteria::NOT_EQUAL);
        }

        $exclude = $this->getExclude();

        if (!is_null($exclude)) {
            $search->filterById($exclude, Criteria::NOT_IN);
        }

        $addresses = $this->search($search, $pagination);

        $loopResult = new LoopResult();

        foreach ($addresses as $address) {

            if ($this->not_empty && $address->countAllProducts() == 0) continue;

            $loopResultRow = new LoopResultRow();
            $loopResultRow->set("ID", $address->getId());
            $loopResultRow->set("NAME", $address->getName());
            $loopResultRow->set("CUSTOMER", $address->getCustomerId());
            $loopResultRow->set("TITLE", $address->getTitleId());
            $loopResultRow->set("COMPANY", $address->getCompany());
            $loopResultRow->set("FIRSTNAME", $address->getFirstname());
            $loopResultRow->set("LASTNAME", $address->getLastname());
            $loopResultRow->set("ADDRESS1", $address->getAddress1());
            $loopResultRow->set("ADDRESS2", $address->getAddress2());
            $loopResultRow->set("ADDRESS3", $address->getAddress3());
            $loopResultRow->set("ZIPCODE", $address->getZipcode());
            $loopResultRow->set("CITY", $address->getCity());
            $loopResultRow->set("COUNTRY", $address->getCountryId());
            $loopResultRow->set("PHONE", $address->getPhone());
            $loopResultRow->set("CELLPHONE", $address->getCellphone());
            $loopResultRow->set("DEFAULT", $address->getIsDefault());

            $loopResult->addRow($loopResultRow);
        }

        return $loopResult;
    }
}