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
- Creates fields `protected $table` and `protected $fillable` to get ready to insert data

## Next features
- Creates your relationships according to your tables foreign keys
