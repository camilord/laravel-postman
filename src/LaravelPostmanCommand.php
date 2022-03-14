<?php

namespace Phpsa\LaravelPostman;

use ReflectionClass;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Routing\Route as RoutingRoute;
use phpDocumentor\Reflection\DocBlockFactory;

class LaravelPostmanCommand extends Command
{
    protected $helper;
    protected $factory;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'postman:export {--C|controllers=*}';
    protected $name = 'postman:export';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exports Laravel API routes to a JSON file usign Postman import format';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Helper $helper)
    {
        $this->helper = $helper;
        $customTags = ['route-name' => RouteNameTag::class];

        $this->factory  = DocBlockFactory::createInstance($customTags);
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $collectionName = $this->getCollectionName();
        $collectionDescription = $this->getCollectionDescription();
        $collection = $this->helper->getCollectionStructure(
            $collectionName,
            $collectionDescription
        );


        foreach ($this->getRoutes() as $folderName => $folderRoutes) {
            $items = [];
            foreach ($folderRoutes as $route) {
                $items = array_merge($this->getRouteItems($route), $items);
            }

            $collection['item'][] = [
                'name'        => $folderName,
                'description' => '',
                'item'        => $items,
            ];
        }

        file_put_contents(
            $this->helper->getExportDirectory() . 'postman.json',
            json_encode($collection)
        );
    }

    /**
     * Returns an array of route items (route + method) for the given route
     *
     * @param  \Illuminate\Routing\Route $route
     * @return array
     */
    protected function getRouteItems(RoutingRoute $route)
    {
        $baseURL = '{{api_url}}';

        $path = $this->helper->replaceGetParameters($route->uri());
        $routeName = $route->getName();
        $routeNameFinal = ! empty($routeName) ? $routeName : $path;
        $methods = $route->methods();
        $items = [];

        foreach ($methods as $method) {
            if ($method === 'HEAD' && config('postman.skipHEAD', true)) {
                continue;
            }
            $body = $this->getBody($route, $method);
            $docs = $this->getDocs($route);
            $items[] = $this->getItemStructure(
                $routeNameFinal,
                $baseURL,
                $path,
                $method,
                $body,
                $docs
            );
        }

        return $items;
    }

    /**
     * Returns an array with postman item format
     *
     * @param  string $routeName
     * @param  string $baseURL
     * @param  string $path
     * @param  string $method
     * @param  array<string,mixed> $body
     * @return array<string,mixed>
     */
    protected function getItemStructure(
        $routeName,
        $baseURL,
        $path,
        $method,
        $body,
        $docs
    ): array {

        $methodComment = '';
        if ($docs) {
            $docblock = $this->factory->create($docs);
            $methodComment = $docblock->getSummary() . "\n\n" . $docblock->getDescription();
            $routeName = ! empty($docblock->getTagsByName('route-name'))
            ? (string) $docblock->getTagsByName('route-name')[0]
            : $routeName;
        }

        return [
            'name'     => $routeName,
            'request'  => [
                'description' => $methodComment,
                'url'         => $baseURL . $path,
                'method'      => $method,
                'body'        => $body,
                "header"      => [
                    [
                        "key"   => "Accept",
                        "value" => "application/json",
                        "type"  => "text"
                    ],
                    [
                        "key"   => "Content-Type",
                        "value" => "application/json",
                        "type"  => "text"
                    ]
                ],
            ],
            'response' => [],
        ];
    }

    /**
     * Returns the user's collection name
     *
     * @return string
     */
    protected function getCollectionName()
    {
        $configCollectionName = config('postman.collectionName');

        if (! empty($configCollectionName)) {
            return $configCollectionName;
        }

        return $this->ask('Enter collection name', 'LaravelPostman Collection');
    }

    /**
     * Returns the user's collection description
     *
     * @return string
     */
    protected function getCollectionDescription()
    {
        $configCollectionDescription = config('postman.collectionDescription');

        if (! empty($configCollectionDescription)) {
            return $configCollectionDescription;
        }

        return $this->ask(
            'Enter collection description',
            'Postman collection generated by LaravelPostman'
        );
    }

    /**
     * Returns an array of API routes organized by folders
     *
     * @return array
     */
    protected function getRoutes()
    {
        $resultRoutes = [];

        $apiPrefix = explode(",", $this->helper->getApiPrefix());

        $filtered = $this->getFilteredControllers();

        foreach ($apiPrefix as $prefix) {
            foreach (Route::getRoutes() as $route) {
                $path = $route->uri();
                $apiPrefixLength = strlen($prefix);

                if (substr($path, 0, $apiPrefixLength) !== $prefix) {
                    continue;
                }

                if ($filtered->isNotEmpty() && $filtered->search(class_basename($route->getController())) === false) {
                    continue;
                }

                $routeFolder = $this->helper->getRouteFolder($route);

                if (! isset($resultRoutes[$routeFolder])) {
                    $resultRoutes[$routeFolder] = [];
                }

                $resultRoutes[$routeFolder][] = $route;
            }
        }

        return $resultRoutes;
    }

    protected function getFilteredControllers(): Collection
    {
        $controllers = collect($this->option('controllers'));
        $only = collect([]);
        if ($controllers->isNotEmpty()) {
            foreach ($controllers as $controller) {
                $names = explode(",", $controller);
                $only = $only->merge($names);
            }
        }

        return $only;
    }

    /**
     * Returns an postman body array for the given route
     *
     * @param  \Illuminate\Routing\Route $route
     * @param  string                   $method
     * @return array
     */
    protected function getBody(RoutingRoute $route, $method)
    {

        $postmanParams = $this->getRouteParams($route, $method);


        if (empty($postmanParams)) {
            return [];
        }


        $body['mode'] = 'raw';
        $body['options'] = [
            "raw" => [
                "language" => "json"
            ]
        ];

        $body['raw'] = json_encode($postmanParams, JSON_PRETTY_PRINT);


        return $body;
    }

    /**
     * Returns an array of the given route parameters
     *
     * @param  \Illuminate\Routing\Route $route
     * @param  string                   $method
     * @return array
     */
    protected function getRouteParams(RoutingRoute $route, $method)
    {
        if ($method === 'GET' || $method === 'DELETE') {
            return [];
        }

        if (! $this->helper->canGetPostmanModel($route)) {
            return [];
        }

        $postmanModel = $this->helper->getPostmanModel($route);

        if (! is_object($postmanModel)) {
            return [];
        }


        if (method_exists($postmanModel, 'getPostmanParams')) {
            return $postmanModel->getPostmanParams($method, $route->getName());
        }

        return array_fill_keys($postmanModel->getFillable(), "");
    }

    protected function getDocs(RoutingRoute $route)
    {
        try {
            $class = new ReflectionClass($route->getController());
            $classMethod = $class->getMethod($route->getActionMethod());
            return $classMethod->getDocComment();
        } catch (\Throwable $error) {
            return false;
        }
    }
}
