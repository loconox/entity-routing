<?php
/**
 * Created by PhpStorm.
 * User: loconox
 * Date: 08/12/2014
 * Time: 15:02
 */

namespace Loconox\EntityRoutingBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class SlugEvent extends Event
{

    /**
     * @var mixed
     */
    protected $entity;

    public function __construct($entity)
    {
        $this->entity = $entity;
    }

    /**
     * Get the entity
     *
     * @return mixed
     */
    public function getEntity()
    {
        return $this->entity;
    }
}