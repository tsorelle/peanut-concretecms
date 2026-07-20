<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 1/30/2019
 * Time: 7:06 AM
 */

namespace Peanut\QnutDocuments;

/**
 * Class DocumentListFilter
 * @package Peanut\QnutDocuments
 * DTO for document list requests and responses
 */
class DocumentListFilter
{
    /**
     * @var string
     * Item code
     */
    public $item = 'cmte';
    /**
     * @var string
     * value code eg committee-code "worship","are"
     */
    public $value;
    /**
     * @var string
     * order by clause
     */
    public $sortOrder = 'publicationDate desc';
    /**
     * @var string
     * Earliest publication date in ISO Date format or null for all
     */
    public $publcationDate = null;
    /**
     * @var int
     */
    public $itemsPerPage = 15;

    /**
     * @var int
     */
    public $pageNumber = 1;

    /**
     * @var bool
     */
    public $publicOnly = false;
}