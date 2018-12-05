<?php

namespace Loconox\EntityRoutingBundle\Matcher;

use Loconox\EntityRoutingBundle\Entity\SlugManager;
use Loconox\EntityRoutingBundle\Host\HostServiceManager;
use Loconox\EntityRoutingBundle\Host\Service\HostServiceInterface;
use Loconox\EntityRoutingBundle\Model\Slug;
use Loconox\EntityRoutingBundle\Slug\Service\SlugServiceInterface;
use Loconox\EntityRoutingBundle\Slug\SlugServiceManager;
use Symfony\Component\Routing\Matcher\RedirectableUrlMatcherInterface;
use Symfony\Component\Routing\Matcher\UrlMatcher as BaseUrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class UrlMatcher extends BaseUrlMatcher
{
    /**
     * @var SlugManager
     */
    protected $slugManager;

    /**
     * @var SlugServiceManager
     */
    protected $slugServiceManager;

    /**
     * @var HostServiceManager
     */
    protected $hostServiceManager;

    /**
     * Constructor.
     *
     * @param RouteCollection $routes A RouteCollection instance
     * @param RequestContext $context The context
     * @param SlugServiceManager $slugServiceManager
     * @param SlugManager $slugManager
     * @param HostServiceManager $hostServiceManager
     */
    public function __construct(
        RouteCollection $routes,
        RequestContext $context,
        SlugServiceManager $slugServiceManager,
        SlugManager $slugManager,
        HostServiceManager $hostServiceManager
    )
    {
        parent::__construct($routes, $context);
        $this->slugServiceManager = $slugServiceManager;
        $this->slugManager = $slugManager;
        $this->hostServiceManager = $hostServiceManager;
    }

    /**
     * @inheritdoc
     */
    protected
    function matchCollection($pathinfo, RouteCollection $routes)
    {
        $supportsTrailingSlash = '/' !== $pathinfo && '' !== $pathinfo && $this instanceof RedirectableUrlMatcherInterface;

        foreach ($routes as $name => $route) {
            $compiledRoute = $route->compile();
            $staticPrefix = $compiledRoute->getStaticPrefix();

            // check the static prefix of the URL first. Only use the more expensive preg_match when it matches
            if ('' === $staticPrefix || 0 === strpos($pathinfo, $staticPrefix)) {
                // no-op
            } elseif (!$supportsTrailingSlash) {
                continue;
            } elseif ('/' === $staticPrefix[-1] && substr($staticPrefix, 0, -1) === $pathinfo) {
                return;
            } elseif ('/' === $pathinfo[-1] && substr($pathinfo, 0, -1) === $staticPrefix) {
                return;
            } else {
                continue;
            }
            $regex = $compiledRoute->getRegex();

            if ($supportsTrailingSlash) {
                $pos = strrpos($regex, '$');
                $hasTrailingSlash = '/' === $regex[$pos - 1];
                $regex = substr_replace($regex, '/?$', $pos - $hasTrailingSlash, 1 + $hasTrailingSlash);
            }

            if (!preg_match($regex, $pathinfo, $matches)) {
                continue;
            }

            if ($supportsTrailingSlash && $hasTrailingSlash !== ('/' === $pathinfo[-1])) {
                return;
            }

            $hostMatches = array();
            if ($compiledRoute->getHostRegex() && !preg_match($compiledRoute->getHostRegex(), $this->context->getHost(), $hostMatches)) {
                continue;
            }

            $status = $this->handleRouteRequirements($pathinfo, $name, $route);

            if (self::REQUIREMENT_MISMATCH === $status[0]) {
                continue;
            }

            $hasRequiredScheme = !$route->getSchemes() || $route->hasScheme($this->context->getScheme());
            if ($requiredMethods = $route->getMethods()) {
                // HEAD and GET are equivalent as per RFC
                if ('HEAD' === $method = $this->context->getMethod()) {
                    $method = 'GET';
                }

                if (!\in_array($method, $requiredMethods)) {
                    if ($hasRequiredScheme) {
                        $this->allow = array_merge($this->allow, $requiredMethods);
                    }

                    continue;
                }
            }

            if (!$hasRequiredScheme) {
                $this->allowSchemes = array_merge($this->allowSchemes, $route->getSchemes());

                continue;
            }

            if (!empty($hostMatches) && !$this->matchHost($hostMatches, $route)) {
                continue;
            }

            $attributes = $this->getAttributes($route, $name, array_replace($matches, $hostMatches, isset($status[1]) ? $status[1] : array()));

            if (!$this->matchSlug($attributes, $route)) {
                continue;
            }

            return $attributes;
        }
    }

    protected
    function matchSlug(&$attributes, Route $route): bool
    {
        foreach ($attributes as $attr => &$value) {
            /** @var SlugServiceInterface $slugService */
            $slugService = $this->slugServiceManager->get($attr, $route);
            if (!$slugService) {
                continue;
            }
            $type = $slugService->getAlias();

            /** @var Slug $slug */
            $slug = $this->slugManager->findOneBy(
                [
                    'type' => $type,
                    'slug' => $value,
                ]
            );

            // Slug not found, maybe it's another route with the same regex
            if (!$slug) {
                return false;
            }

            // Slug found be there is a new one, do redirect
            if ($slug->getNew() !== null) {
                $attributes['_controller'] = 'FrameworkBundle:Redirect:urlRedirect';
            }
            $value = $slugService->getEntity($slug);

        }

        return true;
    }

    protected
    function matchHost(&$attributes, Route $route): bool
    {
        foreach ($attributes as $attr => &$value) {
            /** @var HostServiceInterface $hostService */
            $hostService = $this->hostServiceManager->get($attr, $route);
            if (!$hostService) {
                continue;
            }

            $entity = $hostService->findOneByHost($value);
            if ($entity === null) {
                return false;
            }

            $value = $entity;
        }

        return true;
    }
}