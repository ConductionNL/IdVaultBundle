<?php

// Conduction/CommonGroundBundle/Service/IdVaultService.php
namespace Conduction\IdVaultBundle\Service;

use Conduction\CommonGroundBundle\Service\CommonGroundService;

class IdVaultService
{

    /**
     * @var CommonGroundService
     */
    private $commonGroundService;

    public function __construct(
        CommonGroundService $commonGroundService
    ) {
        $this->commonGroundService = $commonGroundService;
    }

    // getScope

    // getClaim

    // getAuthorisation

    /**
     * This function add a doosier to a current user
     *
     * @parameter $amount Money the amount of money that you want to add
     * @parameter $resource string resource that owns the acount that is being added to
     * @parameter $name string the text displayed with this transaction
     *
     * @returns boolean true if the dossier created was succesfull, false otherwise
     */
    public function createDossier(array $scopes, string $resource, string $name, ?string $description)
    {
        return true;
    }

    // createContract

}
