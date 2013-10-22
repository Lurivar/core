<?php
/*************************************************************************************/
/*                                                                                   */
/*      Thelia	                                                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*	    email : info@thelia.net                                                      */
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

use Thelia\Core\Template\Element\BaseLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;

use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Type\AlphaNumStringListType;
use Thelia\Type\EnumListType;
use Thelia\Type\TypeCollection;

/**
 *
 * @package Thelia\Core\Template\Loop
 *
 * @author Franck Allimant <franck@cqfdev.fr>
 */
class Auth extends BaseLoop
{
    public function getArgDefinitions()
    {
        return new ArgumentCollection(
            new Argument(
                'role',
                new TypeCollection(
                    new AlphaNumStringListType()
                ),
                null,
                true
            ),
            new Argument(
                'resource',
                new TypeCollection(
                    new AlphaNumStringListType()
                )
            ),
            new Argument(
                'access',
                new TypeCollection(
                    new EnumListType(array("view", "create", "update", "delete"))
                )
            ),
            Argument::createAnyTypeArgument('context', 'front', false)
         );
    }

    /**
     * @param $pagination
     *
     * @return LoopResult
     */
    public function exec(&$pagination)
    {
        $roles = $this->getRole();
        $resource = $this->getResource();
        $access = $this->getAccess();

        $loopResult = new LoopResult();

        try {
            if (true === $this->securityContext->isGranted($roles, $resource === null ? array() : $resource, $access === null ? array() : $access)) {

                // Create an empty row: loop is no longer empty :)
                $loopResult->addRow(new LoopResultRow());
            }
        } catch (\Exception $ex) {
            // Not granted, loop is empty
        }

        return $loopResult;
    }
}
