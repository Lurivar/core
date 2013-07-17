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

namespace Thelia\Tests\Type;

use Thelia\Type\AlphaNumStringListType;

/**
 *
 * @author Etienne Roudeix <eroudeix@openstudio.fr>
 *
 */
class AlphaNumStringListTypeTest extends \PHPUnit_Framework_TestCase
{
    public function testAlphaNumStringListType()
    {
        $type = new AlphaNumStringListType();
        $this->assertTrue($type->isValid('FOO1,FOO_2,FOO-3'));
        $this->assertFalse($type->isValid('FOO.1,FOO$_2,FOO-3'));
    }

    public function testFormatAlphaNumStringListType()
    {
        $type = new AlphaNumStringListType();
        $this->assertTrue(is_array($type->getFormattedValue('FOO1,FOO_2,FOO-3')));
        $this->assertNull($type->getFormattedValue('5€'));
    }
}
