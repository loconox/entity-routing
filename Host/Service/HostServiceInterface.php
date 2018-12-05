<?php


namespace Loconox\EntityRoutingBundle\Host\Service;


use Loconox\EntityRoutingBundle\Service\ServiceInterface;

interface HostServiceInterface extends ServiceInterface
{
    /**
     * Find an entity by its hostname field and return it.
     *
     * @param string $hostname
     * @return mixed|null
     */
    public function findOneByHost($hostname);

    /**
     * Returns the host are part of the host, from the related entity.
     *
     * @param mixed $entity
     * @return string
     */
    public function getHost($entity): string;
}