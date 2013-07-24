<?php
/*************************************************************************************/
/*                                                                                   */
/*      Thelia	                                                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*	email : info@thelia.net                                                      */
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
/*	    along with this program. If not, see <http://www.gnu.org/licenses/>.     */
/*                                                                                   */
/*************************************************************************************/

namespace Thelia\Tests\Type;

use Thelia\Type\IntToCombinedStringsListType;

/**
 *
 * @author Etienne Roudeix <eroudeix@openstudio.fr>
 *
 */
class IntToCombinedStringsListTypeTest extends \PHPUnit_Framework_TestCase
{
    public function testIntToCombinedStringsListType()
    {
        $type = new IntToCombinedStringsListType();
        $this->assertTrue($type->isValid('1: foo & bar | (fooo &baar), 4: *, 67: (foooo & baaar)'));
        $this->assertFalse($type->isValid('1,2,3'));
    }

    public function testFormatJsonType()
    {
        $type = new IntToCombinedStringsListType();
        $this->assertEquals(
            $type->getFormattedValue('1: foo & bar | (fooo &baar), 4: *, 67: (foooo & baaar)'),
            array(
                1 => array(
                    "values" => array('foo','bar','fooo','baar'),
                    "expression" => 'foo&bar|(fooo&baar)',
                ),
                4 => array(
                    "values" => array('*'),
                    "expression" => '*',
                ),
                67 => array(
                    "values" => array('foooo','baaar'),
                    "expression" => '(foooo&baaar)',
                ),
            )
        );
        $this->assertNull($type->getFormattedValue('foo'));
    }
}
