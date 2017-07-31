<?php

namespace Khalyomede\ApiGenerator;

use Illuminate\Console\Command;
use Faker\Factory as Faker;
use Exception;
use DB;

class ApiGeneratorCommand extends Command
{
    const QUERY_SHOW_TABLES = 'SHOW TABLES';
    const EXCEPTION_HANDLER_MIDDLEWARE = 'ExceptionHandlerMiddleware';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:generate {--table= : Coma separated list of tables you only want to expose} {--noTable= : Coma separated list of tables you want to exclude from the exposed tables} {--prefix= : String to remove for each exposed tables} {--noCol= : Coma separated list of table followed by a dot and the name of the column name you do not want to expose through GET methods} {--fake= : Number of rows to add to the fake data inserted in the exposed tables} {--uniform : Specify this option if you want to retrieve your resources in a consistent way (for more information browse github package khalyomede/php-jur)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate models, controllers and API routes from a database.';

    protected $table;
    protected $tables;
    protected $nextModelRowIndex;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->createMiddleware( self::EXCEPTION_HANDLER_MIDDLEWARE );

        /**
         * White list
         */
        if( $this->hasTheOption('table') ) {
            $this->tables = $this->getOption('table');
        }
        /**
         * Black list
         */
        else if( $this->hasTheOption('noTable') ) {
            $this->tables = $this->tableBlackList();
        }
        /**
         * Every tables
         */
        else {
            $this->tables = $this->tables();
        }

        $this->checkIfTablesExist();

        $this->openRouteGroup();

        foreach( $this->tables as $this->table ) {
            $this->buildApi();
        }

        $this->closeRouteGroup();
    }

    private function openRouteGroup() {
        $code = $this->getRouteCodeToArray();

        $lastLine = count($code);

        array_splice( $code, $lastLine + 0, 0, '' );
        array_splice( $code, $lastLine + 1, 0, "Route::group(['middleware' => \App\Http\Middleware\ExceptionHandlerMiddleware::class], function() {" );

        $routesPath = $this->routesPath();

        file_put_contents( $routesPath, implode( $code, "\n" ) );
    }

    private function closeRouteGroup() {
        $code = $this->getRouteCodeToArray();

        $lastLine = count($code);

        array_splice( $code, $lastLine, 0, "});" );

        $routesPath = $this->routesPath();

        file_put_contents( $routesPath, implode( $code, "\n" ) );
    }

    /**
     * @todo clean up this method and all related methods by using private properties
     */
    private function createMiddleware( $name ) {
        $path = app_path() . '/Http/Middleware/' . $name . '.php';

        $this->deleteMiddleware( $path );

        $this->call('make:middleware', [
            'name' => $name
        ]);

        $this->fillMiddleware( $path );
    }

    private function deleteMiddleware( $path ) {
        self::deleteFile( $path );
    }

