<?php

// Conduction/CommonGroundBundle/Event/ResourceCreateEvent.php

namespace Conduction\CommonGroundBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
* The commonground.resource.create is dispatched each time before an commonground resource is created
*/
class ResourceCreateEvent extends Event
{
    public const NAME = 'commonground.resource.create';

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