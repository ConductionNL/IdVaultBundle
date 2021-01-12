<?php

// Conduction/CommonGroundBundle/Event/ResourceCreateEvent.php

namespace Conduction\IdVaultBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * The idvault.new.user is dispatched when a user is created in with the id-vault authenticator
 */
class NewUserEvent extends Event
{
    public const NAME = 'idvault.new.user';

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
