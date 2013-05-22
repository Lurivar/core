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

namespace Thelia\Core\Factory;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Thelia\Core\Event\ActionEventClass;

class ActionEventFactory
{

    protected $request;
    protected $action;

    /**
     * @todo : delegate to config for creating associating value
     *
     * @var array
     */
    protected $className;

    protected $defaultClassName = "Thelia\Core\Event\DefaultActionEvent";

    public function __construct(Request $request, $action, $className)
    {
        $this->request = $request;
        $this->action = $action;
        $this->className = $className;
    }

    public function createActionEvent()
    {
        $class = null;
        if (array_key_exists($this->action, $this->className)) {
            $class = new \ReflectionClass($this->className[$this->action]);

            // return $class->newInstance($this->request, $this->action);
        } else {
            $class = new \ReflectionClass($this->defaultClassName);
        }

        if ($class->isSubclassOf("Thelia\Core\Event\ActionEvent") === false) {
            throw new \RuntimeException("%s must be a subclass of Thelia\Core\Event\ActionEvent", $class->getName());
        }

        return $class->newInstance($this->request, $this->action);
    }
}