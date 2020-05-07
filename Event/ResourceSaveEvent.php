<?php

// Conduction/CommonGroundBundle/Event/ResourceSaveEvent.php

namespace Conduction\CommonGroundBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
* The commonground.resource.save event is dispatched each time before an commonground resource is saved
*/
class ResourceSaveEvent extends Event
{
    public const NAME = 'commonground.resource.save';

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