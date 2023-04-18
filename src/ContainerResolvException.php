<?php

namespace Xudid\Container;

use Exception;
use Psr\Container\ContainerExceptionInterface;
use Throwable;

class ContainerResolvException extends Exception implements ContainerExceptionInterface
{
    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
