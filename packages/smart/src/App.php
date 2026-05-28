<?php

declare(strict_types=1);

namespace Brammm\Smart;

use Closure;
use DI\Bridge\Slim\CallableResolver;
use DI\Bridge\Slim\ControllerInvoker;
use DI\Container;
use DI\ContainerBuilder;
use DI\DependencyException;
use DI\NotFoundException;
use InvalidArgumentException;
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
use RuntimeException;
use Slim\App as Slim;
use Slim\Factory\AppFactory;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\ResponseEmitter;
use Slim\Routing\RouteCollector;

use function assert;

final class App
{
    /** @var list<Context> */
    private array $contexts;

    private static ?Slim $slim = null;

    public function __construct(
        private AppEnv $appEnv,
    ) {}

    public function addContext(Context $context): void
    {
        $this->contexts[] = $context;
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

    /**
     * @throws RuntimeException
     */
    private function getSlim(): Slim
    {
        if (self::$slim === null) {
            $builder = new ContainerBuilder();
            $builder->addDefinitions($this->appDependencies($this->appEnv));
            foreach ($this->contexts as $context) {
                $builder->addDefinitions($context->dependencies());
            }

            if (!$this->appEnv->debug) {
                $builder->enableDefinitionCache();
                $builder->enableCompilation($this->appEnv->cacheDirFromProjectRoot());
            }

            $container = $builder->build();

            $app = AppFactory::createFromContainer($container);

            foreach ($this->contexts as $context) {
                $context->routes($app);
            }
            
            $app->addBodyParsingMiddleware();

            self::$slim = $app;
        }

        return self::$slim;
    }

    /**
     * @throws RuntimeException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->getSlim()->handle($request);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function run(): void
    {
        $container = $this->getSlim()->getContainer();
        assert($container instanceof Container, description: 'Container is not an instance of ' . Container::class);

        $response = $this->handle($container->get(ServerRequestCreator::class)->fromGlobals());

        $emitter = new ResponseEmitter();
        $emitter->emit($response);
    }
}
