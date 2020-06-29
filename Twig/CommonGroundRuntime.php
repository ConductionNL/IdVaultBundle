<?php

// src/Twig/Commonground.php

namespace Conduction\CommonGroundBundle\Twig;

use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Twig\Extension\RuntimeExtensionInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CommonGroundRuntime implements RuntimeExtensionInterface
{
    private $commongroundService;
    private $params;
    private $router;

    public function __construct(CommonGroundService $commongroundService, ParameterBagInterface $params, RouterInterface $router)
    {
        $this->commongroundService = $commongroundService;
        $this->params = $params;
        $this->router = $router;
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
    public function getPath(string $path){
        if($this->params->get('app_subpath') != 'false'){
            return '/'.$this->params->get('app_subpath').$this->router->generate($path, [], UrlGeneratorInterface::RELATIVE_PATH);
        }
        else{
            return $this->router->generate($path, [], UrlGeneratorInterface::RELATIVE_PATH);
        }
    }
}
