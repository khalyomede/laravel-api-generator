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

## Create your own Laravel Services Providers
This library has been made possible with the help of Services Providers. Laravel ships with an interesting bundle system to ensure every comunity package introduce themselves well to an existing Laravel project. I strongly encourage to check one of the best tutorials out there made [by our friends at DevDojo](https://devdojo.com/blog/tutorials/how-to-create-a-laravel-package) if you do not know how to get started and create your own laravel packages.

## Features
- Scans your database and creates the models, controllers, and routes according to your tables properties *>= **0.0.1***
- Can scope the tables you only need to expose to your API *>= **0.1.0***
- Can blacklist tables you dont like *>= **0.2.0***
- Can remove a prefix to each of your exposed tables *>= **0.3.0***
- Can blacklist columns of tables to improve security *>= **0.4.0***
- Can fill your tables if you need some fake data *>= **0.5.0***

## Next features
- Provides a neat JSON response for your API
- Always returns a JSON response, no matter it is a success or a database outage, ...
- Log everything, including fatal errors and exceptions
- Creates your relationships according to your tables foreign keys
- Can specify which tables you want to fill with fake data
- Can specify which tables you do not want to fill with fake data

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
Last thing to check is that your `.env` file is well configured. When everything is okay, you are ready to get started !

## List of examples
- [Example of usage 1 : basic usage](#example-of-usage-1--basic-usage)
- [Example of usage 2 : white-listing your prefered tables](#example-of-usage-2--white-listing-your-prefered-tables)
- [Example of usage 3 : black listing the tables you dont want to expose](#example-of-usage-3--black-listing-the-tables-you-dont-want-to-expose)
- [Example of usage 4 : removing a prefix for each of the table exposed](#example-of-usage-4--removing-a-prefix-for-each-of-the-table-exposed)
- [Example of usage 5 : removing columns from some particular tables](#example-of-usage-5--removing-columns-from-some-particular-tables)
- [Example of usage 6 : inserting fake data after the routes have been generated](#example-of-usage-6--inserting-fake-data-after-the-routes-have-been-generated)

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

**Note :** If you specify a table that does not exists in your database, this will throw an error.

[back to the example list](#list-of-examples)
## Example of usage 3 : black listing the tables you dont want to expose
This will remove the tables you specify from your full table list, thus preventing those to be exposed.
```bash
php artisan api:generate --noTable=user,post
```
If the full table list contains `user`, `post`, `address`, and `country`, only the tables `address` and `country` will be exposed. 

**Note :** if you already specifyied [`--table`](#example-of-usage-2--white-listing-your-prefered-tables) option, this option will be ignored.

**Note :** If you specify a table that does not exists in your database, this will throw an error.

[back to the example list](#list-of-examples)
## Example of usage 4 : removing a prefix for each of the table exposed
This removes, from all table name, the word specifyied at the begining of your tables names. This can be useful if for example you are using a third-party system like Wordpress that will add some prefix to your table, but you prefer to keep it readable when asking your API some resources. 
```bash
php artisan api:generate --prefix=wp_
```
*Result :* this will removing the word at the begining of each tables, so each of the related models, controllers, and routes will be cleaned in consequence.

**Note :** If your tables do not begins with the word, this will have no effects on the route name.

[back to the example list](#list-of-examples)
## Example of usage 5 : removing columns from some particular tables
This will removes the column from the all the `GET` methods, but you can still add some values to these columns using `POST`, `PUT/PATCH` or `DELETE` methods. This is usefull if for example you do not want to expose passwords of users after they have been creating (thus, preventing people that scan your network from getting this information through `GET`).
```bash
php artisan api:generate --noCol=user.password,customer.birthDate
```
This will expose all columns of all the tables, except for the tables `user` and `customer` that will be nerfed for the `show` and `index` (`GET`) methods.

**Note :** If you specify column or table that does not exists, this will have no effect on the created model and exposed column in your API.

[back to the example list](#list-of-examples)
## Example of usage 6 : inserting fake data after the routes have been generated
This comes really handy if you worked on your database schema but do not have data yet.
**WARNING** use with caution as this may add news rows to already filled tables.
```bash
php artisan api:generate --fake=10
```
This will genrate all the necessary files to build the API, and will creates 10 rows of fake data to help you begin to work with near-production API resources.

*Note :* The string columns will be filled with one single sentence of Lorem Ipsum.

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
- ~~Currently there is no checking if you specify table that does not exists in filters option like `--table`. They will be "available" but will throw a fatal exception because the related table does not exists when you will browse the related API routes. Futures update will check the existence of the table prior its processing~~ **fixed** since `v0.1.1`.
- If you specify a prefix for your tables, you will still need to constantly prepend this prefix to the filters (like `--table` and `noTable`). An effort should be made to automatically add the prefix if needed.

## Semantic Version ready
This library follows the version [semver guidelines v2.0.0](http://semver.org/) that ensure every step we engage, by adding new functionalities or providing bug fixes, follows these guide and make your job easier by trusting this library as versioningly stable. We strongly encourage using always the last version of the major version number, as it always provides the latest security patches. If you would like to go from a major version to another, an effort will be done to help users passing from one to another.