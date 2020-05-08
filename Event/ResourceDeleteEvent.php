<?php

// Conduction/CommonGroundBundle/Event/ResourceDeleteEvent.php

namespace Conduction\CommonGroundBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
* The commonground.resource.delete event is dispatched each time before an commonground resource is deleted
*/
class ResourceDeleteEvent extends Event
{
    public const NAME = 'commonground.resource.delete';

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