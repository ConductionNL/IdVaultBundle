<?php

// Conduction/CommonGroundBundle/Service/VrcService.php

namespace Conduction\CommonGroundBundle\Service;

/*
 * The VRC Service handels logic reqoured to properly connect with the vrc component
 *
 */
class NotificationService
{
    private $commonGroundService;

    public function __construct(
        CommonGroundService $commonGroundService
    ) {
        $this->commonGroundService = $commonGroundService;
    }

    /*
     * Send a signal to the notification service that somthing has happend with a resource
     *
     * @param array $resource The resource before enrichment
     * @param array The resource afther enrichment
     */
    public function notify(?array $resource, $event)
    {
        // Let write some magix
        $response = null;

        return $response;
    }
}
