<?php

declare(strict_types=1);

namespace Mvc4us;

use Mvc4us\Config\Config;
use Mvc4us\Controller\ControllerInterface;
use Mvc4us\Controller\Exception\CircularForwardException;
use Mvc4us\DependencyInjection\ServiceContainer;
use Mvc4us\Logger\LoggerConfig;
use Mvc4us\MiddleWare\Exception\MiddlewareException;
use Mvc4us\MiddleWare\MiddlewareConstants;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\TaggedContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\RequestContext;

/**
 * @author erdem
 */
class Mvc4us
{

    public const RUN_CMD = 1;

    public const RUN_WEB = 2;

    private ?TaggedContainerInterface $container = null;

    private string $projectDir;

    public function __construct(string $projectDir, ?string $environment = null)
    {
        $this->projectDir = $projectDir;
        $this->reload($environment);
    }

    public function reload(?string $environment = null): void
    {
        Config::load($this->projectDir, $environment);

        $this->container = ServiceContainer::load($this->projectDir);
    }

    public function runCmd(string $controllerName, ?Request $request = null, bool $echo = false): ?string
    {
        $request = $request ?? new Request($_SERVER['argv']);
        $request->setMethod('CLI');
        $response = $this->run($controllerName, $request, self::RUN_CMD);
        if ($echo) {
            echo $response->getContent() . PHP_EOL;
        }
        return $response->getContent();
    }

    public function runWeb(?Request $request = null): ?Response
    {
        $request = $request ?? Request::createFromGlobals();
        $response = $this->run(null, $request, self::RUN_WEB);
        if (PHP_SAPI === 'cli') {
            return $response;
        }
        $response->send();
        return null;
    }

