![GitHub tag](https://img.shields.io/github/tag/khalyomede/laravel-api-generator.svg)
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
- Can scope the tables you only need to expose to your API
- Can blacklist tables you dont like
- Can remove a prefix to each of your tables

## Next features
- Provides a neat JSON response for your API
- Always returns a JSON response, no matter it is a success or a database outage, ...
- Log everything, including fatal errors and exceptions
- Creates your relationships according to your tables foreign keys
- Can fill your tables if you need some fake data
- Can blacklist columns of tables to improve security

## Pre-requisites
You need to have an existing [Laravel](https://laravel.com/) **5.3+** project to be able to use this library.

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

## List of examples
- [Example of usage 1 : basic usage](#example-of-usage-1--basic-usage)
- [Example of usage 2 : white-listing your prefered tables](#example-of-usage-2--white-listing-your-prefered-tables)
- [Example of usage 3 : black listing the tables you dont want to expose](#example-of-usage-3--black-listing-the-tables-you-dont-want-to-expose)
- [Example of usage 4 : removing a suffix for each of the table exposed](#example-of-usage-4--removing-a-suffix-for-each-of-the-table-exposed)

## Example of usage 1 : basic usage
This is the simplest example that scan your database to get all the table name and work from this array.
```bash
php artisan api:generate
```
*Result :* this will create, for each tables in your database, a corresponding model, controller, and routes.

[back to the example list](#list-of-examples)
## Example of usage 2 : white-listing your prefered tables
This will only try build the API for the filtered tables. Note that it is a white-list, so only the specifyied table will be exposed to the API.
```bash
php artisan api:generate --table=user,post
```
*Result :* this will create the model, controller and routes only for the table `user` and `post` in this case.

[back to the example list](#list-of-examples)
## Example of usage 3 : black listing the tables you dont want to expose
This will remove the tables you specify from your full table list, thus preventing those to be exposed.
```bash
php artisan api:generate --noTable=user,post
```
If the full table list contains `user`, `post`, `address`, and `country`, only the tables `address` and `country` will be exposed. **Note :** if you specifyied `--table` white-list, `--noTable` will simply be ignored.

[back to the example list](#list-of-examples)
## Example of usage 4 : removing a suffix for each of the table exposed
This removes, from all table name, the word specifyied at the begining of your tables names. This can be useful if for example you are using a third-party system like Wordpress that will add some prefix to your table, but you prefer to keep it readable when asking your API some resources. 
```bash
php artisan api:generate --suffix=wp_
```
*Result :* this will removing the word at the begining of each tables, so each of the related models, controllers, and routes will be cleaned in consequence.

**Note :** If your tables do not begins with the word, this will have no effects on the route name.

[back to the example list](#list-of-examples)
## Help documentation
At anytime you can run `php artisan help api:generate` to get a complete list of all the option you can use, and their related description. This will always be the equivalent of this documentation. This command can also be used with any of the PHP artisan commands.
## How it works
This command will scan your `/.env` file. Make sure it exists and that your database credentials are correct.

Each time your run this command, at least 3 files are created :
- a model : located at `/app/<name>.php`, it represents the related table. It always begins with an uppercase.
- a controller : located at `/app/Http/Controllers/<name>Controller.php`, it is always the name of the model followed by "Controller"
- a resource route : located at `/routes/api.php`. Instead of creating 5 routes (index, show, store, update, destroy), we use Laravel Resource Controller to bind all these routes into one single call of `Route::resource()`. This make the file more readable, but you should know it if you would like to tweak the end result. 

## Why should I use it
The main advantage is to make an API quickly. The ultimate goal is to let you focus on things that have a value, like user interfaces and innovations.

## Limitations
- For now, if you try to run another time the command, this will not override previous routes but instead this will add again all the necessary routes. This has no impact in the final usage, but this can make the file `/routes/api.php` a little bit overwhelmed if you run it several times.
- The code of each models is not as clear as it should be. An effort should be made to re-order the variables.
- ~~Currently there is no checking if you specify table that does not exists in filters option like `--table`. They will be "available" but will throw a fatal exception because the related table does not exists when you will browse the related API routes. Futures update will check the existence of the table prior its processing~~ **fixed** 2017-07-26 since `v0.1.1`.