    private function fillMiddleware( $path ) {
        $code = explode("\n", file_get_contents( $path ) );

        array_splice( $code, 5 + 0, 0, "use Illuminate\Database\Eloquent\ModelNotFoundException;" );
        array_splice( $code, 5 + 1, 0, "use Illuminate\Validation\ValidationException;" );
        array_splice( $code, 5 + 2, 0, "use Exception;" );

        if( $this->option('uniform') ) {           
            array_splice( $code, 5 + 3, 0, "use Khalyomede\JUR;" );

            array_splice( $code, 12 + 0, 0, "\t" . 'const UNKNOWN_ERROR = 1;');
            array_splice( $code, 12 + 1, 0, "\t" . 'const MODELNOTFOUND_ERROR = 2;');
            array_splice( $code, 12 + 2, 0, "\t" . 'const VALIDATION_ERROR = -1;');

            $code[24] = "\t\t" . '$output = $next($request);';
            
            array_splice( $code, 24 + 1, 0, "\t\t" . '' );
            array_splice( $code, 24 + 2, 0, "\t\t" . 'try {' );
            array_splice( $code, 24 + 3, 0, "\t\t\t" . 'if( ! is_null( $output->exception ) ) {' );
            array_splice( $code, 24 + 4, 0, "\t\t\t\t" . 'throw new $output->exception;' );
            array_splice( $code, 24 + 5, 0, "\t\t\t" . '}' );
            array_splice( $code, 24 + 6, 0, "\t\t\t" . '' );
            array_splice( $code, 24 + 7, 0, "\t\t\t" . 'return $output;' );
            array_splice( $code, 24 + 8, 0, "\t\t" . '}' );
            array_splice( $code, 24 + 9, 0, "\t\t" . 'catch( ValidationException $e ) {' );
            array_splice( $code, 24 + 10, 0, "\t\t\t" . 'return response()->json(' );
            array_splice( $code, 24 + 11, 0, "\t\t\t\t" . 'JUR::fail()' );
            array_splice( $code, 24 + 12, 0, "\t\t\t\t" . '->code( self::VALIDATION_ERROR )' );
            array_splice( $code, 24 + 13, 0, "\t\t\t\t" . '->message( $e->getMessage() )' );
            array_splice( $code, 24 + 14, 0, "\t\t\t\t" . '->resolved()' );
            array_splice( $code, 24 + 15, 0, "\t\t\t\t" . '->toArray(), 400' );
            array_splice( $code, 24 + 16, 0, "\t\t\t" . ');' );
            array_splice( $code, 24 + 17, 0, "\t\t" . '}' );
            array_splice( $code, 24 + 18, 0, "\t\t" . 'catch( ModelNotFoundException $e ) {' );
            array_splice( $code, 24 + 19, 0, "\t\t\t" . 'return response()->json(' );
            array_splice( $code, 24 + 20, 0, "\t\t\t\t" . 'JUR::error()' );
            array_splice( $code, 24 + 21, 0, "\t\t\t\t\t" . '->code( self::MODELNOTFOUND_ERROR )' );
            array_splice( $code, 24 + 22, 0, "\t\t\t\t\t" . '->resolved()' );
            array_splice( $code, 24 + 23, 0, "\t\t\t\t\t" . '->toArray(), 404' );
            array_splice( $code, 24 + 24, 0, "\t\t\t" . ');' );
            array_splice( $code, 24 + 25, 0, "\t\t" . '}' );
            array_splice( $code, 24 + 26, 0, "\t\t" . 'catch( \Exception $e ) {' );
            array_splice( $code, 24 + 27, 0, "\t\t\t" . 'return response()->json(' );
            array_splice( $code, 24 + 28, 0, "\t\t\t\t" . 'JUR::error()' );
            array_splice( $code, 24 + 29, 0, "\t\t\t\t\t" . '->code( self::UNKNOWN_ERROR )' );
            array_splice( $code, 24 + 30, 0, "\t\t\t\t\t" . '->resolved()' );
            array_splice( $code, 24 + 31, 0, "\t\t\t\t\t" . '->toArray(), 500' );
            array_splice( $code, 24 + 32, 0, "\t\t\t" . ');' );
            array_splice( $code, 24 + 33, 0, "\t\t" . '}' );
        }
        else {
            $code[20] = "\t\t" . '$output = $next($request);';

            array_splice( $code, 21 + 0, 0, "\t\t" . '' );
            array_splice( $code, 21 + 1, 0, "\t\t" . 'try {' );
            array_splice( $code, 21 + 2, 0, "\t\t\t" . 'if( ! is_null( $output->exception ) ) {' );
            array_splice( $code, 21 + 3, 0, "\t\t\t\t" . 'throw new $output->exception;' );
            array_splice( $code, 21 + 4, 0, "\t\t\t" . '}' );
            array_splice( $code, 21 + 5, 0, "\t\t\t" . '' );
            array_splice( $code, 21 + 6, 0, "\t\t\t" . 'return $output;' );
            array_splice( $code, 21 + 7, 0, "\t\t" . '}' );
            array_splice( $code, 21 + 8, 0, "\t\t" . 'catch( ValidationException $e ) {' );
            array_splice( $code, 21 + 9, 0, "\t\t\t" . 'return response()->json( $e->getMessage(), 400 );' );
            array_splice( $code, 21 + 10, 0, "\t\t" . '}' );
            array_splice( $code, 21 + 11, 0, "\t\t" . 'catch( ModelNotFoundException $e ) {' );
            array_splice( $code, 21 + 12, 0, "\t\t\t" . "return response()->json('the resource could not be found or has been removed', 404);" );
            array_splice( $code, 21 + 13, 0, "\t\t" . '}' );
            array_splice( $code, 21 + 14, 0, "\t\t" . 'catch( \Exception $e ) {' );
            array_splice( $code, 21 + 15, 0, "\t\t\t" . "return response()->json('the request could not be processed', 500);" );
            array_splice( $code, 21 + 16, 0, "\t\t" . '}' );
        }        

        /**
         * File existence checkings
         */
        if( ! file_exists( $path ) ) {
            die("$path does not exists");
        }

        if( ! is_writable( $path ) ) {
            die( "$path is already opened in another program" );
        }

        file_put_contents( $path , implode($code, "\n") );
    }

