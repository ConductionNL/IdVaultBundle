<?php

namespace Conduction\CommonGroundBundle\Subscriber;

use Conduction\CommonGroundBundle\Event\CommonGroundEvents;
use Conduction\CommonGroundBundle\Event\CommongroundUpdateEvent;
use Conduction\CommonGroundBundle\Service\PtcService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class PtcSubscriber implements EventSubscriberInterface
{
    private $vrcService;

    public function __construct(PtcService $ptcService)
    {
        $this->ptcService = $ptcService;
    }

    public static function getSubscribedEvents()
    {
        return [
            CommonGroundEvents::SAVE     => 'save',
            CommonGroundEvents::SAVED    => 'saved',
            CommonGroundEvents::DELETE   => 'delete',
            CommonGroundEvents::DELETED  => 'deleted',
            CommonGroundEvents::CREATE   => 'create',
            CommonGroundEvents::CREATED  => 'created',
            CommonGroundEvents::UPDATE   => 'update',
            CommonGroundEvents::UPDATED  => 'updated',
            CommonGroundEvents::RESOURCE => 'resource',
            CommonGroundEvents::LIST     => 'list',
            //KernelEvents::VIEW => ['onCreate', ResourceCreateEvent::NAME],
            //ResourceCreateEvent::NAME => 'onCreate',
            //ResourceCreatedEvent::NAME => 'onCreated',
            //ResourceSaveEvent::NAME => 'onSave',
        ];
    }

    // Our resource might reqoure aditional resources to be created, so lets look into that
    public function resource(CommongroundUpdateEvent $event)
    {
        // Lets make sure we only triger on requests resources
        /* @todo lets also check for a vrc component */
        $processType = $event->getResource();
        if (!key_exists('@type', $processType) || $processType['@type'] != 'ProcessType') {
            return;
        }

        $resource = $this->ptcService->extendProcess($event->getResource());
        $event->setResource($resource);

        return $event;
    }

    // Our resource might reqoure aditional resources to be created, so lets look into that
    public function list(CommongroundUpdateEvent $event)
    {
        return $event;
    }

    // Our resource might reqoure aditional resources to be created, so lets look into that
    public function save(CommongroundUpdateEvent $event)
    {
        return $event;
    }

    // Our resource might reqoure aditional resources to be created, so lets look into that
    public function saved(CommongroundUpdateEvent $event)
    {
        return $event;
    }

    // Our resource might reqoure aditional resources to be created, so lets look into that
    public function delete(CommongroundUpdateEvent $event)
    {
        return $event;
    }

    // Our resource might reqoure aditional resources to be created, so lets look into that
    public function deleted(CommongroundUpdateEvent $event)
    {
        return $event;
    }

    // Our resource might reqoure aditional resources to be created, so lets look into that
    public function update(CommongroundUpdateEvent $event)
    {
        return $event;
    }

    // Our resource might reqoure aditional resources to be created, so lets look into that
    public function updated(CommongroundUpdateEvent $event)
    {
        // Lets make sure we only triger on requests resources
        /* @todo lets also check for a vrc component */
        $resource = $event->getResource();
        if (!array_key_exists('@type', $resource) || $resource['@type'] != 'ProcessType') {
            return;
        }

        $resource = $this->ptcService->extendProcess($event->getResource());
        $event->setResource($resource);

        return $event;
    }

    // Our resource might reqoure aditional resources to be created, so lets look into that
    public function create(CommongroundUpdateEvent $event)
    {
        return $event;
    }

    // Our resource might reqoure aditional resources to be created, so lets look into that
    public function created(CommongroundUpdateEvent $event)
    {
        $resource = $event->getResource();
        if (!array_key_exists('@type', $resource) || $resource['@type'] != 'ProcessType') {
            return;
        }

        $resource = $this->ptcService->extendProcess($event->getResource());
        $event->setResource($resource);

        return $event;
    }
}
