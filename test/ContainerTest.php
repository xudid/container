<?php

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Xudid\Container\AutoWireException;
use Xudid\Container\Container;
use Xudid\Container\NotFoundException;

class A {

}
class B {
    public function __construct(A $a, $b = 2) {

    }
}

class C {
    public function __construct(array $a)
    {
    }

}
class ContainerTest extends TestCase
{
    public function testImplementsContainerInterface()
    {
        $container = new Container();
        $this->assertInstanceOf(ContainerInterface::class, $container);
    }

    public function testHasReturnBool()
    {
        $container = new Container();
        $result = $container->has('a');
        $this->assertIsBool($result);
    }

    public function testSetIsFluent()
    {
        $container = new Container();
        $result = $container->set('a', 1);
        $this->assertInstanceOf(ContainerInterface::class, $result);
    }

    public function testCanSetMixedValueType()
    {
        $container = new Container();
        $this->expectNotToPerformAssertions();
        $container->set('a', 'aze');
        $container->set('a', 1);
        $container->set('a', []);
        $container->set('a', new StdClass());
    }

    public function testHasReturnTrueWhenKeyIsSet()
    {
        $container = new Container();
        $container->set('a', 1);
        $result = $container->has('a');
        $this->assertTrue($result);
    }

    public function testHasReturnFalseWhenNotKeyIsset()
    {
        $container = new Container();
        $result = $container->has('a');
        $this->assertFalse($result);
    }

    public function testGetNotSetValueThrowNotFoundException()
    {
        $container = new Container();
        $this->expectException(Xudid\Container\NotFoundException::class);
        $container->get('aze');
    }

    public function testNotFoundExceptionImplementsNotFoundExceptionInterface()
    {
        $container = new Container();
        $this->expectException(NotFoundExceptionInterface::class);
        $container->get('aze');
    }

    public function testGetReturnSetValue()
    {
        $container = new Container();
        $value = 1;
        $container->set('a', $value);
        $result = $container->get('a');
        $this->assertEquals($value, $result);
    }

    public function testGetReturnResultOfCallable()
    {
        $container = new Container();
        $callable = function () {
            return 1;
        };
        $value = $callable();
        $container->set('a', $callable);
        $result = $container->get('a');
        $this->assertEquals($value, $result);
    }

    public function testGetReturnAnObjectOfClassNameEqualToValueSet()
    {
        $container = new Container();
        $className = StdClass::class;
        $result = $container->get($className);
        $this->assertInstanceOf($className, $result);

        $className = B::class;
        $result = $container->get($className);
        $this->assertInstanceOf($className, $result);

        $className = C::class;
        $this->expectException(AutoWireException::class);
        $container->get($className);
    }

    public function testSetSingletonIsFluent()
    {
        $container = new Container();
        $result = $container->singleton('a', B::class);
        $this->assertInstanceOf(Container::class, $result);
    }

    public function testContainerHasSingleton()
    {
        $container = new Container();
        $container->singleton('a', B::class);
        $container->singleton('b', C::class);
        $this->assertTrue($container->has('a'));
    }

    public function testGetSingletonReturnSameObject()
    {
        $container = new Container();
        $container->singleton('a', B::class);
        $result1 = $container->get('a');
        $this->assertTrue($container->has('a'));
        $result2 = $container->get('a');
        $this->assertSame($result1, $result2);
    }
}