[![GitHub release](https://img.shields.io/github/release/khalyomede/laravel-api-generator.svg)]()
![PHP minimum version](https://img.shields.io/badge/php-%3E%3D5.3.0-blue.svg)
![Laravel minimum version](https://img.shields.io/badge/laravel-%3E%3D5.3.0-orange.svg)

# laravel-api-generator
Generates models, controllers and routes API from a database

From

```bash
php artisan api:generate
```

To

```
http://localhost:8000/api/user
```
```
[
  {
    "firstName": "John",
    "lastName": "Doe",
    "age": 39
  },
  {
    "firstName": "Elizabeth",
    "lastName": "Jones",
    "age": 34
  }
]
```

## Features
- Scans your database and creates the models, controllers, and routes according to your tables properties

## Next features
- Can scope the tables you need
- Can blacklist tables you dont like
- Can remove a prefix to each of your tables
- Provides a neat JSON response for your API
- Always returns a JSON response, no matter it is a success or a database outage, ...
- Log everything, including fatal errors and exceptions
- Creates your relationships according to your tables foreign keys
- Can fill your tables if you need some fake data
- Can blacklist columns of tables to improve security

## Installation
Open a prompt command in your project folder root and run :
```bash
composer require khalyomede/laravel-api-generator
```
Next, go to `/config/app.php` and scroll down until reaching the `'providers' => [` array. Add the following line after the last provider :
```php
'provider' => [
  // Previous Services Providers ...  
  /**
   * Api Generator Command Line
   * 
   * Used on the console to create routes API from your database
   *
   * @example php artisan api:generate
   */
  Khalyomede\ApiGenerator\ApiGeneratorServiceProvider::class,
],
```
Now make sure the command line is available. Still on the prompt command, run :
```bash
php artisan list
```
You should see on the first commands this line :
```bash
Available commands:
  clear-compiled       Remove the compiled class file
  down                 Put the application into maintenance mode
  env                  Display the current framework environment
  help                 Displays help for a command
  inspire              Display an inspiring quote
  list                 Lists commands
  migrate              Run the database migrations
  optimize             Optimize the framework for better performance
  serve                Serve the application on the PHP development server
  tinker               Interact with your application
  up                   Bring the application out of maintenance mode
 api
  api:generate         Generate models, controllers and API routes from a database.
```

## Example of usage 1 : basic usage
```bash
php artisan api:generate
```
*Result :* this will create, for each tables in your database, a corresponding model, controller, and routes.

## How it works
Each time your run this command, at least 3 files are created :
- a model : located at `/app/<name>.php`, it represents the related table. It always begins with an uppercase.
- a controller : located at `/app/Http/Controllers/<name>Controller.php`, it is always the name of the model followed by "Controller"
- 5 routes : located at `/routes/api.php`, the programm will create 2 GET, 1 DELETE, 1 PUT and 1 POST route. They represents : displaying all the resources, displaying a specific resource, destroying a specific resource, updating a specific resource and creating a specific resource. 

## Why should I use it
The main advantage is to make an API quickly. The ultimate goal is to let you focus on things that have a value, like user interfaces and innovations, things that for the record cannot be automatized (at least not now !).