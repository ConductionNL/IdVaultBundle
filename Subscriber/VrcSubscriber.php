<?php

namespace Conduction\CommonGroundBundle\Subscriber;

use Conduction\CommonGroundBundle\Event\ResourceSaveEvent;
use Conduction\CommonGroundBundle\Service\VrcService;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class VrcSubscriber implements EventSubscriberInterface
{
    private $vrcService;

    public function __construct(VrcService $vrcService)
    {
        $this->vrcService = $vrcService;

    }

    public static function getSubscribedEvents()
    {
        return [
            ResourceCreatedEvent::NAME => 'onCreated',
        ];
    }

    // Our resource might reqoure aditional resources to be created, so lets look into that
    public function onCreated(ResourceSaveEvent $event)
    {
        // Lets make sure that we are dealing with a Request resource from the vrc
        if($event->getComponeent() == 'vrc' && $event->getType() == 'requests'){
            return;
        }

        // Lets see if we need to do anything with the resource
        $resource = $event->getResource();
        $resource = $this->vrcService->onCreated($resource);
        $event->setResource($resource);

        return $event;
    }
}
