<?php
namespace Saq\Views;

use JetBrains\PhpStorm\Pure;
use Saq\Exceptions\Container\ContainerException;
use Saq\Exceptions\Container\ServiceNotFoundException;
use Saq\Interfaces\ContainerInterface;
use Saq\Routing\Route;
use Saq\Trans\Translator;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
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
    public function getFilters(): array
    {
        return [
            new TwigFilter('lpad', [$this, 'leftPad']),
            new TwigFilter('rpad', [$this, 'rightPad']),
            new TwigFilter('price', [$this, 'formatPrice']),
            new TwigFilter('trans', [$this, 'translate']),
            new TwigFilter('trans_e', [$this, 'translateFromErrors']),
            new TwigFilter('trans_g', [$this, 'translateFromGeneral']),
            new TwigFilter('trans_c', [$this, 'translateConstant']),
            new TwigFilter('trans_p', [$this, 'translatePlural']),
            new TwigFilter('trans_pg', [$this, 'translatePluralFromGeneral'])
        ];
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
            new TwigFunction('mail_for', [$this, 'getMailTo'])
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
        return "mailto:$email";
    }

    /**
     * @param string $text
     * @param string $pad
     * @param int $length
     * @return string
     */
    #[Pure]
    public function leftPad(string $text, string $pad, int $length): string
    {
        return str_pad($text, $length, $pad, STR_PAD_LEFT);
    }

    /**
     * @param string $text
     * @param string $pad
     * @param int $length
     * @return string
     */
    #[Pure]
    public function rightPad(string $text, string $pad, int $length): string
    {
        return str_pad($text, $length, $pad);
    }

    /**
     * @param string $decimal
     * @param int $numberOfDigitsAfterDot
     * @param string $dot
     * @return string
     */
    public function formatPrice(string $decimal, int $numberOfDigitsAfterDot, string $dot = '.'): string
    {
        $parts = explode('.', $decimal, 2);

        if (strlen($parts[0]) === 0)
        {
            $parts[0] = '0';
        }

        if (count($parts) === 1)
        {
            $parts[] = '';
        }

        return $parts[0].$dot.str_pad($parts[1], $numberOfDigitsAfterDot, '0');
    }

    /**
     * @param string $text
     * @param array $parameters
     * @param string|null $fileSubPath
     * @return string
     */
    public function translate(string $text, array $parameters = [], ?string $fileSubPath = null): string
    {
        $fileSubPath = $fileSubPath ?? $this->getSubPathFromRoute();
        $languageCode = $this->container->getRequest()->getAttribute('language');
        return $this->translator->translate($fileSubPath, $text, $parameters, $languageCode);
    }

    /**
     * @param string $text
     * @param array $parameters
     * @return string
     */
    public function translateFromErrors(string $text, array $parameters = []): string
    {
        return $this->translate($text, $parameters, 'errors');
    }

    /**
     * @param string $text
     * @param array $parameters
     * @return string
     */
    public function translateFromGeneral(string $text, array $parameters = []): string
    {
        return $this->translate($text, $parameters, 'general');
    }

    /**
     * @param string $text
     * @param int $value
     * @param array $parameters
     * @return string
     */
    public function translateConstant(string $text, int $value, array $parameters = []): string
    {
        return $this->translateFromGeneral($text.$value, $parameters);
    }

    /**
     * @param string $text
     * @param int $value
     * @param array $parameters
     * @param string|null $fileSubPath
     * @return string
     */
    public function translatePlural(string $text, int $value, array $parameters = [], ?string $fileSubPath = null): string
    {
        if ($this->translator === null)
        {
            return throw new ServiceNotFoundException(Translator::class);
        }

        $fileSubPath = $fileSubPath ?? $this->getSubPathFromRoute();
        $languageCode = $this->container->getRequest()->getAttribute('language');
        return $this->translator->pluralTranslate($fileSubPath, $text, $value, $parameters, $languageCode);
    }

    /**
     * @param string $text
     * @param int $value
     * @param array $parameters
     * @return string
     */
    public function translatePluralFromGeneral(string $text, int $value, array $parameters = []): string
    {
        return $this->translatePlural($text, $value, $parameters, 'general');
    }

    private function getSubPathFromRoute(): string
    {
        if ($this->translator === null)
        {
            return throw new ServiceNotFoundException(Translator::class);
        }

        /** @var Route $route */
        $route = $this->container->getRequest()->getAttribute('route');
        $subPath = str_replace('-', DIRECTORY_SEPARATOR, $route->getName());
        return str_replace('_', '-', $subPath);
    }
}