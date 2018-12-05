<?php

namespace Loconox\EntityRoutingBundle\Tests\Service;

use Loconox\EntityRoutingBundle\Service\AbstractServiceManager;
use Loconox\EntityRoutingBundle\Service\ServiceInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Route;

class AbstractServiceManagerTest extends TestCase
{
    public function testAdd(): void
    {
        $fooService = $this->getMockBuilder(ServiceInterface::class)->getMock();
        /** @var AbstractServiceManager $manager */
        $manager = $this->getMockForAbstractClass(AbstractServiceManager::class);

        $this->assertCount(0, $manager->getAll());
        $manager->add($fooService, 'adw.service.foo', 'foo');
        $this->assertCount(1, $manager->getAll());
    }

    public function testGet(): void
    {
        $fooService = $this->getMockBuilder(ServiceInterface::class)->getMock();
        $fooService
            ->expects($this->once())
            ->method('getClass')
            ->willReturn(get_class(new FooClass()));
        /** @var AbstractServiceManager $manager */
        $manager = $this->getMockForAbstractClass(AbstractServiceManager::class);

        $manager->add($fooService, 'adw.service.foo', 'foo');
        $this->assertEquals($fooService, $manager->get('foo'));
        $this->assertEquals($fooService, $manager->get(new FooClass()));
    }

    public function testGetUnknownService(): void
    {
        /** @var AbstractServiceManager $manager */
        $manager = $this->getMockForAbstractClass(AbstractServiceManager::class);

        $this->assertFalse($manager->get('foo'));
        $this->assertFalse($manager->get(new FooClass()));
    }

    public function testGetUnknownServiceWithRoute(): void
    {
        /** @var AbstractServiceManager $manager */
        $manager = $this->getMockForAbstractClass(AbstractServiceManager::class);

        $route2 = new Route('/');
        $route2->setOption('types', ['bar' => 'baz']);

        $route3 = new Route('/');
        $route3->setOption('types', []);

        $this->assertFalse($manager->get('foo', new Route('/')));
        $this->assertFalse($manager->get(new FooClass(), $route2));
        $this->assertFalse($manager->get('slip', $route3));
    }

    public function testGetArrayClass(): void
    {
        $foobarService = $this->getMockBuilder(ServiceInterface::class)->getMock();
        $foobarService->expects($this->once())
            ->method('getClass')
            ->willReturn([get_class(new TotoClass()), get_class(new BarClass())]);
        /** @var AbstractServiceManager $manager */
        $manager = $this->getMockForAbstractClass(AbstractServiceManager::class);

        $manager->add($foobarService, 'adw.service.foobar', 'foobar');
        $this->assertEquals($foobarService, $manager->get('foobar'));
        $this->assertEquals($foobarService, $manager->get([new FooClass(), new BarClass()]));
    }

    public function testGetClassWithServiceArrayClass(): void
    {
        $foobarService = $this->getMockBuilder(ServiceInterface::class)->getMock();
        $foobarService->expects($this->once())
            ->method('getClass')
            ->willReturn([get_class(new TotoClass()), get_class(new FooClass())]);
        /** @var AbstractServiceManager $manager */
        $manager = $this->getMockForAbstractClass(AbstractServiceManager::class);

        $manager->add($foobarService, 'adw.service.foobar', 'foobar');
        $this->assertFalse($manager->get(new FooClass()));
    }

    public function testGetArrayClassWithServiceClass(): void
    {
        $foobarService = $this->getMockBuilder(ServiceInterface::class)->getMock();
        $foobarService->expects($this->once())
            ->method('getClass')
            ->willReturn(get_class(new FooClass()));
        /** @var AbstractServiceManager $manager */
        $manager = $this->getMockForAbstractClass(AbstractServiceManager::class);

        $manager->add($foobarService, 'adw.service.foobar', 'foobar');
        $this->assertFalse($manager->get([new FooClass(), new FooClass()]));
    }

    public function testGetArrayClassWrongOrder(): void
    {
        $foobarService = $this->getMockBuilder(ServiceInterface::class)->getMock();
        $foobarService->expects($this->once())
            ->method('getClass')
            ->willReturn([get_class(new BarClass()), get_class(new TotoClass())]);
        /** @var AbstractServiceManager $manager */
        $manager = $this->getMockForAbstractClass(AbstractServiceManager::class);

        $manager->add($foobarService, 'adw.service.foobar', 'foobar');
        $this->returnValue($foobarService, $manager->get([new FooClass(), new BarClass()]));
    }

    public function testGetWithRoute(): void
    {
        $id = 'bundle.service.id';
        $alias = 'alias';
        $type = 'foo';
        $fooService = $this->getMockBuilder(ServiceInterface::class)->getMock();

        /** @var AbstractServiceManager $manager */
        $manager = $this->getMockForAbstractClass(AbstractServiceManager::class);
        $manager->add($fooService, $id, $alias);

        $route = new Route('/');
        $route->setOption('types', [$type => $id]);

        $this->assertSame($fooService, $manager->get($type, $route));
    }

    public function testGetWithRouteByAlias(): void
    {
        $id = 'bundle.service.id';
        $alias = 'alias';
        $type = 'foo';
        $fooService = $this->getMockBuilder(ServiceInterface::class)->getMock();

        /** @var AbstractServiceManager $manager */
        $manager = $this->getMockForAbstractClass(AbstractServiceManager::class);
        $manager->add($fooService, $id, $alias);

        $route = new Route('/');
        $route->setOption('types', [$type => $alias]);

        $this->assertSame($fooService, $manager->get($type, $route));
    }
}

class TotoClass
{
}

class FooClass extends TotoClass
{
}

class BarClass
{
}