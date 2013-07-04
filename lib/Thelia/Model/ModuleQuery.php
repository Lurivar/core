<?php

namespace Thelia\Model;

use Thelia\Model\Base\ModuleQuery as BaseModuleQuery;


/**
 * Skeleton subclass for performing query and update operations on the 'module' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */
class ModuleQuery extends BaseModuleQuery {
    /**
     * @return array|mixed|\PropelObjectCollection
     */
    public static function getActivated()
    {
        return self::create()
            ->filterByActivate(1)
            ->find();
    }
} // ModuleQuery
