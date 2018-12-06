<?php

namespace Loconox\EntityRoutingBundle\Tests\Route;

use Loconox\EntityRoutingBundle\Generator\UrlGenerator;
use Loconox\EntityRoutingBundle\Matcher\UrlMatcher;
use Loconox\EntityRoutingBundle\Model\SlugManagerInterface;
use Loconox\EntityRoutingBundle\Route\SlugRouter;
use Loconox\EntityRoutingBundle\Slug\SlugServiceManager;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use PHPUnit\Framework\TestCase;

class SlugRouterTest extends TestCase
{
    /**
     * @var SlugServiceManager
     */
    protected $slugServiceManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $slugManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $loader;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $routeResolverManager;

    public function setUp()
    {
        $this->slugServiceManager = $this->getMockBuilder(SlugServiceManager::class);
        $this->slugManager        = $this->getMockBuilder(SlugManagerInterface::class)->getMock();
        $this->loader             = $this->getMockBuilder(LoaderInterface::class)->getMock();
    }

    public function testSupports()
    {
        $route      = new Route('/foo');
        $collection = new RouteCollection();
        $collection->add('foo', $route);

        $router = $this->getMockBuilder(SlugRouter::class)
                       ->disableOriginalConstructor()
                       ->setMethods(['getRouteCollection'])
                       ->getMock();
        $router->expects($this->atLeastOnce())
               ->method('getRouteCollection')
               ->willReturn($collection);

        $this->assertTrue($router->supports('foo'));
        $this->assertTrue($router->supports($route));
        $this->assertFalse($router->supports('bar'));
    }

    public function testMatchRedirect()
    {
        $path        = '/foo';
        $routeName   = 'foo';
        $routeParams = ['bar' => 42];
        $match       = [
            '_controller' => 'FrameworkBundle:Redirect:urlRedirect',
            '_route'      => $routeName,
        ];
        $match       = array_merge($match, $routeParams);

        $expected = [
            '_controller' => 'FrameworkBundle:Redirect:urlRedirect',
            '_route'      => '',
            'path'        => $path,
            'params'      => [],
            'permanent'   => true,
        ];
        $request  = $this->getMockBuilder(Request::class)
                         ->disableOriginalConstructor()
                         ->getMock();
        $matcher  = $this->getMockBuilder(UrlMatcher::class)
                         ->disableOriginalConstructor()
                         ->getMock();
        $matcher->expects($this->once())
                ->method('matchRequest')
                ->with($this->equalTo($request))
                ->willReturn($match);
        $generator = $this->getMockBuilder(UrlGenerator::class)
                          ->disableOriginalConstructor()
                          ->getMock();
        $generator->expects($this->once())
                  ->method('generate')
                  ->with($this->equalTo($routeName), $this->equalTo($routeParams))
                  ->willReturn($path);

        $router = $this->getMockBuilder(SlugRouter::class)
                       ->setMethods(['getMatcher', 'getGenerator'])
                       ->disableOriginalConstructor()
                       ->getMock();
        $router->expects($this->atLeastOnce())
               ->method('getMatcher')
               ->willReturn($matcher);
        $router->expects($this->atLeastOnce())
               ->method('getGenerator')
               ->willReturn($generator);

        $this->assertEquals($expected, $router->matchRequest($request));
    }

    public function testDontMatch()
    {
        $request  = $this->getMockBuilder(Request::class)
                         ->disableOriginalConstructor()
                         ->getMock();
        $matcher  = $this->getMockBuilder(UrlMatcher::class)
                         ->disableOriginalConstructor()
                         ->getMock();
        $matcher->expects($this->once())
                ->method('matchRequest')
                ->with($this->equalTo($request))
                ->willReturn(null);

        $router = $this->getMockBuilder(SlugRouter::class)
                       ->setMethods(['getMatcher'])
                       ->disableOriginalConstructor()
                       ->getMock();
        $router->expects($this->atLeastOnce())
               ->method('getMatcher')
               ->willReturn($matcher);

        $this->expectException(ResourceNotFoundException::class);
        $router->matchRequest($request);
    }
}