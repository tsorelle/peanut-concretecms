<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 7/3/2017
 * Time: 9:59 AM
 */

namespace Tops\sys;


interface ITaxonomyManager
{
    public function getTaxonomyTerms($taxonomyPath);
    public function createTaxonomy($taxonomyPath, array $taxonomyTerms);
    public function addTaxonomyTerm($taxonomyPath,$taxonomyTerm);
    public function removeTaxonomyTerm($taxonomyPath,$taxonomyTerm);
}