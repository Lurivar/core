<?php

namespace Thelia\Model;

use Thelia\Model\Base\ConfigQuery as BaseConfigQuery;


/**
 * Skeleton subclass for performing query and update operations on the 'config' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */
class ConfigQuery extends BaseConfigQuery {
    public static function read($search, $default = null)
    {
        $value = self::create()->findOneByName($search);

        return $value ? $value->getValue() : $default;
    }

    public static function getDefaultLangWhenNoTranslationAvailable()
    {
        return ConfigQuery::read("default_lang_without_translation", 1);
    }

    public static function isRewritingEnable()
    {
        return self::read("rewriting_enable") == 1;
    }
} // ConfigQuery
