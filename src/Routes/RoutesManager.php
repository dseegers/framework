<?php
namespace OffbeatWP\Routes;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RoutesManager
{
    public $actions = [];
    protected $routesCollection;
    protected $routesContext;
    protected $routeIterator = 0;

    public function __construct()
    {
        $this->routesCollection = new RouteCollection();
    }

    public function callback($checkCallback, $actionCallback, $parameters = [])
    {
        $action = [[
            'actionCallback' => $actionCallback,
            'checkCallback'  => $checkCallback,
            'parameters'     => $parameters,
        ]];

        $this->actions = array_merge($action, $this->actions);
    }

    public function get($route, $actionCallback, $parameters = [])
    {
        $this->addRoute($route, ['_callback' => $actionCallback], [], [], '', [], ['GET']);
    }

    public function post($route, $actionCallback, $parameters = [])
    {
        $this->addRoute($route, ['_callback' => $actionCallback], [], [], '', [], ['POST']);
    }

    public function put($route, $actionCallback, $parameters = [])
    {
        $this->addRoute($route, ['_callback' => $actionCallback], [], [], '', [], ['PUT']);
    }

    public function patch($route, $actionCallback, $parameters = [])
    {
        $this->addRoute($route, ['_callback' => $actionCallback], [], [], '', [], ['PATCH']);
    }

    public function delete($route, $actionCallback, $parameters = [])
    {
        $this->addRoute($route, ['_callback' => $actionCallback], [], [], '', [], ['DELETE']);
    }

    public function addRoute(string $path, array $defaults = [], array $requirements = [], array $options = [],  ? string $host = '', $schemes = [], $methods = [],  ? string $condition = '')
    {
        $route = new Route(
            $path, // path
            $defaults, // default values
            $requirements, // requirements
            $options, // options
            $host, // host
            $schemes, // schemes
            $methods, // methods
            $condition // condition
        );

        $this->routesCollection->add($this->getNextRouteName(), $route);
    }

    public function findUrlMatch()
    {
        // $request = Request::createFromGlobals(); // Disabled, gave issues with uploads
        $request = Request::create($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD'], $_REQUEST, $_COOKIE, [], $_SERVER);

        $context = new RequestContext();
        $context->fromRequest($request);
        $matcher = new UrlMatcher($this->routesCollection, $context);

        try {
            $parameters = $matcher->match($request->getPathInfo());

            if (apply_filters('offbeatwp/route/match/url', true, $matcher)) {
                throw new Exception('Route not match (override)');
            }

            return [
                'actionCallback' => $parameters['_callback'],
                'parameters'     => $parameters,
            ];
        } catch (\Exception $e) {
            return false;
        }
    }

    public function findMatch()
    {
        foreach ($this->actions as $action) {
            if (
                apply_filters('offbeatwp/route/match/wp', true, $action) && 
                $action['checkCallback']()
            ) {
                return $action;
            }

        }

        return false;
    }

    public function getNextRouteName()
    {
        $routeName = 'route' . $this->routeIterator;
        $this->routeIterator++;

        return $routeName;
    }
}
