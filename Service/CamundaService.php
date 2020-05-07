<?php

// src/Service/HuwelijkService.php

namespace App\Service;

use Conduction\CommonGroundBundle\Service\CommonGroundService;

class CamundaService
{
    private $commonGroundService;

    public function __construct(CommonGroundService $commonGroundService)
    {
        $this->commonGroundService = $commonGroundService;
    }

    /*
     * Get a single resource from a common ground componant
     */
    public function proccesFromRequest(?array $request)
    {

    }
}
