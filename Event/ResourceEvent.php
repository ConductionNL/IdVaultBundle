<?php

// Conduction/CommonGroundBundle/Event/ResourceEvent.php

namespace Conduction\CommonGroundBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
* The commonground.resource event is dispatched each time before an commongroundresource list aquired through an api
*/
class ResourceEvent extends Event
{
    public const NAME = 'commonground.resource';

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