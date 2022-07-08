<?php
declare(strict_types=1);

namespace Mvc4us\MiddleWare;

use Symfony\Component\DependencyInjection\TaggedContainerInterface;
use Symfony\Component\Routing\Router;

trait MiddlewareTrait
{
    private ?TaggedContainerInterface $container = null;

    public function getContainer(): ?TaggedContainerInterface
    {
        return $this->container;
    }

    public function setContainer(TaggedContainerInterface $container): void
    {
        $this->container = $this->container ?? $container;
    }

    /**
     * Gets Router from container
     *
     * @return \Symfony\Component\Routing\Router|null
     */
    protected function getRouter(): ?Router
    {
        $router = $this->container->get('router');
        if (!$router instanceof Router) {
            return null;
        }
        return $router;
    }
}
