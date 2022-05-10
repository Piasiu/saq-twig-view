<?php
namespace Saq\Views;

use Saq\Interfaces\ContainerInterface;
use Saq\Routing\Route;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TwigExtension extends AbstractExtension
{
    /**
     * @var ContainerInterface
     */
    private ContainerInterface $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('base_url', [$this, 'getBaseUrl']),
            new TwigFunction('url_for', [$this, 'getUrlFor']),
            new TwigFunction('current_url', [$this, 'getCurrentUrl']),
            new TwigFunction('uri_for', [$this, 'getUriFor']),
            new TwigFunction('current_uri', [$this, 'getCurrentUri']),
            new TwigFunction('mail_to', [$this, 'getMailTo'])
        ];
    }

    /**
     * @param string $path
     * @return string
     */
    public function getBaseUrl(string $path = '/'): string
    {
        $path = '/'.ltrim(trim($path), '/');
        return $this->container->getRouter()->getBasePath().$path;
    }

    /**
     * @param string $routeName
     * @param array $arguments
     * @return string
     */
    public function getUrlFor(string $routeName, array $arguments = []): string
    {
        return $this->getUriFor($routeName, $arguments);
    }

    /**
     * @return string
     */
    public function getCurrentUrl(): string
    {
        /** @var Route $route */
        $route = $this->container->getRequest()->getAttribute('route');
        $router = $this->container->getRouter();
        return $router->urlFor($route->getName(), $route->getArguments());
    }

    /**
     * @param string $routeName
     * @param array $arguments
     * @param array $queryParams
     * @return string
     */
    public function getUriFor(string $routeName, array $arguments = [], array $queryParams = []): string
    {
        return $this->container->getRouter()->urlFor($routeName, $arguments, $queryParams);
    }

    /**
     * @return string
     */
    public function getCurrentUri(): string
    {
        return $this->container->getRequest()->getUri();
    }

    /**
     * @param string $email
     * @return string
     */
    public function getMailTo(string $email): string
    {
        return "mailto:{$email}";
    }
}