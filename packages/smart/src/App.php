<?php

declare(strict_types=1);

namespace Brammm\Smart;

use Closure;
use DI\Bridge\Slim\CallableResolver;
use DI\Bridge\Slim\ControllerInvoker;
use DI\Container;
use DI\ContainerBuilder;
use Invoker\CallableResolver as InvokerResolver;
use Invoker\Invoker;
use Invoker\ParameterResolver\AssociativeArrayResolver;
use Invoker\ParameterResolver\Container\TypeHintContainerResolver;
use Invoker\ParameterResolver\DefaultValueResolver;
use Invoker\ParameterResolver\ResolverChain;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\App as Slim;
use Slim\Factory\AppFactory;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Routing\RouteCollector;

abstract class App
{
    public readonly Slim $slim;

    public function __construct(AppEnv $appEnv)
    {
        $builder = new ContainerBuilder();
        $builder->addDefinitions($this->appDependencies($appEnv));
        if (!$appEnv->debug) {
            $builder->enableDefinitionCache();
            $builder->enableCompilation($appEnv->cacheDir);
        }

        $container = $builder->build();

        $this->slim = AppFactory::createFromContainer($container);
    }

    /**
     * @return array<string, Closure>
     */
    private function appDependencies(AppEnv $appEnv): array
    {
        return [
            AppEnv::class => static fn() => $appEnv,
            Psr17Factory::class => static fn() => new Psr17Factory(),
            ResponseFactoryInterface::class => static fn(Psr17Factory $psr17Factory) => $psr17Factory,
            CallableResolverInterface::class => static fn(Container $container) => new CallableResolver(
                new InvokerResolver($container),
            ),
            RouteCollectorInterface::class => static function (Container $container, Psr17Factory $psr17Factory) {
                $resolvers = [
                    new AssociativeArrayResolver(),
                    new TypeHintContainerResolver($container),
                    new DefaultValueResolver(),
                ];

                $invoker = new Invoker(new ResolverChain($resolvers), $container);
                $controllerInvoker = new ControllerInvoker($invoker);
                $callableResolver = new CallableResolver(new InvokerResolver($container));

                return new RouteCollector($psr17Factory, $callableResolver, $container, $controllerInvoker);
            },
        ];
    }
}
