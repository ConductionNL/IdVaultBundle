<?php

namespace Conduction\CommonGroundBundle\Subscriber;

use Conduction\CommonGroundBundle\Event\ResourceSaveEvent;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Conduction\CommonGroundBundle\Service\VrcService;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class VrcSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            ResourceSaveEvent::NAME => 'onSaveRequest',
        ];
    }

    // Our resource might reqoure aditional resources to be created, so lets look into that
    public function onSaveRequest(ResourceSaveEvent $event)
    {
        // Lets make sure that we are dealing with a Request resource from the vrc
        if($event->getComponeent() == 'vrc' && $event->getType() == 'requests'){
            return;
        }

        // ...
    }
}
