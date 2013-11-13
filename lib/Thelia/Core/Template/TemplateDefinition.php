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

namespace Thelia\Core\Template;

class TemplateDefinition
{
    const FRONT_OFFICE = 1;
    const BACK_OFFICE = 2;
    const PDF = 3;

    const FRONT_OFFICE_SUBDIR = 'frontOffice/';
    const BACK_OFFICE_SUBDIR = 'backOffice/';
    const PDF_SUBDIR = 'pdf/';

    /**
     * @var the template directory name (e.g. 'default')
     */
    protected $name;

    /**
     * @var the template directory full path
     */
    protected $path;

    /**
     * @var the template type (front, back, pdf)
     */
    protected $type;


    public function __construct($name, $type)
    {
        $this->name = $name;
        $this->type = $type;

        if ($type == self::BACK_OFFICE)
            $this->path = self::BACK_OFFICE_SUBDIR . $name;
        else if ($type == self::PDF)
            $this->path = self::PDF_SUBDIR . $name;
        else if ($type == self::FRONT_OFFICE)
            $this->path = self::FRONT_OFFICE_SUBDIR . $name;
        else
            $this->path = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function getI18nPath() {
        return $this->getPath() . DS . 'I18n';
    }

    public function getPath()
    {
        return $this->path;
    }

    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

}
