<?php

declare(strict_types=1);

namespace Mvc4us\Controller;

use Mvc4us\Config\Config;
use Mvc4us\Controller\Exception\CircularForwardException;
use Symfony\Component\DependencyInjection\TaggedContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;

/**
 * @author erdem
 */
abstract class AbstractController implements ControllerInterface
{
    private ?TaggedContainerInterface $container = null;

    private static array $callStack = [];

    public function setContainer(TaggedContainerInterface $container): self
    {
        $this->container = $this->container ?? $container;
        return $this;
    }

    /**
     * Returns true if the service id is defined.
     */
    protected function has(string $id): bool
    {
        return $this->container->has($id);
    }

    /**
     * Gets a container service by its id.
     *
     * @return object|null The service
     */
    protected function get(string $id): ?object
    {
        return $this->container->get($id);
    }

    /**
     * Gets Router from container
     *
     * @return \Symfony\Component\Routing\Router|null
     */
    protected function getRouter(): ?Router
    {
        return $this->container->get('router');
    }

    /**
     * Gets a controller from container
     *
     * @param string $controllerName
     * @return \Mvc4us\Controller\AbstractController|null
     */
    protected function getController(string $controllerName): ?AbstractController
    {
        return $this->container->get($controllerName)?->setContainer($this->container);
    }

