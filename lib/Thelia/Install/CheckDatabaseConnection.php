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

namespace Thelia\Install;

use PDO;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Translation\TranslatorInterface;
use Thelia\Core\Translation\Translator;
use Thelia\Install\Exception\InstallException;


/**
 * Class CheckDatabaseConnection
 *
 * Take care of integration tests (database connection)
 *
 * @package Thelia\Install
 * @author  Manuel Raynaud <mraynaud@openstudio.fr>
 * @author  Guillaume MOREL <gmorel@openstudio.fr>
 */
class CheckDatabaseConnection extends BaseInstall
{
    protected $validationMessages = array();

    /** @var bool If permissions are OK */
    protected $isValid = true;

    /** @var TranslatorInterface Translator Service */
    protected $translator = null;

    /** @var string Database host information  */
    protected $host = null;

    /** @var string Database user information  */
    protected $user = null;

    /** @var string Database password information  */
    protected $password = null;

    /** @var int Database port information  */
    protected $port = null;

    /**
     * Constructor
     *
     * @param string     $host          Database host information
     * @param string     $user          Database user information
     * @param string     $password      Database password information
     * @param int        $port          Database port information
     * @param bool       $verifyInstall If verify install
     * @param Translator $translator    Translator Service
     *                                  necessary for install wizard
     */
    public function __construct($host, $user, $password, $port, $verifyInstall = true, Translator $translator = null)
    {
        $this->host = $host;
        $this->user = $user;
        $this->password = $password;
        $this->port = $port;

        parent::__construct($verifyInstall);
    }

    /**
     * Perform database connection check
     *
     * @return bool
     */
    public function exec()
    {
        $link = mysql_connect($this->host . ':' . $this->port, $this->user, $this->password);
        if (!$link) {
            throw new InstallException('Can\'t connect to the given credentials');
        }
        mysql_close($link);
    }

}
