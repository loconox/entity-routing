<?php

namespace Loconox\EntityRoutingBundle\Tests\Slug\Service;

use Loconox\EntityRoutingBundle\Entity\Slug;

class BaseSlugServiceTest extends \PHPUnit_Framework_TestCase
{
    public function testGetClass()
    {
        $slugManager = $this->getMockBuilder('Loconox\EntityRoutingBundle\Entity\SlugManager')
                            ->disableOriginalConstructor()
                            ->getMock();
        $service = $this->getMockBuilder('Loconox\EntityRoutingBundle\Slug\Service\BaseSlugService')
                        ->setConstructorArgs(['FooClass', $slugManager])
                        ->setMethods(['setValues'])
                        ->getMockForAbstractClass();

        $this->assertEquals('FooClass', $service->getClass());
    }

    public function testIncrementSlug()
    {
        $slug    = new Slug();
        $newSlug = new Slug();

        $slugManager = $this->getMockBuilder('Loconox\EntityRoutingBundle\Entity\SlugManager')
                            ->disableOriginalConstructor()
                            ->getMock();
        $slugManager->expects($this->once())
                    ->method('create')
                    ->will($this->returnValue($newSlug));
        $service = $this->getMockBuilder('Loconox\EntityRoutingBundle\Slug\Service\BaseSlugService')
                        ->setConstructorArgs(['FooClass', $slugManager])
                        ->setMethods(['setValues'])
                        ->getMockForAbstractClass();
        $service->expects($this->any())
                ->method('setValues')
                ->will($this->returnValue(true));

        $entity = new \stdClass();


        $retSlug = $service->incrementSlug($entity, $slug);
        $this->assertEquals($retSlug, $slug->getNew());
        $this->assertEquals($slug, $retSlug->getOld());
    }

    public function testCreateSlug()
    {
        $newSlug = new Slug();

        $slugManager = $this->getMockBuilder('Loconox\EntityRoutingBundle\Entity\SlugManager')
                            ->disableOriginalConstructor()
                            ->getMock();
        $slugManager->expects($this->once())
                    ->method('create')
                    ->will($this->returnValue($newSlug));
        $service = $this->getMockBuilder('Loconox\EntityRoutingBundle\Slug\Service\BaseSlugService')
                        ->setConstructorArgs(['FooClass', $slugManager])
                        ->setMethods(['setValues'])
                        ->getMockForAbstractClass();
        $service->expects($this->any())
                ->method('setValues')
                ->will($this->returnValue(true));

        $entity = new \stdClass();

        $this->assertEquals($newSlug, $service->createSlug($entity, false));
    }

    public function testCreateSlugSave()
    {
        $newSlug = new Slug();

        $slugManager = $this->getMockBuilder('Loconox\EntityRoutingBundle\Entity\SlugManager')
                            ->disableOriginalConstructor()
                            ->getMock();
        $slugManager->expects($this->once())
                    ->method('create')
                    ->will($this->returnValue($newSlug));
        $slugManager->expects($this->once())
                    ->method('save')
                    ->with($this->equalTo($newSlug));
        $service = $this->getMockBuilder('Loconox\EntityRoutingBundle\Slug\Service\BaseSlugService')
                        ->setConstructorArgs(['FooClass', $slugManager])
                        ->setMethods(['setValues'])
                        ->getMockForAbstractClass();
        $service->expects($this->any())
                ->method('setValues')
                ->will($this->returnValue(true));

        $entity = new \stdClass();

        $this->assertEquals($newSlug, $service->createSlug($entity));
    }

    public function testUpdateSlug()
    {
        $slug   = new Slug();
        $entity = new \stdClass();

        $slugManager = $this->getMockBuilder('Loconox\EntityRoutingBundle\Entity\SlugManager')
                            ->disableOriginalConstructor()
                            ->getMock();
        $slugManager->expects($this->once())
                    ->method('save')
                    ->with($this->equalTo($slug));
        $service = $this->getMockBuilder('Loconox\EntityRoutingBundle\Slug\Service\BaseSlugService')
                        ->setConstructorArgs(['FooClass', $slugManager])
                        ->setMethods(['setValues', 'findSlug', 'hasChanged'])
                        ->getMockForAbstractClass();
        $service->expects($this->any())
                ->method('setValues')
                ->with($this->equalTo($slug), $this->equalTo($entity))
                ->will($this->returnValue(true));
        $service->expects($this->any())
                ->method('findSlug')
                ->with($this->equalTo($entity))
                ->will($this->returnValue($slug));
        $service->expects($this->any())
                ->method('hasChanged')
                ->will($this->returnValue(false));

        $service->updateSlug($entity);
    }

    public function testUpdateSlugChangedValue()
    {
        $slug   = new Slug();
        $entity = new \stdClass();

        $slugManager = $this->getMockBuilder('Loconox\EntityRoutingBundle\Entity\SlugManager')
                            ->disableOriginalConstructor()
                            ->getMock();

        $service = $this->getMockBuilder('Loconox\EntityRoutingBundle\Slug\Service\BaseSlugService')
                        ->setConstructorArgs(['FooClass', $slugManager])
                        ->setMethods(['incrementSlug', 'findSlug', 'hasChanged'])
                        ->getMockForAbstractClass();
        $service->expects($this->once())
                ->method('incrementSlug')
                ->with($this->equalTo($entity), $this->equalTo($slug))
                ->will($this->returnValue($slug));
        $service->expects($this->any())
                ->method('findSlug')
                ->with($this->equalTo($entity))
                ->will($this->returnValue($slug));
        $service->expects($this->any())
                ->method('hasChanged')
                ->will($this->returnValue(true));

        $service->updateSlug($entity);
    }

    /**
     * @dataProvider slugProvider
     */
    public function testSlugify($string, $expected)
    {
        $service = $this->getMockBuilder('Loconox\EntityRoutingBundle\Slug\Service\BaseSlugService')
                        ->disableOriginalConstructor()
                        ->getMockForAbstractClass();

        $method = new \ReflectionMethod('Loconox\EntityRoutingBundle\Slug\Service\BaseSlugService', 'slugify');
        $method->setAccessible(true);

        $this->assertEquals($expected, $method->invoke($service, $string));
    }

    public function slugProvider()
    {
        return [
            ['Iğdır Aras Spor', 'igdir-aras-spor'],
            ['Vitória Guimarães II', 'vitoria-guimaraes-ii'],
            ['Şanlıurfaspor', 'sanliurfaspor'],
            ['Třinec', 'trinec'],
            ['Brøndby', 'brondby'],
            ['Vålerenga', 'valerenga'],
            ['Amkar Perm\'', 'amkar-perm'],
            ['Spartak Nal\'chik', 'spartak-nal-chik'],
            ['Folgore / Falciano', 'folgore-falciano'],
            ['Serbie-Monténégro', 'serbie-montenegro'],
            ['Preußen Münster', 'preussen-munster'],
            ['Ægir', 'aegir'],
            ['Þór Þ', 'thor-th'],
            ['Łomża', 'lomza'],
            ['Jœuf', 'joeuf'],
        ];
    }
}