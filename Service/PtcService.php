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

    public function __construct(VrcService $vrcService,CommonGroundService $commonGroundService, CamundaService $camundaService)
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
     * This function fills a procces with all the requered data in order to render it
     *
     * @param array $resource The resource before enrichment
     * @param array The resource afther enrichment
     */
    public function extendProcess(?array $resource)
    {
        $resource['completed'] = false;
        $resource['valid'] = false;
        foreach($resource['stages'] as $stageKey => $stage){
            foreach($stage['sections'] as $sectionKey => $section) {
                $resource['stages'][$stageKey]['sections'][$sectionKey]['propertiesForms'] = [];
                foreach ($section['properties'] as $propertyKey => $property) {
                    $property = $this->commonGroundService->getResource($property);
                    $property['value'] = null;
                    $property['valid'] = false;
                    $property['message'] = null;
                    $resource['stages'][$stageKey]['sections'][$sectionKey]['propertiesForms'][$property['id']] = $property;
                }
                $resource['stages'][$stageKey]['sections'][$sectionKey]['completed'] = false;
                $resource['stages'][$stageKey]['sections'][$sectionKey]['valid'] = false;
            }
            $resource['stages'][$stageKey]['completed'] = false;
            $resource['stages'][$stageKey]['valid'] = false;
        }

        return $resource;
    }


    /*
     * Aditional logic triggerd afther a Request has been newly created
     *
     * @param array $resource The resource before enrichment
     * @param array The resource afther enrichment
     */
    public function fillProcess(?array $procces, array $request = null)
    {
        foreach($resource['stages'] as $stageKey => $stage){
            $resource['stages'][$stageKey]['completed'] = true;
            $resource['stages'][$stageKey]['valid'] = true;
            foreach($stage['sections'] as $sectionKey => $section) {
                $resource['stages'][$stageKey]['sections'][$sectionKey]['propertiesForms'] = [];
                $resource['stages'][$stageKey]['sections'][$sectionKey]['completed'] = true;
                $resource['stages'][$stageKey]['sections'][$sectionKey]['valid'] = true;
                foreach ($section['propertiesForms'] as $propertyKey => $property) {
                    $result = $this->vrcService->checkProperty($request, $propertys);
                    $property['value'] = $result['value'];
                    $property['valid'] = $result['valid'];
                    $property['message'] = $result['message'];
                    if(!$property['valid']){
                        $resource['stages'][$stageKey]['sections'][$sectionKey]['completed'] = false;
                        $resource['stages'][$stageKey]['sections'][$sectionKey]['valid'] = false;
                    }
                }
                if(!$resource['stages'][$stageKey]['sections'][$sectionKey]['valid']){
                    $resource['stages'][$stageKey]['valid'] = false;
                }
                if(!$resource['stages'][$stageKey]['sections'][$sectionKey]['completed']){
                    $resource['stages'][$stageKey]['completed'] = false;
                }
            }
            if(!$resource['stages'][$stageKey]['valid']){
                $resource['valid'] = false;
            }
            if(!$resource['stages'][$stageKey]['completed']){
                $resource['completed'] = false;
            }
        }
        return $resource;
    }
}
