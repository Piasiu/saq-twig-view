<?php
namespace Saq\Views;

use Saq\Exceptions\Container\ContainerException;
use Saq\Exceptions\Container\ServiceNotFoundException;
use Saq\Interfaces\ContainerInterface;
use Saq\Interfaces\Http\RequestInterface;
use Saq\Routing\Route;
use Saq\Trans\Translator;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TwigExtension extends AbstractExtension
{
    /**
     * @var ContainerInterface
     */
    private ContainerInterface $container;

    /**
     * @var Translator|null
     */
    private ?Translator $translator = null;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        if ($container->has('translator'))
        {
            if (!($container['translator'] instanceof Translator))
            {
                throw new ContainerException(sprintf('Container "translator" service must be of type %s.', Translator::class));
            }

            $this->translator = $container['translator'];
        }
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
            new TwigFunction('trans', [$this, 'translate']),
            new TwigFunction('ptrans', [$this, 'pluralTranslate'])
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

    /**
     * @param string $text
     * @param array $parameters
     * @return string
     */
    public function translate(string $text, array $parameters = []): string
    {
        $languageCode = $this->container->getRequest()->getAttribute('language');
        return $this->translator->translate($this->getSubPathFromRoute(), $text, $parameters, $languageCode);
    }

    /**
     * @param string $text
     * @param int $value
     * @param array $parameters
     * @return string
     */
    public function pluralTranslate(string $text, int $value, array $parameters = []): string
    {
        if ($this->translator === null)
        {
            return throw new ServiceNotFoundException(Translator::class);
        }

        $languageCode = $this->container->getRequest()->getAttribute('language');
        return $this->translator->pluralTranslate($this->getSubPathFromRoute(), $text, $value, $parameters, $languageCode);
    }

    private function getSubPathFromRoute(RequestInterface $request): string
    {
        if ($this->translator === null)
        {
            return throw new ServiceNotFoundException(Translator::class);
        }

        /** @var Route $route */
        $route = $this->container->getRequest()->getAttribute('route');
        return str_replace('-', DIRECTORY_SEPARATOR, $route->getName());
    }
}