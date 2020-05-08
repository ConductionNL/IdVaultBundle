<?php

// Conduction/CommonGroundBundle/Event/ResourceListEvent.php

namespace Conduction\CommonGroundBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
* The commonground.resource.list is dispatched each time afhter an commonground resource list aquired through an api
*/
class ResourceListEvent extends Event
{
    public const NAME = 'commonground.resource.list';

    protected $resource;

    public function __construct(Array $resource)
    {
    $this->resource = $resource;
    }

    public function getResource()
    {
    return $this->resource;
    }
}