    private function run(?string $controllerName, Request $request, int $runMode): ?Response
    {
        $e = null;
        if ($this->container === null) {
            $this->reload();
        }
        /**
         * @var \Symfony\Component\HttpFoundation\RequestStack $requestStack
         */
        $requestStack = $this->container->get('request_stack');
        // TODO: Check if below flag is really needed (when run as memory resident application server).
        $popRequest = false;

        try {
            $request->attributes->set('_runMode', $runMode);
            $requestStack->push($request);
            $popRequest = true;
            //TODO: try to implement a matcher for command
            if ($runMode === self::RUN_WEB) {
                try {
                    $beforeMatcherMiddlewares = $this->container->findTaggedServiceIds(
                        MiddlewareConstants::BEFORE_MATCHER
                    );
                    foreach ($beforeMatcherMiddlewares as $id => $tags) {
                        /**
                         * @var \Mvc4us\MiddleWare\BeforeMatcherInterface $beforeMatcherMiddleware
                         */
                        $beforeMatcherMiddleware = $this->container->get($id);
                        if (method_exists($beforeMatcherMiddleware, 'setContainer')) {
                            $beforeMatcherMiddleware->setContainer($this->container);
                        }
                        $response = $beforeMatcherMiddleware->processBeforeMatcher($requestStack);
                        if ($response !== null) {
                            goto skipController;
                        }
                    }
                } catch (\Throwable $e) {
                    throw new MiddlewareException($e);
                }

                if ($controllerName === null) {
                    /**
                     * @var \Symfony\Component\Routing\Router $router
                     */
                    $router = $this->container->get('router');

                    $context = new RequestContext();
                    $context->fromRequest($request);
                    $router->setContext($context);
                    $matcher = $router->getMatcher();

                    if ($matcher instanceof RequestMatcherInterface) {
                        $parameters = $matcher->matchRequest($request);
                    } else {
                        $parameters = $matcher->match($request->getPathInfo());
                    }

                    $request->attributes->add($parameters);

                    $controllerName = $request->attributes->get('_controller');
                }
            }

            if ($controllerName === null) {
                throw new ResourceNotFoundException('Controller is not specified.');
            }

            $methodName = 'handle';
            if (is_array($controllerName)) {
                [$controllerName, $methodName] = $controllerName;
            }

            /**
             * @var ControllerInterface $controller
             */
            $controller = $this->container->get($controllerName);
            if (method_exists($controller, 'setContainer')) {
                $controller->setContainer($this->container);
            }

            try {
                $beforeMiddlewares = $this->container->findTaggedServiceIds(MiddlewareConstants::BEFORE_CONTROLLER);
                foreach ($beforeMiddlewares as $id => $tags) {
                    /**
                     * @var \Mvc4us\MiddleWare\BeforeControllerInterface $beforeMiddleware
                     */
                    $beforeMiddleware = $this->container->get($id);
                    if (method_exists($beforeMiddleware, 'setContainer')) {
                        $beforeMiddleware->setContainer($this->container);
                    }
                    $response = $beforeMiddleware->processBefore($requestStack);
                    if ($response !== null) {
                        goto skipController;
                    }
                }
            } catch (\Throwable $e) {
                throw new MiddlewareException($e);
            }

            if (is_callable($controller)) {
                $response = $controller($request);
            } else {
                $response = $controller->$methodName($request);
            }
        } catch (ResourceNotFoundException $e) {
            $response = new Response('', Response::HTTP_NOT_FOUND);
            if ($runMode === self::RUN_WEB) {
                $message = sprintf('No routes found for "%s %s"', $request->getMethod(), $request->getPathInfo());

                if ($referer = $request->headers->get('referer')) {
                    $message .= sprintf(' (Referer: "%s")', $referer);
                }

                $reflectionObject = new \ReflectionObject($e);
                $reflectionObjectProp = $reflectionObject->getProperty('message');
                //$reflectionObjectProp->setAccessible(true);
                $reflectionObjectProp->setValue($e, $message);
            }
        } catch (MethodNotAllowedException $e) {
            $response = new Response('', Response::HTTP_METHOD_NOT_ALLOWED);
            $message = sprintf(
                'Method "%s" not allowed for route "%s". (Allow: %s)',
                $request->getMethod(),
                $request->getPathInfo(),
                implode(', ', $e->getAllowedMethods())
            );

            $reflectionObject = new \ReflectionObject($e);
            $reflectionObjectProp = $reflectionObject->getProperty('message');
            //$reflectionObjectProp->setAccessible(true);
            $reflectionObjectProp->setValue($e, $message);
        } catch (ServiceNotFoundException $e) {
            $response = new Response('', Response::HTTP_NOT_FOUND);
        } catch (InvalidArgumentException|ServiceCircularReferenceException|CircularForwardException $e) {
            $response = new Response('', Response::HTTP_SERVICE_UNAVAILABLE);
//        } catch (\TypeError $e) {
//            $response = new Response('', Response::HTTP_SERVICE_UNAVAILABLE);
//        } catch (\Exception $e) {
//            $response = new Response('', Response::HTTP_SERVICE_UNAVAILABLE);
//        } catch (\Error $e) {
//            $response = new Response('', Response::HTTP_SERVICE_UNAVAILABLE);
        }

        skipController:
        if ($e !== null) {
            $request->attributes->set('exception', $e);
            LoggerConfig::getInstance()->error(
                sprintf("%s\n  thrown in %s on line %s", $e, $e->getFile(), $e->getLine())
            );
        }

        $response = $response ?? new Response();
        $response->prepare($request);

        try {
            $afterMiddlewares = $this->container->findTaggedServiceIds(MiddlewareConstants::AFTER_CONTROLLER);
            foreach ($afterMiddlewares as $id => $tags) {
                /**
                 * @var \Mvc4us\MiddleWare\AfterControllerInterface $afterMiddleware
                 */
                $afterMiddleware = $this->container->get($id);
                if (method_exists($afterMiddleware, 'setContainer')) {
                    $afterMiddleware->setContainer($this->container);
                }
                if (!$afterMiddleware->processAfter($requestStack, $response)) {
                    break;
                }
            }
        } catch (\Throwable $e) {
            throw new MiddlewareException($e);
        }

        if ($popRequest) {
            $requestStack->pop();
        }
        return $response;
    }
}
