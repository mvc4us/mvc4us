<?php

declare(strict_types=1);

namespace Mvc4us;

use Mvc4us\Config\Config;
use Mvc4us\DependencyInjection\Loader\ServiceLoader;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;

/**
 * @author erdem
 */
class Mvc4us
{

    private const RUN_CMD = 1;

    private const RUN_WEB = 2;

    private ?ContainerInterface $container = null;

    private string $projectDir;

    public function __construct($projectDir, $environment = null)
    {
        $this->projectDir = $projectDir;
        $this->reload($environment);
    }

    public function reload($environment = null)
    {
        Config::load($this->projectDir, $environment);

        $this->container = ServiceLoader::load($this->projectDir);
    }

    public function runCmd($controllerName, ?Request $request = null, $echo = false): ?string
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

    private function run($controllerName, ?Request $request, $run): ?Response
    {
        $e = null;
        try {
            if ($this->container === null) {
                $this->reload();
            }

            if ($run === self::RUN_WEB && $controllerName === null) {
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
            if ($controllerName == null) {
                throw new ResourceNotFoundException('Controller is not specified.');
            }

            /**
             * @var \Mvc4us\Controller\AbstractController $controller
             */
            $controller = $this->container->get($controllerName);
            $controller->setContainer($this->container);
            $response = $controller->handle($request);
        } catch (ResourceNotFoundException $e) {
            $response = new Response('', Response::HTTP_NOT_FOUND);
            if ($run === self::RUN_WEB) {
                $message = sprintf('No routes found for "%s %s"', $request->getMethod(), $request->getPathInfo());

                if ($referer = $request->headers->get('referer')) {
                    $message .= sprintf(' (Referer: "%s")', $referer);
                }

                $reflectionObject = new \ReflectionObject($e);
                $reflectionObjectProp = $reflectionObject->getProperty('message');
                $reflectionObjectProp->setAccessible(true);
                $reflectionObjectProp->setValue($e, $message);
            }
        } catch (MethodNotAllowedException $e) {
            $response = new Response('', Response::HTTP_METHOD_NOT_ALLOWED);
            $message = sprintf(
                'No routes found for "%s %s". Method Not Allowed (Allow: %s)',
                $request->getMethod(),
                $request->getPathInfo(),
                implode(', ', $e->getAllowedMethods())
            );

            $reflectionObject = new \ReflectionObject($e);
            $reflectionObjectProp = $reflectionObject->getProperty('message');
            $reflectionObjectProp->setAccessible(true);
            $reflectionObjectProp->setValue($e, $message);
        } catch (ServiceNotFoundException $e) {
            $response = new Response('', Response::HTTP_NOT_FOUND);
        } catch (InvalidArgumentException $e) {
            $response = new Response('', Response::HTTP_SERVICE_UNAVAILABLE);
        } catch (ServiceCircularReferenceException $e) {
            $response = new Response('', Response::HTTP_SERVICE_UNAVAILABLE);
        } catch (\TypeError $e) {
            $response = new Response('', Response::HTTP_SERVICE_UNAVAILABLE);
        } catch (\Exception $e) {
            $response = new Response('', Response::HTTP_SERVICE_UNAVAILABLE);
        } catch (\Error $e) {
            $response = new Response('', Response::HTTP_SERVICE_UNAVAILABLE);
        }

        if ($e !== null) {
            $request->attributes->set('exception', $e);
            if ($this->container->has('logger')) {
                // TODO logger service
            } else {
                error_log(sprintf("%s\n  thrown in %s on line %s", $e, $e->getFile(), $e->getLine()));
            }
        }
        $response = $response ?? new Response();
        $response->prepare($request);
        return $response;
    }
}
