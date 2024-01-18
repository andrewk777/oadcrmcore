<?php

namespace Oadsoft\Crmcore\Helpers;

use Illuminate\Support\Facades\Route;

class RouteHelper {

    protected $routes = [];

    public function addSingleRoute(string $slug, string $controller) 
    {
        return $this->addRoute($slug, $controller,'single');
    }

    public function addResourceRoute(string $slug, string $controller) 
    {
        return $this->addRoute($slug, $controller,'resource');
    }

    public function addFormRoute(string $slug, string $controller) 
    {
        return $this->addRoute($slug, $controller,'form');
    }

    public function addRoute(string $slug, string $controller, $type = 'single') {
        $this->routes[$slug] = [
            'slug'           => $slug,
            'type'          => $type,
            'controller'    => $controller
        ];
        return $this;
    }

    public function getResourceRoutes() 
    {
        return $this->getRoutes('resource');
    }

    public function getFormRoutes() 
    {
        return $this->getRoutes('form');
    }

    public function getSignleRoutes()
    {
        return $this->getRoutes('single');
    }

    public function getRoutes($type = null) 
    {
        if ($type) 
        {
            return collect($this->routes)->filter(function($item) use ($type) {
                return $item['type'] == $type;
            });
        }
        return $this->routes;
    }

    public function genResourceRoutes() 
    {
        $this->getResourceRoutes()->each(function($route) 
        {
            Route::post($route['slug'], $route['controller'] . '@index');
            Route::get($route['slug'], $route['controller'] . '@show');
            Route::get($route['slug'] . '/list', $route['controller'] . '@list');
            Route::post($route['slug'] . '/save', $route['controller'] . '@store');
            Route::delete($route['slug'] . '/{id}', $route['controller'] . '@destroy')->where('id', '([a-zA-Z0-9\-]+)');
            Route::post($route['slug'] . '/export', $route['controller'] . '@export');
        });
        return $this;
    }

    public function genSingleRoutes()
    {
        $this->getSignleRoutes()->each(function($route)
        {
            Route::get($route['slug'], $route['controller']);
        });
    }
    
    public function genFormRoutes() 
    {
        $this->getFormRoutes()->each(function($route) 
        {
            Route::get($route['slug'], $route['controller'] . '@show');
            Route::post($route['slug'] . '/save', $route['controller'] . '@store');
    
        });
        return $this;
    }

    public function getRouteController(string $slug) {
        return $this->routes[$slug]['controller'];
    }

    
}