<?php

// Conduction/CommonGroundBundle/Event/ResourceSavedEvent.php

namespace Conduction\CommonGroundBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
* The commonground.resource.saved event is dispatched each time afther an commonground resource is saved
*/
class ResourceSavedEvent extends Event
{
    public const NAME = 'commonground.resource.saved';

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