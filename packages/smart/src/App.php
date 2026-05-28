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
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App as Slim;
use Slim\Factory\AppFactory;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\ResponseEmitter;
use Slim\Routing\RouteCollector;

final readonly class App
{
    private Slim $slim;

    private Container $container;

    /**
     * @param list<Context> $contexts
     */
    public function __construct(AppEnv $appEnv, array $contexts = [])
    {
        $builder = new ContainerBuilder();
        $builder->addDefinitions($this->appDependencies($appEnv));
        foreach ($contexts as $context) {
            $builder->addDefinitions($context->dependencies());
        }

        if (!$appEnv->debug) {
            $builder->enableDefinitionCache();
            try {
                $builder->enableCompilation($appEnv->cacheDirFromProjectRoot());
            } catch (\Throwable) {
                // @mago-expect no-empty-catch-clause
            }
        }

        $this->container = $builder->build();

        $this->slim = AppFactory::createFromContainer($this->container);

        foreach ($contexts as $context) {
            $context->routes($this->slim);
        }
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
            ServerRequestCreator::class => static fn(Psr17Factory $psr17Factory) => new ServerRequestCreator(
                $psr17Factory,
                $psr17Factory,
                $psr17Factory,
                $psr17Factory,
            ),
        ];
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->slim->handle($request);
    }

    /**
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \InvalidArgumentException
     */
    public function run(): void
    {
        $response = $this->handle($this->container->get(ServerRequestCreator::class)->fromGlobals());

        $emitter = new ResponseEmitter();
        $emitter->emit($response);
    }
}