    /**
     * Generates a URL from the given parameters.
     *
     * @see \Symfony\Component\Routing\Generator\UrlGeneratorInterface
     */
    protected function generateUrl(
        string $route,
        array $parameters = [],
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string {
        return $this->getRouter()->generate($route, $parameters, $referenceType);
    }

    /**
     * Forwards the request to another controller.
     *
     * @param string $controllerName
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Mvc4us\Controller\Exception\CircularForwardException
     */
    protected function forward(string $controllerName, Request $request): Response
    {
        self::$callStack[static::class] = (self::$callStack[static::class] ?? 0) + 1;

        $maxStack = Config::get('app', 'controller_forward_limit') ?? 10;
        if ((self::$callStack[$controllerName] ?? 0) > $maxStack) {
            throw new CircularForwardException(sprintf('Maximum forward recursion of %d reached.', $maxStack));
        }
        $response = $this->getController($controllerName)->handle($request->duplicate());
        self::$callStack[static::class]--;
        return $response;
    }

    /**
     * Returns true if current call is a forwarded call.
     *
     * @return bool
     */
    protected function isForwarded(): bool
    {
        return !empty(self::$callStack);
    }

    /**
     * Returns a RedirectResponse to the given URL.
     *
     * @param string $url
     * @param int $status
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function redirect(string $url, int $status = 302): RedirectResponse
    {
        return new RedirectResponse($url, $status);
    }

    /**
     * Returns a RedirectResponse to the given route with the given parameters.
     *
     * @param string $route     The name of the route
     * @param array $parameters An array of parameters
     * @param int $status       The status code to use for the Response
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function redirectToRoute(string $route, array $parameters = [], int $status = 302): RedirectResponse
    {
        return $this->redirect($this->generateUrl($route, $parameters), $status);
    }

    /**
     * Parses a json string and returns as an object or populates an object instance using Symfony Serializer
     *
     * @param string $json          A json string
     * @param object|string $object An object instance or type of object
     * @param array $context        Context to pass to serializer when using serializer component
     * @return mixed
     */
    protected function parseJson(string $json, object|string $object, array $context = []): mixed
    {
        if (!$this->container->has('serializer')) {
            throw new \LogicException(
                'Serializer not found. Try running "composer require symfony/serializer".'
            );
        }
        return $this->container->get('serializer')->parseJson($json, $object, $context);
    }

    /**
     * Returns a JsonResponse that uses the serializer component if enabled, or json_encode.
     *
     * @param mixed $data    The response data
     * @param int $status    The status code to use for the Response
     * @param array $headers Array of extra headers to add
     * @param array $context Context to pass to serializer when using serializer component
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function json(mixed $data, int $status = 200, array $headers = [], array $context = []): JsonResponse
    {
        if (!$this->container->has('serializer')) {
            return new JsonResponse($data, $status, $headers);
        }
        return new JsonResponse($this->container->get('serializer')->json($data, $context), $status, $headers, true);
    }

    /**
     * Returns a BinaryFileResponse object with original or customized file name and disposition header.
     *
     * @param \SplFileInfo|string $file File object or path to file to be sent as response
     * @param string|null $fileName     File name to be sent to response or null (will use original file name)
     * @param string $disposition       Disposition of response ("attachment" is default, other type is "inline")
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    protected function file(
        \SplFileInfo|string $file,
        string $fileName = null,
        string $disposition = ResponseHeaderBag::DISPOSITION_ATTACHMENT
    ): BinaryFileResponse {
        $response = new BinaryFileResponse($file);
        $response->setContentDisposition(
            $disposition,
            $fileName === null ? $response->getFile()->getFilename() : $fileName
        );

        return $response;
    }

    /**
     * Adds a flash message to the current session for type.
     *
     * @param string $type    The type
     * @param string $message The message
     * @throws \LogicException
     */
    protected function addFlash(string $type, string $message)
    {
        if (!$this->container->has('session')) {
            throw new \LogicException(
                'You can not use the addFlash method if sessions are disabled. Enable them in "config/packages/framework.yaml".'
            );
        }

        $this->container->get('session')->getFlashBag()->add($type, $message);
    }

    /**
     * Returns a rendered view.
     *
     * @param string $view      The view name
     * @param array $parameters An array of parameters to pass to the view
     * @return string The rendered view
     */
    protected function renderView(string $view, array $parameters = []): string
    {
        if ($this->container->has('templating')) {
            return $this->container->get('templating')->render($view, $parameters);
        }

        if (!$this->container->has('twig')) {
            throw new \LogicException(
                'You can not use the "renderView" method if the Templating Component or the Twig Component are not available. Try running "composer require twig/twig".'
            );
        }

        return $this->container->get('twig')->render($view, $parameters);
    }

    /**
     * Renders a view.
     *
     * @param string $view                                              The view name
     * @param array $parameters                                         An array of parameters to pass to the view
     * @param \Symfony\Component\HttpFoundation\Response|null $response A response instance
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function render(string $view, array $parameters = [], ?Response $response = null): Response
    {
        if ($this->container->has('templating')) {
            $content = $this->container->get('templating')->render($view, $parameters);
        } elseif ($this->container->has('twig')) {
            $content = $this->container->get('twig')->render($view, $parameters);
        } else {
            throw new \LogicException(
                'You can not use the "render" method if the Templating Component or the Twig Component are not available. Try running "composer require twig/twig".'
            );
        }

        if ($response === null) {
            $response = new Response();
        }

        $response->setContent($content);

        return $response;
    }

    // /**
    // * Streams a view.
    // *
    // * @
    // */
    // protected function stream(string $view, array $parameters = array(), StreamedResponse $response = null): StreamedResponse
    // {
    // if ($this->container->has('templating')) {
    // $templating = $this->container->get('templating');

    // $callback = function () use ($templating, $view, $parameters) {
    // $templating->stream($view, $parameters);
    // };
    // } elseif ($this->container->has('twig')) {
    // $twig = $this->container->get('twig');

    // $callback = function () use ($twig, $view, $parameters) {
    // $twig->display($view, $parameters);
    // };
    // } else {
    // throw new \LogicException(
    // 'You can not use the "stream" method if the Templating Component or the Twig Bundle are not available. Try running "composer require symfony/twig-bundle".');
    // }

    // if (null === $response) {
    // return new StreamedResponse($callback);
    // }

    // $response->setCallback($callback);

    // return $response;
    // }

    // /**
    // * Returns a NotFoundHttpException.
    // *
    // * This will result in a 404 response code. Usage example:
    // *
    // * throw $this->createNotFoundException('Page not found!');
    // */
    // protected function createNotFoundException(string $message = 'Not Found', \Exception $previous = null): NotFoundHttpException
    // {
    // return new NotFoundHttpException($message, $previous);
    // }

    // /**
    // * Returns an AccessDeniedException.
    // *
    // * This will result in a 403 response code. Usage example:
    // *
    // * throw $this->createAccessDeniedException('Unable to access this page!');
    // *
    // * @throws \LogicException If the Security component is not available
    // */
    // protected function createAccessDeniedException(string $message = 'Access Denied.', \Exception $previous = null): AccessDeniedException
    // {
    // if (! class_exists(AccessDeniedException::class)) {
    // throw new \LogicException(
    // 'You can not use the "createAccessDeniedException" method if the Security component is not available. Try running "composer require symfony/security-bundle".');
    // }

    // return new AccessDeniedException($message, $previous);
    // }

    // /**
    // * Creates and returns a Form instance from the type of the form.
    // */
    // protected function createForm(string $type, $data = null, array $options = array()): FormInterface
    // {
    // return $this->container->get('form.factory')->create($type, $data, $options);
    // }

    // /**
    // * Creates and returns a form builder instance.
    // */
    // protected function createFormBuilder($data = null, array $options = array()): FormBuilderInterface
    // {
    // return $this->container->get('form.factory')->createBuilder(FormType::class, $data, $options);
    // }

    // /**
    // * Shortcut to return the Doctrine Registry service.
    // *
    // * @throws \LogicException If DoctrineBundle is not available
    // */
    // protected function getDoctrine(): ManagerRegistry
    // {
    // if (! $this->container->has('doctrine')) {
    // throw new \LogicException(
    // 'The DoctrineBundle is not registered in your application. Try running "composer require symfony/orm-pack".');
    // }

    // return $this->container->get('doctrine');
    // }

    // /**
    // * Get a user from the Security Token Storage.
    // *
    // * @return mixed
    // *
    // * @throws \LogicException If SecurityBundle is not available
    // *
    // * @see TokenInterface::getUser()
    // */
    // protected function getUser()
    // {
    // if (! $this->container->has('security.token_storage')) {
    // throw new \LogicException(
    // 'The SecurityBundle is not registered in your application. Try running "composer require symfony/security-bundle".');
    // }

    // if (null === $token = $this->container->get('security.token_storage')->getToken()) {
    // return;
    // }

    // if (! \is_object($user = $token->getUser())) {
    // // e.g. anonymous authentication
    // return;
    // }

    // return $user;
    // }

    // /**
    // * Checks the validity of a CSRF token.
    // *
    // * @param string $id
    // * The id used when generating the token
    // * @param string|null $token
    // * The actual token sent with the request that should be validated
    // */
    // protected function isCsrfTokenValid(string $id, ?string $token): bool
    // {
    // if (! $this->container->has('security.csrf.token_manager')) {
    // throw new \LogicException(
    // 'CSRF protection is not enabled in your application. Enable it with the "csrf_protection" key in "config/packages/framework.yaml".');
    // }

    // return $this->container->get('security.csrf.token_manager')->isTokenValid(new CsrfToken($id, $token));
    // }

    // /**
    // * Dispatches a message to the bus.
    // *
    // * @param object|Envelope $message
    // * The message or the message pre-wrapped in an envelope
    // */
    // protected function dispatchMessage($message): Envelope
    // {
    // if (! $this->container->has('message_bus')) {
    // $message = class_exists(Envelope::class) ? 'You need to define the "messenger.default_bus" configuration option.' : 'Try running "composer require symfony/messenger".';
    // throw new \LogicException('The message bus is not enabled in your application. ' . $message);
    // }

    // return $this->container->get('message_bus')->dispatch($message);
    // }

    // /**
    // * Adds a Link HTTP header to the current response.
    // *
    // * @see https://tools.ietf.org/html/rfc5988
    // */
    // protected function addLink(Request $request, Link $link)
    // {
    // if (! class_exists(AddLinkHeaderListener::class)) {
    // throw new \LogicException(
    // 'You can not use the "addLink" method if the WebLink component is not available. Try running "composer require symfony/web-link".');
    // }

    // if (null === $linkProvider = $request->attributes->get('_links')) {
    // $request->attributes->set('_links', new GenericLinkProvider(array(
    // $link
    // )));

    // return;
    // }

    // $request->attributes->set('_links', $linkProvider->withLink($link));
    // }
}