    private function primaryKey() {
        $indexes = DB::connection()->getDoctrineSchemaManager()->listTableIndexes( $this->table );

        $column = '';

        foreach( $indexes as $index ) {
            if( $index->isPrimary() ) {
                $column = $index->getColumns()[0];
            }
        }

        return $column;
    }

    private function columns() {
        $columns = [];

        $bulkColumns = DB::connection()->getDoctrineSchemaManager()->listTableColumns( $this->table );

        foreach( $bulkColumns as $bulkColumn ) {
            $columns[ $bulkColumn->getName() ] = $bulkColumn->getType()->getName();
        }

        return $columns;
    }

    private function tableBlackList() {
        return array_diff( $this->tables(), $this->getOption('noTable') );
    }

    private function checkIfTablesExist() {
        $schema = DB::getSchemaBuilder();

        foreach( $this->tables as $table ) {
            if( ! $schema->hasTable( $table ) ) {
                throw new Exception("\"$table\" does not exists in your database \"" . (string) env('DB_DATABASE') . "\"");
            }
        }
    }

    private function hasTheOption( $key ) {
        return ! is_null( $this->option( $key ) );
    }

    private function buildApi() {
        $this->createModel();
        $this->createController();
        $this->createRoutes();

        if( $this->hasTheOption('fake') ) {
            $faker = Faker::create();

            $count = $this->getfirstOption('fake');

            $columns = $this->columns();

            $primaryKey = $this->primaryKey();

            $fakes = [];

            for( $i = 0; $i < $count; $i++ ) {
                foreach( $columns as $column => $type ) {
                    if( $column != $primaryKey ) {
                        /**
                         * @see http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/types.html
                         * @see https://github.com/fzaninotto/Faker
                         */
                        if( in_array($type, ['smallint', 'integer', 'bigint']) ) {
                            $fakes[ $i ][ $column ] = $faker->randomNumber();
                        }
                        else if( in_array($type, ['decimal', 'float']) ) {
                            $fakes[ $i ][ $column ] = $faker->randomFloat();   
                        }
                        else if( in_array($type, ['text', 'string', 'guid']) ) {
                            $fakes[ $i ][ $column ] = $faker->paragraph(1);
                        }
                        else if( $type === 'boolean' ) {
                            $fakes[ $i ][ $column ] = $faker->boolean();
                        }
                        else if( in_array($type, ['date', 'date_immutable']) ) {
                            $fakes[ $i ][ $column ] = $faker->date();   
                        }
                        else if( in_array($type, ['datetime', 'datetime_immutable']) ) {
                            $fakes[ $i ][ $column ] = $faker->date('Y-m-d H:i:s');
                        }
                        else if( in_array($type, ['time', 'time_immutable']) ) {
                            $fakes[ $i ][ $column ] = $faker->time();   
                        }
                    }                    
                }
            }

            $instance = '\App\\' . $this->modelName();

            $instance::insert($fakes);
        }
    }

    private function getOption( $key ) {
        return explode( ',', (string) $this->option( $key ) );
    }

    private function getFirstOption( $key ) {
        return isset($this->getOption( $key )[0]) ? $this->getOption( $key )[0] : '';
    }

    /**
     * @return string[]
     */
    private function tables() {
        $tables = [];

        $bulkTables = DB::select( self::QUERY_SHOW_TABLES );

        foreach( $bulkTables as $table ) {
            $tables[] = $table->{key($table)};
        }

        return $tables;
    }

