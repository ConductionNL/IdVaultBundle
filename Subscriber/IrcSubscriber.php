<?php

namespace Conduction\CommonGroundBundle\Subscriber;

use Conduction\CommonGroundBundle\Event\CommonGroundEvents;
use Conduction\CommonGroundBundle\Event\CommongroundUpdateEvent;
use Conduction\CommonGroundBundle\Service\IrcService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class IrcSubscriber implements EventSubscriberInterface
{
    private $ircService;

    public function __construct(IrcService $ircService)
    {
        $this->ircService = $ircService;
    }

    public static function getSubscribedEvents()
    {
        return [
            CommonGroundEvents::SAVED => 'saved',
        ];
    }

    // Our resource might reqoure aditional resources to be created, so lets look into that
    public function saved(CommongroundUpdateEvent $event)
    {
        // Lets make sure that we are dealing with a Request resource from the vrc
        $resource = $event->getResource();
        if(!array_key_exists('@type',$resource) || $resource['@type'] !='Assent') {
            return;
        }

        // Lets see if we need to do anything with the resource
        $resource = $event->getResource();
        $resource = $this->ircService->scanResource($resource);
        $event->setResource($resource);

        return $event;
    }
}
