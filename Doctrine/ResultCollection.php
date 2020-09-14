<?php

namespace Conduction\CommonGroundBundle\Doctrine;

use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayIterator;
use Doctrine\Common\Collections\Closure;
use Doctrine\Common\Collections\Expr\ClosureExpressionVisitor;
use const ARRAY_FILTER_USE_BOTH;
use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_reverse;
use function array_search;
use function array_slice;
use function array_values;
use function count;
use function current;
use function end;
use function in_array;
use function key;
use function next;
use function reset;
use function spl_object_hash;
use function uasort;

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

class ResultCollection implements Collection, Selectable
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
     * {@inheritDoc}
     */
    public function toArray()
    {
        // Backwards compatability
        $result = $this->elements;
        $result["hydra:member"] = $result;
        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function first()
    {
        return reset($this->elements);
    }

    /**
     * Creates a new instance from the specified elements.
     *
     * This method is provided for derived classes to specify how a new
     * instance should be created when constructor semantics have changed.
     *
     * @param array $elements Elements.
     *
     * @return static
     *
     * @psalm-param array<TKey,T> $elements
     * @psalm-return static<TKey,T>
     */
    protected function createFrom(array $elements)
    {
        return new static($elements);
    }

    /**
     * {@inheritDoc}
     */
    public function last()
    {
        return end($this->elements);
    }

    /**
     * {@inheritDoc}
     */
    public function key()
    {
        return key($this->elements);
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
