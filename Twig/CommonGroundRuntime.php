<?php

// src/Twig/Commonground.php

namespace Conduction\CommonGroundBundle\Twig;

use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Twig\Extension\RuntimeExtensionInterface;

class CommonGroundRuntime implements RuntimeExtensionInterface
{
    private $commongroundService;

    public function __construct(CommonGroundService $commongroundService)
    {
        $this->commongroundService = $commongroundService;
    }

    public function getResource($resource)
    {
        return $this->commongroundService->getResource($resource);
    }

    public function isResource($resource){
        return $this->commongroundService->isResource($resource);
    }

    public function getResourceList($url, $query = null)
    {
        return $this->commongroundService->getResourceList($url, $query);
    }

    public function getComponentList()
    {
        return $this->commongroundService->getComponentList();
    }

    public function getComponentHealth($component)
    {
        return $this->commongroundService->getComponentHealth($component);
    }

    public function getComponentResources($component)
    {
        return $this->commongroundService->getComponentResources($component);
    }

    public function getApplication()
    {
        return $this->commongroundService->getApplication();
    }

    public function cleanUrl($url = false, $resource = false, $autowire = true)
    {
        return $this->commongroundService->cleanUrl($url, $resource, $autowire);
    }
    public function getPath()
}
