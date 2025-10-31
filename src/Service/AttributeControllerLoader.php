<?php

namespace WechatPayBundle\Service;

use Symfony\Bundle\FrameworkBundle\Routing\AttributeRouteControllerLoader;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Routing\RouteCollection;
use Tourze\RoutingAutoLoaderBundle\Service\RoutingAutoLoaderInterface;
use WechatPayBundle\Controller\AppController;
use WechatPayBundle\Controller\UnifiedOrderController;

#[AutoconfigureTag(name: 'routing.loader')]
#[AutoconfigureTag(name: 'routing.auto.loader')]
class AttributeControllerLoader extends Loader implements RoutingAutoLoaderInterface
{
    private AttributeRouteControllerLoader $controllerLoader;

    public function __construct()
    {
        parent::__construct();
        $this->controllerLoader = new AttributeRouteControllerLoader();
    }

    public function load(mixed $resource, ?string $type = null): RouteCollection
    {
        return $this->autoload();
    }

    public function supports(mixed $resource, ?string $type = null): bool
    {
        return false;
    }

    public function autoload(): RouteCollection
    {
        $collection = new RouteCollection();

        $collection->addCollection($this->controllerLoader->load(AppController::class));
        $collection->addCollection($this->controllerLoader->load(UnifiedOrderController::class));

        return $collection;
    }
}