    /**
     * @param string $name
     * @return void
     * @throws Exception
     */
    private function deleteModel() {
        $path = $this->modelPath();

        self::deleteFile( $path );
    }

    private function modelPath() {
        return app_path($this->modelName() . '.php');
    }

    /**
     * @return void
     */
    private function createModel() {
        $this->deleteModel();

        $name = $this->modelName();

        $this->call("make:model", ['name' => $name]);
        $this->nextModelRowIndex = 8;
        /**
         * @todo change $this->createModelProperty to be able to call following methods in any order (currently bug)
         */
        $this->createModelProperty('protected', 'table', $this->table);  
        $this->createModelProperty('public', 'timestamps', 'false');      
        $this->createModelProperty('protected', 'hidden', $this->columnsBlackList());
        $this->createModelProperty('protected', 'guarded', []);
    }

    private function columnsBlackList() {
        $guarded = [];

        if( $this->hasTheOption('noCol') ) {
            $bulkColumns = $this->getOption('noCol');

            foreach( $bulkColumns as $bulkColumn ) {
                list( $table, $column ) = explode('.', $bulkColumn);

                if( $this->table === $table ) {
                    $guarded[] = $column;
                }
            }
        }  
        else {
            // nothing to do, $guarded is already empty
        }

        return $guarded;
    }

    /**
     * If passing an array, make sure it is only a one dimensionnal array
     *
     * @return void
     * @param string $scrope
     * @param string $name
     * @param mixed $value
     */
    private function createModelProperty( $scope, $name, $value ) {
        $code = $this->getModelCodeToArray();

        if( is_array($value) ) {
            array_splice($code, $this->nextModelRowIndex, 0, "\t$scope " . '$' . "$name = [");

            $this->nextModelRowIndex++;

            $valueCount = count($value);

            $index = 0;

            foreach( $value as $index => $item ) {
                $string = "\t\t'$item'";

                if( $valueCount !== $index + 1 ) {
                    $string .= ',';
                }

                array_splice( $code, $this->nextModelRowIndex, 0, $string );

                $this->nextModelRowIndex++;
            }

            array_splice( $code, $this->nextModelRowIndex, 0, "\t];");

            $this->nextModelRowIndex++;

            array_splice( $code, $this->nextModelRowIndex, 0, "\t" . '' );

            $this->nextModelRowIndex++;
        }
        else if( is_numeric($value) || $value == 'false' || $value == 'true' ) {
            array_splice( $code, $this->nextModelRowIndex, 0, "\t$scope " . '$' . "$name = $value;");

            $this->nextModelRowIndex++;
            
            array_splice( $code, $this->nextModelRowIndex, 0, '' );

            $this->nextModelRowIndex++;

            array_splice( $code, $this->nextModelRowIndex, 0, '' );
        }
        else {
            $code[ $this->nextModelRowIndex ] = "\t$scope " . '$' . "$name = '$value';";

            $this->nextModelRowIndex++;
            
            array_splice( $code, $this->nextModelRowIndex, 0, '' );

            $this->nextModelRowIndex++;

            array_splice( $code, $this->nextModelRowIndex, 0, '' );
        }

        file_put_contents( $this->modelPath(), implode($code, "\n") );
    }

    private function getModelCodeToArray() {
        return explode( "\n", file_get_contents( $this->modelPath() ) );
    }

    private function modelName() {
        return $this->hasTheOption('prefix') ? ucfirst( preg_replace( "/^\b" . $this->getFirstOption('prefix') . "/i", '', $this->table ) ) : ucfirst( $this->table );
    }

