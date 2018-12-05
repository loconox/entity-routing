<?php

namespace Loconox\EntityRoutingBundle\Tests\Matcher;

use Loconox\EntityRoutingBundle\Entity\Slug;
use Loconox\EntityRoutingBundle\Entity\SlugManager;
use Loconox\EntityRoutingBundle\Host\HostServiceManager;
use Loconox\EntityRoutingBundle\Host\Service\AbstractHostService;
use Loconox\EntityRoutingBundle\Matcher\UrlMatcher;
use Loconox\EntityRoutingBundle\Route\RouteCompiler;
use Loconox\EntityRoutingBundle\Slug\Service\SlugServiceInterface;
use Loconox\EntityRoutingBundle\Slug\SlugServiceManager;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use PHPUnit\Framework\TestCase;

class UrlMatcherTest extends TestCase
{

    public function testMatch()
    {
        $type = 'bar';
        $slugValue = 'toto';
        $routeName = 'foo';
        $route = new Route('/{' . $type . '}');
        $slug = new Slug();
        $entity = new \stdClass();
        $expected = [
            '_route' => $routeName,
            $type => $entity,
        ];

        $routeCollection = new RouteCollection();
        $routeCollection->add($routeName, $route);

        $hostServiceManager = $this->getMockBuilder(HostServiceManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $context = $this->getMockBuilder(RequestContext::class)
            ->getMock();

        $slugManager = $this->getMockBuilder(SlugManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $slugManager->expects($this->atLeastOnce())
            ->method('findOneBy')
            ->with($this->equalTo(['type' => $type, 'slug' => $slugValue]))
            ->willReturn($slug);

        $slugService = $this->getMockBuilder(SlugServiceInterface::class)
            ->getMock();
        $slugService->expects($this->atLeastOnce())
            ->method('getEntity')
            ->with($this->equalTo($slug))
            ->willReturn($entity);
        $slugService
            ->method('getAlias')
            ->willReturn($type);

        $slugServiceManager = $this->getMockBuilder(SlugServiceManager::class)
            ->getMock();
        $slugServiceManager->expects($this->atLeastOnce())
            ->method('get')
            ->willReturnMap([
                [$type, $route, $slugService],
            ]);

        $matcher = new UrlMatcher($routeCollection, $context, $slugServiceManager, $slugManager, $hostServiceManager);

        $this->assertEquals($expected, $matcher->match('/' . $slugValue));
    }

    public function testMatchSameRegex()
    {
        $type1 = 'foo';
        $routeName1 = 'foo';
        $route1 = new Route('/{' . $type1 . '}');

        $type2 = 'bar';
        $routeName2 = 'bar';
        $route2 = new Route('/{' . $type2 . '}');

        $slug = new Slug();
        $slugValue = 'toto';
        $entity = new \stdClass();
        $expected = [
            '_route' => $routeName2,
            $type2 => $entity,
        ];

        $routeCollection = new RouteCollection();
        $routeCollection->add($routeName1, $route1);
        $routeCollection->add($routeName2, $route2);

        $hostServiceManager = $this->getMockBuilder(HostServiceManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $context = $this->getMockBuilder(RequestContext::class)
            ->getMock();

        $slugManager = $this->getMockBuilder(SlugManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $slugManager->expects($this->atLeastOnce())
            ->method('findOneBy')
            ->willReturnCallback(function ($params) use ($type2, $slugValue, $slug) {
                $type = $params['type'];
                $value = $params['slug'];
                if ($type === $type2 && $value === $slugValue) {
                    return $slug;
                }

                return null;
            });

        $slugService1 = $this->getMockBuilder(SlugServiceInterface::class)
            ->getMock();
        $slugService1->expects($this->never())
            ->method('getEntity');
        $slugService1
            ->method('getAlias')
            ->willReturn($type1);

        $slugService2 = $this->getMockBuilder(SlugServiceInterface::class)
            ->getMock();
        $slugService2->expects($this->atLeastOnce())
            ->method('getEntity')
            ->with($this->equalTo($slug))
            ->willReturn($entity);
        $slugService2
            ->method('getAlias')
            ->willReturn($type2);

        $slugServiceManager = $this->getMockBuilder(SlugServiceManager::class)
            ->getMock();
        $slugServiceManager->expects($this->atLeastOnce())
            ->method('get')
            ->willReturnMap([
                [$type1, $route1, $slugService1],
                [$type2, $route2, $slugService2],
            ]);

        $matcher = new UrlMatcher($routeCollection, $context, $slugServiceManager, $slugManager, $hostServiceManager);

        $this->assertEquals($expected, $matcher->match('/' . $slugValue));
    }

    public function testMatchRedirect()
    {
        $type = 'foo';
        $routeName = 'foo';
        $route = new Route('/{' . $type . '}');

        $newSlug = new Slug();
        $slug = new Slug();
        $slug->setNew($newSlug);
        $slugValue = 'toto';
        $entity = new \stdClass();
        $expected = [
            '_controller' => 'FrameworkBundle:Redirect:urlRedirect',
            '_route' => $routeName,
            $type => $entity,
        ];

        $routeCollection = new RouteCollection();
        $routeCollection->add($routeName, $route);

        $hostServiceManager = $this->getMockBuilder(HostServiceManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $context = $this->getMockBuilder(RequestContext::class)
            ->getMock();

        $slugManager = $this->getMockBuilder(SlugManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $slugManager->expects($this->atLeastOnce())
            ->method('findOneBy')
            ->with($this->equalTo(['type' => $type, 'slug' => $slugValue]))
            ->willReturn($slug);

        $slugService = $this->getMockBuilder(SlugServiceInterface::class)
            ->getMock();
        $slugService->expects($this->atLeastOnce())
            ->method('getEntity')
            ->with($this->equalTo($slug))
            ->willReturn($entity);
        $slugService
            ->method('getAlias')
            ->willReturn($type);


        $slugServiceManager = $this->getMockBuilder(SlugServiceManager::class)
            ->getMock();
        $slugServiceManager->expects($this->atLeastOnce())
            ->method('get')
            ->willReturnCallback(function ($t) use ($slugService, $type) {
                if ($type === $t) {
                    return $slugService;
                }

                return false;
            });

        $matcher = new UrlMatcher($routeCollection, $context, $slugServiceManager, $slugManager, $hostServiceManager);

        $this->assertEquals($expected, $matcher->match('/' . $slugValue));
    }

    public function testMatchSimpleRoute()
    {
        $routeName = 'foo';
        $route = new Route('/foo');
        $expected = [
            '_route' => $routeName,
        ];

        $routeCollection = new RouteCollection();
        $routeCollection->add($routeName, $route);

        $hostServiceManager = $this->getMockBuilder(HostServiceManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $context = $this->getMockBuilder(RequestContext::class)
            ->getMock();

        $slugManager = $this->getMockBuilder(SlugManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $slugManager->expects($this->never())
            ->method('findOneBy');

        $slugService = $this->getMockBuilder(SlugServiceInterface::class)
            ->getMock();
        $slugService->expects($this->never())
            ->method('getEntity');

        $slugServiceManager = $this->getMockBuilder(SlugServiceManager::class)
            ->getMock();
        $slugServiceManager->expects($this->any())
            ->method('get')
            ->with($this->anything())
            ->willReturn(false);

        $matcher = new UrlMatcher($routeCollection, $context, $slugServiceManager, $slugManager, $hostServiceManager);

        $this->assertEquals($expected, $matcher->match('/foo'));
    }

    public function testMatchHost()
    {

        $type = 'bar';
        $host = 'toto.fr';
        $routeName = 'foo';
        $route = new Route('/');
        $route->setHost('{'.$type.'}');
        $route->setOption('compiler_class', RouteCompiler::class);
        $entity = new \stdClass();
        $expected = [
            '_route' => $routeName,
            $type => $entity,
        ];

        $routeCollection = new RouteCollection();
        $routeCollection->add($routeName, $route);

        $hostService = $this->getMockBuilder(AbstractHostService::class)
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMockForAbstractClass();
        $hostService
            ->expects($this->atLeastOnce())
            ->method('findOneByHost')
            ->with($this->equalTo($host))
            ->willReturn($entity);

        $hostServiceManager = $this->getMockBuilder(HostServiceManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $hostServiceManager
            ->expects($this->atLeastOnce())
            ->method('get')
            ->willReturnCallback(function ($t) use ($hostService, $type) {
                if ($type === $t) {
                    return $hostService;
                }

                return false;
            });

        $context = $this->getMockBuilder(RequestContext::class)
            ->getMock();
        $context
            ->expects($this->atLeastOnce())
            ->method('getHost')
            ->willReturn($host);

        $slugManager = $this->getMockBuilder(SlugManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $slugServiceManager = $this->getMockBuilder(SlugServiceManager::class)
            ->getMock();

        $matcher = new UrlMatcher($routeCollection, $context, $slugServiceManager, $slugManager, $hostServiceManager);

        $this->assertEquals($expected, $matcher->match('/'));
    }

    /**
     * @expectedException \Symfony\Component\Routing\Exception\ResourceNotFoundException
     */
    public function testMatchWithHostMissmatch()
    {

        $type = 'bar';
        $otherHost = 'titi.fr';
        $routeName = 'foo';
        $route = new Route('/index');
        $route->setHost('{'.$type.'}');
        $route->setOption('compiler_class', RouteCompiler::class);

        $routeCollection = new RouteCollection();
        $routeCollection->add($routeName, $route);

        $hostService = $this->getMockBuilder(AbstractHostService::class)
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMockForAbstractClass();
        $hostService
            ->expects($this->atLeastOnce())
            ->method('findOneByHost')
            ->with($this->equalTo($otherHost))
            ->willReturn(null);

        $hostServiceManager = $this->getMockBuilder(HostServiceManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $hostServiceManager
            ->expects($this->atLeastOnce())
            ->method('get')
            ->willReturnCallback(function ($t) use ($hostService, $type) {
                if ($type === $t) {
                    return $hostService;
                }

                return false;
            });

        $context = $this->getMockBuilder(RequestContext::class)
            ->getMock();
        $context
            ->expects($this->atLeastOnce())
            ->method('getHost')
            ->willReturn($otherHost);

        $slugManager = $this->getMockBuilder(SlugManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $slugServiceManager = $this->getMockBuilder(SlugServiceManager::class)
            ->getMock();

        $matcher = new UrlMatcher($routeCollection, $context, $slugServiceManager, $slugManager, $hostServiceManager);

        $matcher->match('/index');
    }
}