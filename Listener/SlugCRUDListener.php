<?php

namespace Loconox\EntityRoutingBundle\Listener;

use Loconox\EntityRoutingBundle\Entity\RouteManager;
use Loconox\EntityRoutingBundle\Event\SlugEvent;
use Loconox\EntityRoutingBundle\Events;
use Loconox\EntityRoutingBundle\Slug\SlugServiceManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SlugCRUDListener implements EventSubscriberInterface
{

    /**
     * @var SlugServiceManagerInterface
     */
    protected $serviceManager;

    function __construct(SlugServiceManagerInterface $serviceManager)
    {
        $this->serviceManager = $serviceManager;
    }

    /**
     * @param SlugEvent $event
     * @throws \Exception
     */
    public function actionCreateSlug(SlugEvent $event)
    {
        $entity = $event->getEntity();

        $service = $this->serviceManager->get(get_class($entity));

        if (!$service) {
            throw new \Exception(sprintf('No service found for the class %s', get_class($entity)));
        }

        $service->createSlug($entity);
    }

    /**
     * @param SlugEvent $event
     * @throws \Exception
     */
    public function actionUpdateSlug(SlugEvent $event)
    {
        $entity = $event->getEntity();

        $service = $this->serviceManager->get(get_class($entity));

        if (!$service) {
            throw new \Exception(sprintf('No service found for the class %s', get_class($entity)));
        }

        // Update the slug itself
        $newSlug = $service->updateSlug($entity);
    }

    public static function getSubscribedEvents()
    {
        return [
            Events::ACTION_CREATE_SLUG => 'actionCreateSlug',
            Events::ACTION_UPDATE_SLUG => 'actionUpdateSlug',
        ];
    }
}