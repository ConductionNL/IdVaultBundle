<?php

// Conduction/CommonGroundBundle/Service/VrcService.php

namespace Conduction\CommonGroundBundle\Service;

use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Conduction\CommonGroundBundle\Service\CamundaService;

/*
 * The VRC Service handels logic reqoured to properly connect with the vrc component
 *
 */
class VrcService
{
    private $commonGroundService;
    private $camundaService;

    public function __construct(CommonGroundService $commonGroundService, CamundaService $camundaService)
    {
        $this->commonGroundService = $commonGroundService;
        $this->camundaService = $camundaService;
    }

    /*
     * Validates a resource with optional commonground and component specific logic
     *
     * @param array $resource The resource before enrichment
     * @param array The resource afther enrichment
     */
    public function scanResource(?array $resource)
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
            foreach($resource['properties'] as $property){

            }
        }

        // Lets see if we need to create assents for the submitters
        if(key_exists('submitters', $resource)){
            foreach ($resource['submitters'] as $submitter){

            }
        }


        // If the request has Camunda requests we need to trigger those
        if(key_exists('caseType', $requestType) && !key_exists('cases', $resource)){
            /* @todo create a case */
        }


        // If the request has Zaak properties we need to trigger those
        if(key_exists('camundaProces', $requestType) && !key_exists('processes', $resource)){
            /* @todo start a camunda procces */
            $procces = $this->camundaService->proccesFromRequest($resource);
            $resource['processes'] = [$procces];
        }

        return $resource;
    }
}