    private function createController() {
        $this->deleteController();

        $modelName = $this->modelName();

        $this->call('make:controller', [
            '--resource' => true,
            'name' => $this->controllerName()
        ]);

        $name = $this->tableName();

        $code = $this->getControllerCodeToArray();

        /**
         * Dependencies
         */
        if( $this->option('uniform') ) {
            array_splice( $code, (6 - 1) + 0, 0, 'use Khalyomede\JUR;' );
            array_splice( $code, (6 - 1) + 1, 0, 'use App\\' . $modelName . ';' );
        }
        else {
            array_splice( $code, (6 - 1) + 0, 0, 'use App\\' . $modelName . ';' );
        }
        
        /**
         * Index
         */
        if( $this->option('uniform') ) {
            $code[ 18 - 1 ] = "\t\t" . 'return JUR::reset()';

            array_splice( $code, 18 + 0, 0, "\t\t\t" . '->requested()' );
            array_splice( $code, 18 + 1, 0, "\t\t\t" . '->get()' );
            array_splice( $code, 18 + 2, 0, "\t\t\t" . '->data( ' . $modelName . '::all() )' );
            array_splice( $code, 18 + 3, 0, "\t\t\t" . '->success()' );
            array_splice( $code, 18 + 4, 0, "\t\t\t" . '->resolved()' );
            array_splice( $code, 18 + 5, 0, "\t\t\t" . '->toArray();' );
        }
        else {
            $code[ 17 - 1 ] = "\t\t" . 'return ' . $modelName . '::all();';
        }

        /**
         * Store
         */
        if( $this->option('uniform') ) {
            $code[ 45 - 1 ] = "\t\t" . 'return JUR::reset()';

            array_splice( $code, 45 + 0, 0, "\t\t\t" . '->requested()' );
            array_splice( $code, 45 + 1, 0, "\t\t\t" . '->post()' );
            array_splice( $code, 45 + 2, 0, "\t\t\t" . '->data( ' . $modelName . '::findOrFail( ' . $modelName . '::create( $request->input() )->id ) )' );
            array_splice( $code, 45 + 3, 0, "\t\t\t" . '->success()' );
            array_splice( $code, 45 + 4, 0, "\t\t\t" . '->resolved()' );
            array_splice( $code, 45 + 5, 0, "\t\t\t" . '->toArray();' );
        }
        else {
            $code[ 38 - 1 ] = "\t\t" . 'return ' . $modelName . '::findOrFail( ' . $modelName . '::create( $request->input() )->id );';
        }

        /**
         * Show
         */
        if( $this->option('uniform') ) {
            $code[ 62 - 1 ] = "\t\t" . 'return JUR::reset()';

            array_splice( $code, 62 + 0, 0, "\t\t\t" . '->requested()' );
            array_splice( $code, 62 + 1, 0, "\t\t\t" . '->get()' );
            array_splice( $code, 62 + 2, 0, "\t\t\t" . '->data( ' . $modelName . '::findOrFail( $id ) )' );
            array_splice( $code, 62 + 3, 0, "\t\t\t" . '->success()' );
            array_splice( $code, 62 + 4, 0, "\t\t\t" . '->resolved()' );
            array_splice( $code, 62 + 5, 0, "\t\t\t" . '->toArray();' );
        }
        else {
            $code[ 49 - 1 ] = "\t\t" . 'return ' . $modelName . '::findOrFail( $id );';
        }

        /**
         * Update
         */
        if( $this->option('uniform') ) {
            $code[ 91 - 1 ] = "\t\t" . '$' . $name . ' = ' . $modelName . '::findOrFail( $id );';

            array_splice( $code, 91 + 0, 0, "\t\t" . '' );
            array_splice( $code, 91 + 1, 0, "\t\t" . 'foreach( $request->input() as $key => $value ) {' );
            array_splice( $code, 91 + 2, 0, "\t\t\t" . '$' . $name . '->{ $key } = $value;' );
            array_splice( $code, 91 + 3, 0, "\t\t" . '}' );
            array_splice( $code, 91 + 4, 0, "\t\t" . '' );
            array_splice( $code, 91 + 5, 0, "\t\t" . '$' . $name . '->save();' );
            array_splice( $code, 91 + 6, 0, "\t\t" . '' );
            array_splice( $code, 91 + 7, 0, "\t\t" . 'return JUR::reset()' );
            array_splice( $code, 91 + 8, 0, "\t\t\t" . '->requested()' );
            array_splice( $code, 91 + 9, 0, "\t\t\t" . '->put()' );
            array_splice( $code, 91 + 10, 0, "\t\t\t" . '->data( ' . $modelName . '::findOrFail( $id ) )' );
            array_splice( $code, 91 + 11, 0, "\t\t\t" . '->success()' );
            array_splice( $code, 91 + 12, 0, "\t\t\t" . '->resolved()' );
            array_splice( $code, 91 + 13, 0, "\t\t\t" . '->toArray();' );
        }
        else {
            $code[ 72 - 1 ] = "\t\t" . '$' . $name . ' = ' . $modelName . '::findOrFail( $id );';

            array_splice( $code, 72 + 0, 0, "\t\t" . '' );
            array_splice( $code, 72 + 1, 0, "\t\t" . 'foreach( $request->input() as $key => $value ) {' );
            array_splice( $code, 72 + 2, 0, "\t\t\t" . '$' . $name . '->{ $key } = $value;' );
            array_splice( $code, 72 + 3, 0, "\t\t" . '}' );
            array_splice( $code, 72 + 4, 0, "\t\t" . '' );
            array_splice( $code, 72 + 5, 0, "\t\t" . '$' . $name . '->save();' );
            array_splice( $code, 72 + 6, 0, "\t\t" . '' );
            array_splice( $code, 72 + 7, 0, "\t\t" . 'return ' . $modelName . '::findOrFail( $id );' );
        }

        /**
         * Delete
         */
        if( $this->option('uniform') ) {
            $code[ 116 - 1 ] = "\t\t" . '$' . $name . ' = ' . $modelName . '::findOrFail( $id );';

            array_splice( $code, 116 + 0, 0, "\t\t" . '' );
            array_splice( $code, 116 + 1, 0, "\t\t" . '$' . $name . '->delete();' );
            array_splice( $code, 116 + 2, 0, "\t\t" . '' );
            array_splice( $code, 116 + 3, 0, "\t\t" . 'return JUR::reset()' );
            array_splice( $code, 116 + 4, 0, "\t\t\t" . '->requested()' );
            array_splice( $code, 116 + 5, 0, "\t\t\t" . '->delete()' );
            array_splice( $code, 116 + 6, 0, "\t\t\t" . '->data( $' . $name . ' )' );
            array_splice( $code, 116 + 7, 0, "\t\t\t" . '->success()' );
            array_splice( $code, 116 + 8, 0, "\t\t\t" . '->resolved()' );
            array_splice( $code, 116 + 9, 0, "\t\t\t" . '->toArray();' );
        }
        else {
            $code[ 91 - 1 ] = "\t\t" . '$' . $name . ' = ' . $modelName . '::findOrFail( $id );';

            array_splice( $code, 91 + 0, 0, "\t\t" . '' );
            array_splice( $code, 91 + 1, 0, "\t\t" . '$' . $name . '->delete();' );
            array_splice( $code, 91 + 2, 0, "\t\t" . '' );
            array_splice( $code, 91 + 3, 0, "\t\t" . 'return $' . $name . ';' );
        }

        file_put_contents( $this->controllerPath(), implode( $code, "\n" ) );
    }

