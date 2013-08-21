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

namespace Thelia\Coupon\Type;

use Thelia\Coupon\Type\CouponAbstract;

/**
 * Created by JetBrains PhpStorm.
 * Date: 8/19/13
 * Time: 3:24 PM
 *
 * Allow to remove an amount from the checkout total
 *
 * @package Coupon
 * @author  Guillaume MOREL <gmorel@openstudio.fr>
 *
 */
class RemoveXAmount extends CouponAbstract
{
    /**
     * Constructor
     *
     * @param string $code              Coupon code (ex: XMAS)
     * @param string $title             Coupon title (ex: Coupon for XMAS)
     * @param string $shortDescription  Coupon short description
     * @param string $description       Coupon description
     * @param float  $amount            Coupon amount to deduce
     * @param bool   $isCumulative      if Coupon is cumulative
     * @param bool   $isRemovingPostage if Coupon is removing postage
     */
    function __construct($code, $title, $shortDescription, $description, $amount, $isCumulative, $isRemovingPostage)
    {
        $this->code = $code;
        $this->title = $title;
        $this->shortDescription = $shortDescription;
        $this->description = $description;

        $this->isCumulative = $isCumulative;
        $this->isRemovingPostage = $isRemovingPostage;

        $this->amount = $amount;
    }

}
