<?php

// Conduction/CommonGroundBundle/Event/ResourceCreatedEvent.php

namespace Conduction\CommonGroundBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
* The commonground.resource.created event is dispatched each time afther an commonground resource is created
*/
class ResourceCreatedEvent extends Event
{
    public const NAME = 'commonground.resource.created';

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