<?php

// Conduction/CommonGroundBundle/Event/ResourceUpdateEvent.php

namespace Conduction\CommonGroundBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
* The commonground.resource.update is dispatched each time before an commonground resource is updated
*/
class ResourceUpdateEvent extends Event
{
    public const NAME = 'commonground.resource.update';

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