    private function getControllerCodeToArray() {
        return explode("\n", file_get_contents( $this->controllerPath() ));
    }

    private function deleteController() {
        $path = $this->controllerPath();

        self::deleteFile( $path );
    }

    private function controllerName() {
        return $this->modelName() . 'Controller';
    }

    private function controllerPath() {
        return app_path('Http/Controllers/' . $this->controllerName() . '.php');
    }

    private static function deleteFile( $path ) {
        if( file_exists( $path ) ) {
            unlink( $path );    
        }
    }

    private function routesName() {
        return 'api';
    }

    private function routesPath() {
        return base_path('routes/' . $this->routesName() . '.php' );
    }

    private function getRouteCodeToArray() {
        return explode("\n", file_get_contents( $this->routesPath() ) );
    }

    private function tableName() {
        return strtolower($this->table);
    }

    private function routeName() {
        return strtolower( $this->modelName() );
    }

    private function createRoutes() {
        $code = $this->getRouteCodeToArray();

        $lastLine = count($code);

        $name = $this->routeName();
        $controllerName = $this->controllerName();

        array_splice( $code, $lastLine + 1, 0, '' );
        array_splice( $code, $lastLine + 2, 0, "\tRoute::resource('/$name', '" . $controllerName . "');" );

        $routesPath = $this->routesPath();

        file_put_contents( $routesPath, implode( $code, "\n" ) );

        echo "Ressource created successfully." . PHP_EOL;
    }
}