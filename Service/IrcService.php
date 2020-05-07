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
     *
     * @param array $resource The resource before enrichment
     * @param array The resource afther enrichment
     */
    public function scanResource(array $resource)
    {
        // Lets see if we need to create a contact for the contact
        if(key_exists('contact', $resource) && !key_exists('@id', $resource['contact'])){
            $contact = $this->commonGroundService->saveResource($resource['contact'],['component'=>'cc','type'=>'people']);
            $resource['contact'] = $contact['@id'];
        }

        // Lets see if we need to create a contact for the requester
        if(key_exists('requester', $resource) && !key_exists('@id', $resource['requester'])){
            $contact = $this->commonGroundService->saveResource($resource['requester'],['component'=>'cc','type'=>'people']);
            $resource['contact'] = $contact['@id'];
        }

        return $resource;
    }
}
