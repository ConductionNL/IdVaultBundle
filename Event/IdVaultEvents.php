<?php

namespace Conduction\IdVaultBundle\Event;

/**
 * The IdVaultEvents holds hook able events triggered from the id-vault service.
 */
class IdVaultEvents
{
    /**
     * Called directly before the Lorem Ipsum API data is returned.
     *
     * Listeners have the opportunity to change that data.
     *
     * @Event("Conduction\IdVaultBundle\Event\NewUserEvent")
     */
    public const NEWUSER = 'idvault.new.user';

    /**
     * Called directly before the Lorem Ipsum API data is returned.
     *
     * Listeners have the opportunity to change that data.
     *
     * @Event("Conduction\IdVaultBundle\Event\LoggedInEvent")
     */
    public const LOGGEDIN = 'idvault.logged.in';

}
