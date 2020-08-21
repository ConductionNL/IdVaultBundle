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
}
