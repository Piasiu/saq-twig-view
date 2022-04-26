<?php
namespace Saq\Views;

use Saq\Interfaces\ContainerInterface;

class TwigService
{
    /**
     * @var ContainerInterface
     */
    private ContainerInterface $container;

    /**
     * @var TwigView|null
     */
    private ?TwigView $view = null;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return TwigView
     */
    public function __invoke(): TwigView
    {
        if ($this->view === null)
        {
            $this->view = new TwigView($this->container);
        }

        return $this->view;
    }
}