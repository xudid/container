<?php

namespace Xudid\Container;

use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionParameter;

Class Weaver
{
    protected array $instanciableClassForInterface = [];
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    private function get($id)
    {
        return $this->container->get($id);
    }

    private function has($id)
    {
        return $this->container->has($id);
    }


    public function make($class, $arguments = [])
    {
        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();
        if ($reflection->hasMethod('__construct')) {
            $args = $this->getArguments($constructor, $arguments);
            if ($args) {
                return $reflection->newInstanceArgs($args);
            }
        }
        return $reflection->newInstance();
    }

    protected function getArguments(?\ReflectionMethod $method, $arguments): array
    {
        $args = [];
        foreach ($method->getParameters() as $parameter) {
            $args[] = $this->getArgument($parameter, $arguments);
        }

        return $args;
    }

    protected function getArgument(ReflectionParameter $parameter, $arguments): mixed
    {
        if ($parameter->hasType()) {
            $parameterName = $parameter->getType()->getName();
        } else {
            $parameterName = '';
        }

        if ($parameterName && array_key_exists($parameterName, $arguments)) {
            return $arguments[$parameterName];
        }

        if ($parameterName && $this->has($parameterName)) {
            $arg = $this->get($parameterName);
            return $arg;
        } elseif (
            $parameterName
            && class_exists($parameterName)
        ) {
            $arg = $this->get($parameterName);
            return $arg;
        } elseif ($parameterName && $this->findInstantiableClassForInterface($parameterName)) {
            $arg = $this->findInstantiableClassForInterface($parameterName);
            return $this->get($arg);
        } elseif ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        } else {
            throw new AutoWireException("Can't auto-wire parameter without type and default value : " . $parameter->name);
        }
    }

    private function findInstantiableClassForInterface($interfaceName)
    {
        if (array_key_exists($interfaceName, $this->instanciableClassForInterface)) {
            return $this->instanciableClassForInterface[$interfaceName];
        }

        if (!in_array($interfaceName, get_declared_interfaces())) {
            return false;
        }

        foreach (get_declared_classes() as $class) {
            if (!$this->canInstantiate($class)) {
                continue;
            }

            $this->instanciableClassForInterface[$interfaceName] = $class;
            return $class;
        }

        return false;
    }

    private function canInstantiate($id): bool
    {
        $reflection = new ReflectionClass($id);
        if (!$reflection->isUserDefined()) {
            return false;
        }

        return $reflection->isInstantiable();
    }
}