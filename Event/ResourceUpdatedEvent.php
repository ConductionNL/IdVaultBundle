<?php

// Conduction/CommonGroundBundle/Event/ResourceUpdatedEvent.php

namespace Conduction\CommonGroundBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
* The commonground.resource.updated event is dispatched each time afther an commonground resource is updated
*/
class ResourceUpdatedEvent extends Event
{
    public const NAME = 'commonground.resource.updated';

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