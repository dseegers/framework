<?php
namespace OffbeatWP\Foundation;

use Closure;
use DI\Container;
use DI\ContainerBuilder;
use Exception;
use OffbeatWP\Assets\AssetsManager;
use OffbeatWP\Assets\ServiceEnqueueScripts;
use OffbeatWP\Components\ComponentsService;
use OffbeatWP\Config\Config;
use OffbeatWP\Content\Post\Relations\Service;
use OffbeatWP\Http\Http;
use OffbeatWP\Routes\RoutesService;
use OffbeatWP\Wordpress\WordpressService;
use WP_Error;
use function DI\create;

class App
{
    private static $instance;
    public $container;
    private $services = [];
    protected $config = null;
    protected $route;

    public static function singleton()
    {
        if (!isset(static::$instance)) {
            static::$instance = new static;
        }
        return static::$instance;
    }

    public function bootstrap()
    {
        // add_filter('deprecated_file_trigger_error', '__return_false');

        $containerBuilder = new ContainerBuilder();

        $containerBuilder->addDefinitions($this->baseBindings());
        $this->initiateBaseServices($containerBuilder);
        $this->initiateServices($containerBuilder);

        $this->container = $containerBuilder->build();

        $this->registerServices();

        offbeat('hooks')->doAction('offbeat.ready');

        add_filter('wp', [$this, 'findRoute'], 0);
    }

    private function baseBindings()
    {
        return [
            'assets' => create(AssetsManager::class),
            'http' => create(Http::class),
        ];
    }

    private function initiateBaseServices($containerBuilder)
    {
        foreach ([
            WordpressService::class,
            RoutesService::class,
            ComponentsService::class,
            ServiceEnqueueScripts::class,
            Service::class,
        ] as $service) {
            $this->initiateService($service, $containerBuilder);
        }
    }

    private function initiateServices($containerBuilder)
    {
        $services = config('services');

        if (is_object($services) && $services->isNotEmpty()) {
            $services->each(function ($service) use ($containerBuilder) {
                $this->initiateService($service, $containerBuilder);
            });
        }
    }

    private function initiateService($serviceClass, $containerBuilder)
    {
        if ($this->isServiceInitiated($serviceClass)) {
            return $this->getService($serviceClass);
        }

        if (class_exists($serviceClass)) {
            $service = new $serviceClass($this);

            if (property_exists($service, 'bindings') && !empty($service->bindings)) {
                foreach ($service->bindings as &$binding) {
                    if (is_string($binding)) {
                        $binding = \DI\autowire($binding);
                    }
                }
                $containerBuilder->addDefinitions($service->bindings);
            }

            $this->markServiceAsInitiated($service);
        }

    }

    private function registerServices()
    {
        if (!empty($this->services)) {
            foreach ($this->services as $service) {
                if (is_callable([$service, 'register'])) {
                    $this->container->call([$service, 'register']);
                }
            }
        }

    }

    public function getService($serviceClass)
    {
        if ($this->isServiceInitiated($serviceClass)) {
            return $this->services[$serviceClass];
        }

        return false;
    }

    public function isServiceInitiated($serviceClass)
    {
        if (isset($this->services[$serviceClass])) {
            return true;
        }

        return false;
    }

    public function markServiceAsInitiated($service)
    {
        $this->services[get_class($service)] = $service;
    }

    public function bind($abstract, $concrete)
    {
        $this->container->set($abstract, $concrete);
    }

    public function configPath()
    {
        return get_template_directory() . '/config';
    }

    public function routesPath()
    {
        return get_template_directory() . '/routes';
    }

    public function componentsPath()
    {
        return get_template_directory() . '/components';
    }

    public function config($config, $default)
    {
        if (is_null($this->config)) {
            $this->config = new Config($this);
        }

        if (!is_null($config)) {
            return $this->config->get($config, $default);
        }
        return $this->config;
    }

    public function findRoute() {
        if (is_admin()) return;

        do_action('offbeatwp/route/match/before');

        $route = offbeat('routes')->findRoute();

        do_action('offbeatwp/route/match/after', $route);

        $this->route = $route;
    }

    public function run($config = [])
    {
        $route = $this->route;

        try {
            $output = $this->runRoute($route);

            // Remove route from collection so if there is a second run it skips this route
            offbeat('routes')->removeRoute($route);

            if ($output === false) {
                throw new Exception('Route return false, try to find next match
                ');
            }

            $output = apply_filters('route_render_output', $output); //Legacy
            $output = apply_filters('offbeatwp/route/output', $output);

            echo $output;
        } catch (Exception $e) {
            // Find new route
            $this->findRoute();
            $this->run($config);
        }
    }

    public function runRoute($route)
    {
        $route = apply_filters('offbeatwp/route/run/init', $route);

        if ($route !== false && $route->hasValidActionCallback()) {
            $actionReturn = apply_filters('offbeatwp/route/run/pre', false, $route);

            if (!$actionReturn) {
                $actionReturn = $route->doActionCallback(); 
            }

            $actionReturn = apply_filters('offbeatwp/route/run/post', $actionReturn, $route);

            if (is_array($actionReturn)) {
                header('Content-type: application/json');
                return json_encode($actionReturn);
            } else {
                return $actionReturn;
            }
        }

        return new WP_Error('broke', __("No route matched", 'raow'));
    }

}
