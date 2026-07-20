<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 7/3/2017
 * Time: 10:05 AM
 */

namespace Tops\sys;


class TaxonomyManager
{
    /**
     * @var ITaxonomyManager
     */
    private static $instance;

    private static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = TObjectContainer::Get('tops.taxonomy');
        }
        return self::$instance;
    }

    public static function GetTerms($taxonomyPath) {
        return self::getInstance()->getTaxonomyTerms($taxonomyPath);
    }

    public static function Create($taxonomyPath, array $taxonomyTerms) {
        return self::getInstance()->createTaxonomy($taxonomyPath, $taxonomyTerms);
    }

    public static function AddTerm($taxonomyPath,$taxonomyTerm) {
        return self::getInstance()->addTaxonomyTerm($taxonomyPath,$taxonomyTerm);
    }

    public static function RemoveTerm($taxonomyPath,$taxonomyTerm) {
        return self::getInstance()->removeTaxonomyTerm($taxonomyPath,$taxonomyTerm);
    }

}