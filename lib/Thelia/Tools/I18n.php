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

namespace Thelia\Tools;

use Thelia\Model\Lang;

/**
 * Created by JetBrains PhpStorm.
 * Date: 8/19/13
 * Time: 3:24 PM
 *
 * Helper for translations
 *
 * @package I18n
 * @author  Guillaume MOREL <gmorel@openstudio.fr>
 *
 */
class I18n
{
    /**
     * Create a \DateTime from a date picker form input
     * The date format is the same as the one from the current User Session
     * Ex : $lang = $session->getLang()
     *
     * @param Lang   $lang Object containing date format
     * @param string $date String to convert
     *
     * @return \DateTime
     */
    public function getDateTimeFromForm(Lang $lang, $date)
    {
        $currentDateFormat = $lang->getDateFormat();

        return \DateTime::createFromFormat($currentDateFormat, $date);
    }

}
