<?php


namespace Loconox\EntityRoutingBundle\Host\Service;


abstract class AbstractHostService implements HostServiceInterface
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $alias;

    /**
     * @var string
     */
    protected $class;

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @param string $alias
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
    }

    /**
     * {@inheritdoc}
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Find an entity by its hostname field and return it.
     *
     * @param string $hostname
     * @return mixed|null
     */
    abstract public function findOneByHost($hostname);

    /**
     * Returns the host are part of the host, from the related entity.
     *
     * @param mixed $entity
     * @return string
     */
    abstract public function getHost($entity): string;
}