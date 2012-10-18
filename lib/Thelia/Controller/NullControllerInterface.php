<?php

namespace Thelia\Controller;

use Symfony\Component\HttpFoundation\Request;

/**
 * 
 * @author Manuel Raynaud <mraynaud@openstudio.fr>
 */

interface NullControllerInterface {
    
    /**
     * Nothing to do
     */
    public function noAction(Request $request);
    
}
?>
