<?php

namespace Xudid\Container;

use Exception;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;

class Container implements ContainerInterface
{
    private array $values = [];
    private array $singleton = [];

    public function get(string $id): mixed
    {
        try {
            if ($this->canAutoWire($id)) {
                $return = $this->autoWire($id);

                if ($this->hasSingleton($id)) {
                    $this->set($id, $return);
                }

                return $return;
            }

            if (!$this->hasValue($id)) {
                throw new NotFoundException();
            }

            $value = $this->values[$id];
            if ($this->hasCallable($id)) {
                return $value();
            }

            return $value;
        } catch (Exception $ex) {
           if (get_class($ex) == ReflectionException::class) {
               throw new ContainerResolvException();
           }
           throw $ex;
        }
    }

    public function has(string $id): bool
    {
        if ($this->hasSingleton($id)) {
            return true;
        }

        if ($this->hasValue($id)) {
            return true;
        }

        return false;
    }

    private function hasValue($id): bool
    {
        return array_key_exists($id, $this->values);
    }

    private function hasCallable($id): bool
    {
        if (!$this->hasValue($id)) {
            return false;
        }
        $value = $this->values[$id];
        return is_callable($value);
    }

    private function hasSingleton($id): bool
    {
        return array_key_exists($id, $this->singleton);
    }

    private function canAutoWire($id)
    {
        return !$this->hasValue($id) && class_exists($id);
    }

    private function autoWire($id)
    {
        $reflection = new ReflectionClass($id);
        if (!$reflection->isInstantiable()) {
            throw new ContainerResolvException("Can't resolve not instantiable class");
        }

        return $this->getInstance($id);
    }

    public function set(string $id, mixed $value)
    {
        $this->values[$id] = $value;
        return $this;
    }

    public function singleton(string $id, mixed $value): static
    {
        $this->singleton[$id] = $value;
        return $this;
    }

    private function getInstance($id)
    {
        $reflection = new ReflectionClass($id);
        $constructor = $reflection->getConstructor();
        if ($reflection->hasMethod('__construct')) {
            $args = $this->getArguments($constructor);
            if ($args) {
                return $reflection->newInstanceArgs($args);
            }
        }
        return $reflection->newInstance();
    }

    private function getArguments(?\ReflectionMethod $method): array
    {
        $args = [];
        foreach ($method->getParameters() as $parameter) {
            $args[] = $this->getArgument($parameter);
        }

        return $args;
    }


    private function getArgument(ReflectionParameter $parameter): mixed
    {
        if (
            $parameter->hasType()
            && class_exists($parameter->getType()->getName())
        ) {
            $arg = $this->get($parameter->getType()->getName());
            return $arg;
        } elseif($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        } else {
            throw new AutoWireException("Can't auto-wire parameter without type and default value");
        }
    }
}
