<?php
/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Loconox\EntityRoutingBundle\Route;

use Loconox\EntityRoutingBundle\Entity\SlugManager;
use Loconox\EntityRoutingBundle\Generator\UrlGenerator;
use Loconox\EntityRoutingBundle\Host\HostServiceManager;
use Loconox\EntityRoutingBundle\Matcher\UrlMatcher;
use Loconox\EntityRoutingBundle\Slug\SlugServiceManager;
use Symfony\Cmf\Component\Routing\VersatileGeneratorInterface;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class SlugRouter implements RequestMatcherInterface, VersatileGeneratorInterface
{

    /**
     * @var \Symfony\Component\Routing\RouteCollection|null
     */
    protected $collection = null;

    /**
     * @var RequestContext
     */
    protected $context;

    /**
     * @var string
     */
    protected $resource;

    /**
     * @var LoaderInterface
     */
    protected $loader;

    /**
     * @var array
     */
    protected $cache;

    /**
     * @var UrlGenerator
     */
    protected $generator;

    /**
     * @var SlugServiceManager
     */
    protected $slugServiceManager;

    /**
     * @var SlugManager
     */
    protected $slugManager;

    /**
     * @var UrlMatcher
     */
    protected $matcher;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var array
     */
    protected $resources;

    /**
     * @var HostServiceManager
     */
    protected $hostServiceManager;

    /**
     * @param SlugServiceManager $slugServiceManager
     * @param SlugManager $slugManager
     * @param $resources
     * @param LoaderInterface $loader
     * @param HostServiceManager $hostServiceManager
     */
    public function __construct(
        SlugServiceManager $slugServiceManager,
        SlugManager $slugManager,
        $resources,
        LoaderInterface $loader,
        HostServiceManager $hostServiceManager
    )
    {
        $this->slugServiceManager = $slugServiceManager;
        $this->slugManager = $slugManager;
        $this->resources = $resources;
        $this->loader = $loader;
        $this->cache = [];
        $this->hostServiceManager = $hostServiceManager;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * {@inheritdoc}
     */
    public function setContext(RequestContext $context)
    {
        $this->context = $context;

        if (null !== $this->generator) {
            $this->getGenerator()->setContext($context);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @return \Symfony\Component\Routing\RouteCollection
     */
    public function getRouteCollection()
    {
        if (null === $this->collection) {
            $this->collection = new RouteCollection();
            foreach ($this->resources as $resource) {
                $this->collection->addCollection($this->loader->load($resource['resource'], $resource['type']));
            }
        }

        return $this->collection;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($name)
    {
        if (is_object($name) && $name instanceof Route) {
            return true;
        }

        if (is_string($name)) {
            return $this->getRouteCollection()->get($name) !== null;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function generate($name, $parameters = array(), $referenceType = self::ABSOLUTE_PATH)
    {
        return $this->getGenerator()->generate($name, $parameters, $referenceType);
    }

    public function getGenerator()
    {
        if (null !== $this->generator) {
            return $this->generator;
        }

        $this->generator = new UrlGenerator(
            $this->getRouteCollection(),
            $this->context,
            $this->slugServiceManager,
            $this->hostServiceManager
        );

        return $this->generator;
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteDebugMessage($name, array $parameters = array())
    {
        return "Route '$name' not found";
    }


    public function matchRequest(Request $request)
    {
        $matcher = $this->getMatcher();
        $match = $matcher->matchRequest($request);

        if (!$match) {
            throw new ResourceNotFoundException($request->getPathInfo());
        }

        if ($match['_controller'] === 'FrameworkBundle:Redirect:urlRedirect') {
            $routeName = $match['_route'];
            $controller = $match['_controller'];
            unset($match['_controller']);
            unset($match['_route']);
            $redirect = [
                '_controller' => $controller,
                '_route' => '',
                'path' => $this->getGenerator()->generate($routeName, $match),
                'params' => array(),
                'permanent' => true,
            ];

            return $redirect;
        }

        return $match;
    }

    public function getMatcher()
    {
        if (null !== $this->matcher) {
            return $this->matcher;
        }

        $this->matcher = new UrlMatcher(
            $this->getRouteCollection(),
            $this->context,
            $this->slugServiceManager,
            $this->slugManager,
            $this->hostServiceManager
        );

        return $this->matcher;
    }
}
