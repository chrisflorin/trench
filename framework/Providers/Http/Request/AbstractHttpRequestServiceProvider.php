<?php

namespace Trench\Providers\Http\Request;

use Trench\Http\Requests\AbstractRequest;
use Trench\Providers\AbstractServiceProvider;

class AbstractHttpRequestServiceProvider extends AbstractServiceProvider
{
    protected $formRequestVerbs = [
        'store' => 'Create',
        'update' => 'Update'
    ];

    protected $requestNamespace = 'Trench\Http\Requests\\';

    public function register()
    {
        $this->app->bindShared(AbstractRequest::class, function ($app) {
            $routeActionName = Route::getCurrentroute()->getActionName();
            $routeParts = explode('@', $routeActionName);

            $routeAction = $routeParts[1];

            $controllerClassParts = explode('\\', $routeParts[0]);
            $className = end($controllerClassParts);
            $modelName = str_replace('ApiController', '', $className);

            $formRequestVerb = $this->formRequestVerbs[$routeAction];
            $formRequestName = "{$formRequestVerb}{$modelName}Request";

            return $app->make("{$this->requestNamespace}$modelName\\{$formRequestName}");
        });
    }
}