# laravel-postman

This package allows you to export your API routes to a postman import json file, original work by https://github.com/sojeda/laravel-postman

## Installation

Install the package via composer

`composer require --dev phpsa/laravel-postman`

Then add the service provider in config/app.php:
]

## Configuration

Optionally, publish the package configuration file:

`php artisan vendor:publish --provider="Phpsa\LaravelPostman\ServiceProvider" --tag="config"`

Note: publishing the configuration file is optional, you can use de default package options.

### Options

- LARAVEL_API_URL => Url for the api to use in its variables
- LARAVEL_POSTMAN_COLLECTION_NAME => your collection name
- LARAVEL_POSTMAN_COLLECTION_DESCRIPTION => description of collection
- LARAVEL_POSTMAN_API_PREFIX => comma list of routes to include (default api,oauth)
- LARAVEL_POSTMAN_SKIP_HEAD => skip head routes - default true
- LARAVEL_POSTMAN_EXPORT_DIRECTORY => where to store the file, default storage folder
  ];

#### apiURL

This is the base URL for your postman routes

default value: config('app.url')

#### collectionName

This is the postman collection name

default value: the command will ask for it

#### collectionDescription

This is the postman collection description

default value: the command will ask for it

#### apiPrefix

This is the prefix by which we identify the routes to export

default value: 'api'

#### skipHEAD

This avoids creating routes for HEAD method

default value: true

#### exportDirectory

The directory to which the postman.json file will be exported

## Usage

### Configuring controllers

Add a property to your entity controller like this:

`public $postmanModel = 'App\MyEntityModel';`

### Add a public method to your model class like this: (optional)

```php
public function getPostmanParams($method) { return [
    'key1': 'sampleValue',
    'key2' : ''
];
}
```

This array of params will be used to fill POST and PUT urlencoded form data section in
postman. The previous method is just an example, you should return the array of
params that you want to see in postman.

if the above method not supplied, will use the model fillable to fill it.

### Documenting endpoints

Laravel-Postman uses the docblocks above your methods to generate the documentation
eg:

```php
/**
  * this is the summary - which headlines the description
  *
  * this is the description - which goes on a new line under the summary (optional)
  *
  * @route-name My Custom Route Name (defaults to folder.model.method if not set)
  *
  * @param ...
  ...
  */
```

### Export

`php artisan laravelPostman:export`
