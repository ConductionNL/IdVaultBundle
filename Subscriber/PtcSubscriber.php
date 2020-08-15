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
        if ($event->getResource()['@type'] != 'Request') {
            return;
        }

        $resource = $this->vrcService->onResource($event->getResource());
        $event->setResource($resource);

        return $event;
    }


    // Our resource might reqoure aditional resources to be created, so lets look into that
    public function list(CommongroundUpdateEvent $event)
    {
        // Lets make sure we only triger on requests resources
        /* @todo lets also check for a vrc component */
        if ($event->getList()['@type'] != 'Request') {
            return;
        }

        $resource = $this->vrcService->onList($event->getResource());
        $event->setResource($resource);

        return $event;
    }

    // Our resource might reqoure aditional resources to be created, so lets look into that
    public function save(CommongroundUpdateEvent $event)
    {
        // Lets make sure we only triger on requests resources
        /* @todo lets also check for a vrc component */
        if ($event->getSave()['@type'] != 'Request') {
            return;
        }

        $resource = $this->vrcService->onSave($event->getResource());
        $event->setResource($resource);

        return $event;
    }

    // Our resource might reqoure aditional resources to be created, so lets look into that
    public function saved(CommongroundUpdateEvent $event)
    {
        // Lets make sure we only triger on requests resources
        /* @todo lets also check for a vrc component */
        if ($event->getSaved()['@type'] != 'Request') {
            return;
        }

        $resource = $this->vrcService->onSaved($event->getResource());
        $event->setResource($resource);

        return $event;
    }

    // Our resource might reqoure aditional resources to be created, so lets look into that
    public function delete(CommongroundUpdateEvent $event)
    {
        // Lets make sure we only triger on requests resources
        /* @todo lets also check for a vrc component */
        if ($event->getDelete()['@type'] != 'Request') {
            return;
        }

        $resource = $this->vrcService->onDelete($event->getResource());
        $event->setResource($resource);

        return $event;
    }

    // Our resource might reqoure aditional resources to be created, so lets look into that
    public function deleted(CommongroundUpdateEvent $event)
    {
        // Lets make sure we only triger on requests resources
        /* @todo lets also check for a vrc component */
        if ($event->getDelteted()['@type'] != 'Request') {
            return;
        }

        $resource = $this->vrcService->onDeleted($event->getResource());
        $event->setResource($resource);

        return $event;
    }

    // Our resource might reqoure aditional resources to be created, so lets look into that
    public function update(CommongroundUpdateEvent $event)
    {
        // Lets make sure we only triger on requests resources
        /* @todo lets also check for a vrc component */
        if ($event->getUpdate()['@type'] != 'Request') {
            return;
        }

        $resource = $this->vrcService->onUpdate($event->getResource());
        $event->setResource($resource);

        return $event;
    }

    // Our resource might reqoure aditional resources to be created, so lets look into that
    public function updated(CommongroundUpdateEvent $event)
    {
        // Lets make sure we only triger on requests resources
        /* @todo lets also check for a vrc component */
        if ($event->getResource()['@type'] != 'Request') {
            return;
        }

        $resource = $this->vrcService->onUpdated($event->getResource());
        $event->setResource($resource);

        return $event;
    }

    // Our resource might reqoure aditional resources to be created, so lets look into that
    public function create(CommongroundUpdateEvent $event)
    {
        // Lets make sure we only triger on requests resources
        /* @todo lets also check for a vrc component */
        if ($event->getCreate()['@type'] != 'Request') {
            return;
        }

        $resource = $this->vrcService->onCreate($event->getResource());
        $event->setResource($resource);

        return $event;
    }

    // Our resource might reqoure aditional resources to be created, so lets look into that
    public function created(CommongroundUpdateEvent $event)
    {
        if ($event->getResource()['@type'] != 'Request') {
            return;
        }

        $resource = $this->vrcService->onCreated($event->getResource());
        $event->setResource($resource);

        return $event;
    }
}
