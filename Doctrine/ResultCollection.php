<?php

namespace Conduction\CommonGroundBundle\Doctrine;

use Doctrine\Common\Collections\Collection;
use Conduction\CommonGroundBundle\Service\CommonGroundService;

/**
 * An resource representing a result collection
 *
 * This entity represents a product that can be ordered via the OrderRegistratieComponent.
 *
 * @author Ruben van der Linde <ruben@conduction.nl>
 *
 * @category Entity
 *
 * @license EUPL <https://github.com/ConductionNL/productenendienstencatalogus/blob/master/LICENSE.md>
 **/

class ResultCollection
{
    /**
     * An array containing the entries of this collection.
     *
     * @var array
     */
    private $elements;

    /**
     * The commonground service responsible for creating this result collection.
     *
     * @var object
     */
    private $commonGroundService;

    /**
     * @var integer the total amount of pages
     *
     */
    private $pages;

    /**
     * @var integer the current page
     *
     */
    private $page;

    /**
     * @var string The amount of items displayed per page
     *
     */
    private $limit;

    /**
     * @var string The amount of items before the first item
     *
     */
    private $offset;

    /**
     * @var string The total amount of items
     *
     */
    private $totalItems;

    /**
     * Initializes a new ResultCollection.
     *
     * @param array $elements
     *
     */
    public function __construct(array $elements = [])
    {
        $this->elements = $elements;
    }

    /**
     *
     */
    public function setCommonGroundService(CommonGroundService $commonGroundService)
    {
        // Backwards compatability
        $this->commonGroundService = $commonGroundService;
        return $this;
    }

    /**
     *
     */
    public function toArray()
    {
        // Backwards compatability
        $result = $this->elements;
        $result["hydra:member"] = $result;
        return $result;
    }

    /**
     * @param  array The result of a get list function
     *
     */
    function hydrate($result){

        // Hydra result (otherwise known as json-ld)
        if(key_exists("hydra:member",$result)){
            $this->result = $result["hydra:member"];
            $this->pages = $result["hydra:member"];
            $this->page = $result["hydra:member"];
            $this->limit = $result["hydra:member"];
            $this->offset = $result["hydra:member"];
            $this->totalItems = $result["hydra:member"];
        }
        elseif(key_exists("_embedded",$result)){
            $this->result = $result["_embedded"];
        }
        elseif(key_exists("result",$result)){
            $this->result = $result["result"];
            $this->totalItems = count($result);
        }
    }

}
