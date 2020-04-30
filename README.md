# ConductionCommonGroundBundle
This bundle adds VNG Common Ground functionality to you Symfony API Platform application. It aims to extend this platform by providing addition features either inherent to common ground (like the VNG API Standard) or typical for the Dutch Application landscape (like BSN checks and KVK lookups).   

This bundle is maintained as a free of charge open-source project by [conduction](http://conduction.nl), a Dutch platform oriented startup. 

**Requires**: PHP 5.3+ or PHP 7.0+

**Lead Developer**: [@rubenlinde](http://twitter.com/rubenlinde)

**Original Author**: [@conduction_nl](http://twitter.com/conduction_nl)

## Installation

Symfony flex aproval for this bundle is still underway so right now it needs to be installed manually trough composer (without flex)

``` CLI
composer require conduction/commongroundbundle
```

After installation activate the bundle by adding it to config/bundles.php of your symfony installation

``` PHP
return [
    ...
	Conduction\CommonGroundBundle\CommonGroundBundle::class => ['all' => true],
];
```

Additionally you will need to copy the parameter file conduction_common_ground.yaml (from resources/config) to config/packages folder of your application


## Commonground

Common ground is a Dutch governmental initiative exploring the possibilities of using open source, and rest api's as a backbone for government and public architecture
