<?php

// Conduction/CommonGroundBundle/Service/VrcService.php

namespace Conduction\CommonGroundBundle\Service;

use Conduction\CommonGroundBundle\Service\CommonGroundService;

/*
 * The VRC Service handels logic reqoured to properly connect with the vrc component
 *
 */
class VrcService
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
        // Lets get the request type
        if(key_exists('requestType', $resource)) {
            $requestType = $this->commonGroundService->getResource($resource['requestType']);
        }

        // Lets get the process type
        if(key_exists('processType', $resource)) {
            $processType = $this->commonGroundService->getResource($resource['processType']);
        }

        // We need to loop trough the properties, and see if items need to be created
        if(key_exists('properties', $resource)){
            foreach ($resource['properties']){

            }
        }

        // Lets see if we need to create assents for the submitters
        if(key_exists('submitters', $resource)){
            foreach ($resource['submitters']){

            }
        }


        // If the request has Camunda requests we need to trigger those

        // If the request has Zaak properties we need to trigger those

        return $resource;
    }
}
