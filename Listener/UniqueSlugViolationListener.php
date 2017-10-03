<?php
/**
 * Created by PhpStorm.
 * User: loconox
 * Date: 16/12/2014
 * Time: 16:14
 */

namespace Loconox\EntityRoutingBundle\Listener;

use Loconox\EntityRoutingBundle\Event\SlugEvent;
use Loconox\EntityRoutingBundle\Events;
use Loconox\EntityRoutingBundle\Exception\SlugServiceNotFoundException;
use Loconox\EntityRoutingBundle\Model\SlugManagerInterface;
use Loconox\EntityRoutingBundle\Slug\SlugServiceManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UniqueSlugViolationListener implements EventSubscriberInterface
{
    /**
     * @var SlugManagerInterface
     */
    protected $slugManager;

    /**
     * @var SlugServiceManagerInterface
     */
    protected $slugServiceManager;

    function __construct($slugManager, $slugServiceManager)
    {
        $this->slugManager = $slugManager;
        $this->slugServiceManager = $slugServiceManager;
    }


    public function uniqueSlugViolation(SlugEvent $event)
    {
        $entity = $event->getEntity();

        $service = $this->slugServiceManager->get($entity);

        if (!$service) {
            throw new SlugServiceNotFoundException($entity);
        }

        $slugViolation = $service->createSlug($entity, false);

        $slugs = $this->slugManager->findSlugLike($slugViolation);

        $i = 1;
        foreach($slugs as $slug) {
            // Si c'est le slug de l'entity déjà présent en base continuer
            if ($slug->getEntityId() == $slugViolation->getEntityId()) {
                continue;
            }
            preg_match('/'.$slugViolation->getSlug().'-([0-9]+)$/', $slug->getSlug(), $matches);
            if (!empty($matches)) {
                $j = intval($matches[1]);
                $i = $j >= $i ? $j + 1 : $i;
            }
        }
        $slugViolation->setSlug($slugViolation->getSlug().'-'.$i);
        $service->setEntitySlug($slugViolation, $entity);
    }

    public static function getSubscribedEvents()
    {
        return [
            Events::UNIQUE_SLUG_VIOLATION => 'uniqueSlugViolation',
        ];
    }
}