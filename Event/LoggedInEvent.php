<?php

// Conduction/CommonGroundBundle/Event/ResourceCreateEvent.php

namespace Conduction\IdVaultBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * The idvault.logged.in is dispatched when a user logs in with the id-vault authenticator
 */
class LoggedInEvent extends Event
{
    public const NAME = 'idvault.logged.in';

    protected $resource;


    public function __construct(?array $resource)
    {
        $this->resource = $resource;

    }

    public function setResource(?array $resource)
    {
        $this->resource = $resource;

        return $this;
    }

    public function getResource()
    {
        return $this->resource;
    }

}
