<?php

// Conduction/CommonGroundBundle/Service/IrcService.php

namespace Conduction\CommonGroundBundle\Service;

use Conduction\CommonGroundBundle\Service\CommonGroundService;


class IrcService
{
    private $commonGroundService;

    public function __construct(CommonGroundService $commonGroundService)
    {
        $this->commonGroundService = $commonGroundService;

    }

    /*
     * Validates a resource with optional commonground and component specific logic
     */
    public function scanResource($resource)
    {
        // Lets see if we need to create contacts for the submitters
        if(key_exists('submitters', $resource)){
            foreach ($resource['submitters']){

            }
        }


        return $resource;
    }
}
