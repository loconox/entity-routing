<?php


namespace Loconox\EntityRoutingBundle\Service;


interface ServiceInterface
{
    /**
     * Get the classe name
     *
     * @return string
     */
    public function getClass();

    /**
     * Get the service name
     *
     * @return
     */
    public function getName();

    /**
     * Set service name
     *
     * @param string $name
     */
    public function setName($name);

    /**
     * Get the service alias
     *
     * @return string
     */
    public function getAlias();

    /**
     * Set the service alias
     *
     * @param string $alias
     */
    public function setAlias($alias);
}