<?php
namespace Saq\Views;

use RuntimeException;
use Saq\Interfaces\ContainerInterface;
use Saq\Routing\Route;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\ExtensionInterface;
use Twig\Loader\FilesystemLoader;

class TwigView
{
    /**
     * @var ContainerInterface
     */
    private ContainerInterface $container;

    /**
     * @var Environment
     */
    private Environment $environment;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $settings = $container->getSettings()->get('view', []);

        if (!isset($settings['paths']))
        {
            throw new RuntimeException('View settings must contain the "paths" option.');
        }

        $loader = new FilesystemLoader($settings['paths']);

        $options = isset($settings['options']) ? $settings['options'] : [];
        $this->environment = new Environment($loader, $options);

        $this->environment->addExtension(new TwigExtension($container));
        $extensions = isset($settings['extensions']) ? $settings['extensions'] : [];

        foreach ($extensions as $extension)
        {
            if (!($extension instanceof ExtensionInterface))
            {
                $message = sprintf('The view settings option "extensions" must contain objects that implement the %s interface.', ExtensionInterface::class);
                throw new RuntimeException($message);
            }

            $this->environment->addExtension($extension);
        }
    }

    /**
     * @param string $templateName
     * @param array $context
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function render(string $templateName, array $context = []): string
    {
        $template = $this->environment->load($templateName);
        return $template->render($context);
    }

    /**
     * @return Environment
     */
    public function getEnvironment(): Environment
    {
        return $this->environment;
    }

    private function addGlobals(): void
    {
        $request = $this->container->getRequest();
        /** @var Route $route */
        $route = $request->getAttribute('route');
        $data = [
            'settings' => $this->container->getSettings(),
            'request' => $request,
            'route' => $route
        ];

        $this->environment->addGlobal('app', $data);
    }
}