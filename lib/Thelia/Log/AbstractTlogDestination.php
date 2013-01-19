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

namespace Thelia\Log;

abstract class AbstractTlogDestination
{
    //Tableau de TlogDestinationConfig paramétrant la destination
    protected $_configs;

    //Tableau des lignes de logs stockés avant utilisation par ecrire()
    protected $_logs;

    // Vaudra true si on est dans le back office.
    protected $flag_back_office = false;

    public function __construct()
    {
        $this->_configs = array();
        $this->_logs = array();
        
        // Initialiser les variables de configuration
         $this->_configs = $this->getConfigs();

         // Appliquer la configuration
         $this->configure();
    }

    //Affecte une valeur à une configuration de la destination
    public function setConfig($name, $value)
    {
        foreach ($this->_configs as $config) {
            if ($config->name == $name) {
                $config->value = $value;
                // Appliquer les changements
                $this->configure();

                return true;
            }
        }

        return false;
    }

    //Récupère la valeur affectée à une configuration de la destination
    public function getConfig($name)
    {
        foreach ($this->_configs as $config) {
            if ($config->name == $name) {
                return $config->value;
            }
        }

        return false;
    }

    public function getConfigs()
    {
        return $this->_configs;
    }

    public function SetBackOfficeMode($bool)
    {
            $this->flag_back_office = $bool;
    }

    //Ajoute une ligne de logs à la destination
    public function add($string)
    {
        $this->_logs[] = $string;
    }

    protected function InsertAfterBody(&$res, $logdata)
    {
            $match = array();

            if (preg_match("/(<body[^>]*>)/i", $res, $match)) {
                    $res = str_replace($match[0], $match[0] . "\n" . $logdata, $res);
            } else {
                    $res = $logdata . $res;
            }
    }

    // Demande à la destination de se configurer pour être prête
    // a fonctionner. Si $config est != false, celà indique
    // que seul le paramètre de configuration indiqué a été modifié.
    protected function configure()
    {
            // Cette methode doit etre surchargée si nécessaire.
    }

    //Lance l'écriture de tous les logs par la destination
    //$res : contenu de la page html
    abstract public function write(&$res);

    // Retourne le titre de cette destination, tel qu'affiché dans le menu de selection
    abstract public function getTitle();

    // Retourne une brève description de la destination
    abstract public function getDescription();
}
