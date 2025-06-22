<?php

namespace WechatPayBundle\Service;

use Symfony\Bundle\FrameworkBundle\Routing\AttributeRouteControllerLoader;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Routing\RouteCollection;
use WechatPayBundle\Controller\AppController;
use WechatPayBundle\Controller\UnifiedOrderController;

class AttributeControllerLoader implements LoaderInterface
{
    public function __construct(
        private AttributeRouteControllerLoader $controllerLoader
    ) {}

    public function load(mixed $resource, ?string $type = null): RouteCollection
    {
        $collection = new RouteCollection();

        $collection->addCollection($this->controllerLoader->load(AppController::class));
        $collection->addCollection($this->controllerLoader->load(UnifiedOrderController::class));

        return $collection;
    }

    public function supports(mixed $resource, ?string $type = null): bool
    {
        return $type === 'wechat_pay_controllers';
    }

    public function getResolver(): LoaderResolverInterface
    {
        return $this->controllerLoader->getResolver();
    }

    public function setResolver(LoaderResolverInterface $resolver): void
    {
        $this->controllerLoader->setResolver($resolver);
    }

    public function autoload(): RouteCollection
    {
        return $this->load(null, 'wechat_pay_controllers');
    }
}
