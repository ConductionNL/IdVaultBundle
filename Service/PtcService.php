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
        $properties = $this->getProperties($proces);

        // Lets check if the property exists
        if(!array_key_exists($name, $properties)){
            return false;
        }

        return $properties[$name];
    }

    /*
     * This function fills a procces with all the requered data in order to render it
     *
     * @param array $resource The resource before enrichment
     * @param array The resource afther enrichment
     */
    public function extendProcess(?array $procces)
    {
        $procces['valid'] = false;
        foreach ($procces['stages'] as $stageKey => $stage) {
            foreach ($stage['sections'] as $sectionKey => $section) {
                $procces['stages'][$stageKey]['sections'][$sectionKey]['propertiesForms'] = [];
                foreach ($section['properties'] as $propertyKey => $property) {
                    $property = $this->commonGroundService->getResource($property);
                    $property['value'] = null;
                    $property['valid'] = false;
                    $property['message'] = null;
                    unset($property['requestType']);
                    $procces['stages'][$stageKey]['sections'][$sectionKey]['propertiesForms'][$property['@id']] = $property;
                }
                $procces['stages'][$stageKey]['sections'][$sectionKey]['valid'] = false;
            }
            $procces['stages'][$stageKey]['valid'] = false;
        }

        return $procces;
    }

    /*
     * Aditional logic triggerd afther a Request has been newly created
     *
     * @param array $resource The resource before enrichment
     * @param array The resource afther enrichment
     */
        public function fillProcess(?array $procces, array $request = null)
        {
            $procces = $this->extendProcess($procces);

            foreach ($procces['stages'] as $stageKey => $stage) {
                $procces['stages'][$stageKey]['valid'] = true;
                foreach ($stage['sections'] as $sectionKey => $section) {
                    $procces['stages'][$stageKey]['sections'][$sectionKey]['propertiesForms'] = [];
                    $procces['stages'][$stageKey]['sections'][$sectionKey]['valid'] = true;

                    // Lets validate the indivual property forms
                    foreach ($section['propertiesForms'] as $propertyKey => $property) {
                        // Lets validate
                        $result = $this->vrcService->checkProperty($request, $property);
                        // Set the results
                        $property['value'] = $result['value'];
                        $property['valid'] = $result['valid'];
                        $property['messages'] = $result['messages'];
                        $property['messages'] = $result['messages'];
                        // Store results to the current procces
                        if (!$property['valid']) {
                            $procces['stages'][$stageKey]['sections'][$sectionKey]['valid'] = false;
                        }
                    }

                    if (!$procces['stages'][$stageKey]['sections'][$sectionKey]['valid']) {
                        $procces['stages'][$stageKey]['valid'] = false;
                    }
                }
                if (!$procces['stages'][$stageKey]['valid']) {
                    $procces['valid'] = false;
                }
            }

            return $procces;
        }


}
