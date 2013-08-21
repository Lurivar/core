<?php
/**********************************************************************************/
/*                                                                                */
/*      Thelia	                                                                  */
/*                                                                                */
/*      Copyright (c) OpenStudio                                                  */
/*      email : info@thelia.net                                                   */
/*      web : http://www.thelia.net                                               */
/*                                                                                */
/*      This program is free software; you can redistribute it and/or modify      */
/*      it under the terms of the GNU General Public License as published by      */
/*      the Free Software Foundation; either version 3 of the License             */
/*                                                                                */
/*      This program is distributed in the hope that it will be useful,           */
/*      but WITHOUT ANY WARRANTY; without even the implied warranty of            */
/*      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the             */
/*      GNU General Public License for more details.                              */
/*                                                                                */
/*      You should have received a copy of the GNU General Public License         */
/*	    along with this program. If not, see <http://www.gnu.org/licenses/>.      */
/*                                                                                */
/**********************************************************************************/

namespace Thelia\Coupon\Rule;

/**
 * Created by JetBrains PhpStorm.
 * Date: 8/19/13
 * Time: 3:24 PM
 *
 * @package Coupon
 * @author  Guillaume MOREL <gmorel@openstudio.fr>
 *
 */
class AvailableForRepeatedPeriod extends AvailableForPeriod
{
    /**
     * Generate current Rule validator from adapter
     *
     * @param CouponAdapterInterface $adapter allowing to gather
     *                               all necessary Thelia variables
     *
     * @throws \Symfony\Component\Intl\Exception\NotImplementedException
     * @return $this
     */
    protected function setValidators(CouponAdapterInterface $adapter)
    {
        parent::setValidators($adapter); // TODO: Change the autogenerated stub
    }

    /**
     * Generate current Rule param to be validated from adapter
     *
     * @param CouponAdapterInterface $adapter allowing to gather
     *                               all necessary Thelia variables
     *
     * @throws \Symfony\Component\Intl\Exception\NotImplementedException
     * @return $this
     */
    protected function setParametersToValidate(CouponAdapterInterface $adapter)
    {
        parent::setParametersToValidate($adapter); // TODO: Change the autogenerated stub
    }
}