<?php


namespace Loconox\EntityRoutingBundle\Service;


use Symfony\Component\Routing\Route;

abstract class AbstractServiceManager
{
    /**
     * @var array
     */
    protected $services = [];

    /**
     * Add a service mapper
     *
     * @param ServiceInterface $service
     * @param string $id
     * @param string $alias
     */
    public function add(ServiceInterface $service, $id, $alias)
    {
        $service->setName($id);
        $service->setAlias($alias);
        $keys = [$service->getClass(), $id, $alias];
        $this->services[] = [
            'key' => $keys,
            'service' => $service
        ];
    }

    /**
     * Get the service by class or service name
     *
     * @param mixed $name
     * @param Route|null $route
     * @return ServiceInterface|false
     */
    public function get($name, Route $route = null)
    {
        if (false !== $service = $this->doGet($name)) {
            return $service;
        }

        if ($route && !is_object($name)) {
            $types = $route->getOption('types');
            if (isset($types[$name]) && (false !== $service = $this->doGet($types[$name]))) {
                return $service;
            }
        }

        return false;
    }

    /**
     * Get the service by class or service name
     *
     * @param mixed $name
     * @return ServiceInterface|false
     */
    protected function doGet($name)
    {
        foreach ($this->services as $node) {
            $keys = $node['key'];
            $service = $node['service'];
            foreach ($keys as $key) {
                if ($this->matchKey($name, $key)) {
                    return $service;
                }
            }
        }

        return false;
    }

    /**
     * Returns all services
     *
     * @return ServiceInterface[]
     */
    public function getAll()
    {
        $result = [];
        foreach ($this->services as $node) {
            $result[] = $node['service'];
        }

        return $result;
    }

    protected function matchKey($name, $key)
    {
        if ($name === $key) {
            return true;
        }
        if (is_object($name) && !is_array($key) && class_exists($key) && $name instanceof $key) {
            return true;
        }
        if (is_array($key) && is_array($name)) {
            foreach ($name as $index => $object) {
                if (!is_object($object) || !isset($key[$index])) {
                    return false;
                }
                $class = $key[$index];
                if (!$object instanceof $class) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }
}