<?php

// src/Twig/CommonGroundExtension.php

namespace Conduction\CommonGroundBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CommonGroundExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            // the logic of this filter is now implemented in a different class
            new TwigFunction('commonground_resource_list', [CommonGroundRuntime::class, 'getResourceList']),
            new TwigFunction('commonground_resource', [CommonGroundRuntime::class, 'getResource']),
            new TwigFunction('commonground_is_resource', [CommonGroundRuntime::class, 'isResource']),
            new TwigFunction('commonground_component_list', [CommonGroundRuntime::class, 'getComponentList']),
            new TwigFunction('commonground_component_health', [CommonGroundRuntime::class, 'getComponentHealth']),
            new TwigFunction('commonground_component_resources', [CommonGroundRuntime::class, 'getComponentResources']),
            new TwigFunction('commonground_application', [CommonGroundRuntime::class, 'getApplication']),
            new TwigFunction('commonground_cleanurl', [CommonGroundRuntime::class, 'cleanUrl']),
            new TwigFunction('commonground_path', [CommonGroundRuntime::class, 'getPath']),
            new TwigFunction('path', [CommonGroundRuntime::class, 'getPath']),
            new TwigFunction('iterable', [CommonGroundRuntime::class, 'iterableArray']),
            new TwigFunction('date_interval', [CommonGroundRuntime::class, 'date_interval']),
        ];
    }
}
