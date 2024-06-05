<?php
declare(strict_types=1);

namespace Mvc4us\Routing;

use Symfony\Component\Routing\Route;

/**
 * @internal
 */
class AttributeClassLoader extends \Symfony\Component\Routing\Loader\AttributeClassLoader
{
    protected function configureRoute(
        Route $route,
        \ReflectionClass $class,
        \ReflectionMethod $method,
        object $annot
    ): void {
        if ($method->getName() === '__invoke' || $method->getName() === 'handle') {
            $route->setDefault('_controller', $class->getName());
        } else {
            $route->setDefault('_controller', [$class->getName(), $method->getName()]);
        }
    }
}
