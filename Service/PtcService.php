<?php

// Conduction/CommonGroundBundle/Service/PtcService.php

namespace Conduction\CommonGroundBundle\Service;

/*
 * The PTC Service handels logic reqoured to properly connect with the ptc component
 *
 */
class PtcService
{
    private $vrcService;
    private $commonGroundService;
    private $camundaService;

    public function __construct(VrcService $vrcService, CommonGroundService $commonGroundService, CamundaService $camundaService)
    {
        $this->vrcService = $vrcService;
        $this->commonGroundService = $commonGroundService;
        $this->camundaService = $camundaService;
    }

    /*
     * Validates a resource with optional commonground and component specific logic
     *
     * @param array $resource The resource before enrichment
     * @param array The resource afther enrichment
     */
    public function onResource(?array $resource)
    {
        return $this->extendProcess($resource);
    }

    /*
     * Validates a resource with optional commonground and component specific logic
     *
     * @param array $resource The resource before enrichment
     * @param array The resource afther enrichment
     */
    public function onList(?array $resource)
    {
        return $this->extendProcess($resource);
    }

    /*
     * Validates a resource with optional commonground and component specific logic
     *
     * @param array $resource The resource before enrichment
     * @param array The resource afther enrichment
     */
    public function onSave(?array $resource)
    {
        return $this->extendProcess($resource);
    }

    /*
     * Validates a resource with optional commonground and component specific logic
     *
     * @param array $resource The resource before enrichment
     * @param array The resource afther enrichment
     */
    public function onSaved(?array $resource)
    {
        return $this->extendProcess($resource);
    }

    /*
     * Validates a resource with optional commonground and component specific logic
     *
     * @param array $resource The resource before enrichment
     * @param array The resource afther enrichment
     */
    public function onDelete(?array $resource)
    {
        return $this->extendProcess($resource);
    }

    /*
     * Validates a resource with optional commonground and component specific logic
     *
     * @param array $resource The resource before enrichment
     * @param array The resource afther enrichment
     */
    public function onDeleted(?array $resource)
    {
        return $this->extendProcess($resource);
    }

    /*
     * Validates a resource with optional commonground and component specific logic
     *
     * @param array $resource The resource before enrichment
     * @param array The resource afther enrichment
     */
    public function onUpdate(?array $resource)
    {
        return $this->extendProcess($resource);
    }

    /*
     * Validates a resource with optional commonground and component specific logic
     *
     * @param array $resource The resource before enrichment
     * @param array The resource afther enrichment
     */
    public function onUpdated(?array $resource)
    {
        return $this->extendProcess($resource);
    }

    /*
     * Validates a resource with optional commonground and component specific logic
     *
     * @param array $resource The resource before enrichment
     * @param array The resource afther enrichment
     */
    public function onCreate(?array $resource)
    {
        return $this->extendProcess($resource);
    }

    /*
     * Aditional logic triggerd afther a Request has been newly created
     *
     * @param array $resource The resource before enrichment
     * @param array The resource afther enrichment
     */
    public function onCreated(?array $resource)
    {
        return $this->extendProcess($resource);
    }

    /*
     * Get al the properties associatied with an specific procces
     *
     * @param array $resource The resource before enrichment
     * @param array The resource afther enrichment
     */
    public function getProperties($proces)
    {
        // If we get an string instead of an array we need to turn it into a commonground object
        if(is_string($proces)){
            $proces = $this->commonGroundService->getResource($proces);
        }

        // lets setup the array
        $properties = [];

        // By now the procces should be an array
        if(!is_array($proces)) return $properties;

        // Lets make sure that we have the data we need
        if(!in_array('stages',$proces)) return $properties;

        // Lets turn the properties into a indexed array by name
        foreach ($proces['stages'] as $stage) {

            if(!in_array('sections',$stage)) continue;

            foreach ($stage['sections'] as $section){

                if(!in_array('properties',$section)) continue;

                foreach ($section['properties'] as $property){
                    $property = $this->commonGroundService->getResource($property);
                    $properties[$property['name']] = $property;
                }
            }
        }

        return $properties ;
    }


    /*
     * get a single property on name for a procces
     *
     * @param array $resource The resource before enrichment
     * @param array The resource afther enrichment
     */
    public function getProperty($proces, string $name)
    {
        $properties = $this->operties($proces);

        // Lets check if the property exists
        if(!array_key_exists($name, $properties)){
            return false;
        }

        return $properties[$name];
    }


}
