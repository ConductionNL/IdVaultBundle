# ConductionCommonGroundBundle
This bundle adds VNG Common Ground functionality to you Symfony API Platform application. It aims to extend this platform by providing addition features either inherent to common ground (like the VNG API Standard) or typical for the Dutch Application landscape (like BSN checks and KVK lookups).   

This bundle is maintained as a free of charge open-source project by [conduction](http://conduction.nl), a Dutch platform oriented startup. 

**Requires**: PHP 7.1+

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

## Events 
The commonground bundle adds a couple of commonground specific events to your symfony installation

The commonground.resource event is dispatched each time *before* an commongroundresource list aquired through an api
The commonground.resource.list is dispatched each time *before* an commonground resource list aquired through an api
The commonground.resource.delete event is dispatched each time *before* an commonground resource is deleted
The commonground.resource.save event is dispatched each time *before* an commonground resource is saved
The commonground.resource.saved event is dispatched each time *afther* an commonground resource is saved
The commonground.resource.update is dispatched each time *before* an commonground resource is updated
The commonground.resource.updated event is dispatched each time *afther* an commonground resource is updated
The commonground.resource.create is dispatched each time *before* an commonground resource is created
The commonground.resource.created event is dispatched each time *afther* an commonground resource is created

## Commonground

Common ground is a Dutch governmental initiative exploring the possibilities of using open source, and rest api's as a backbone for government and public architecture
