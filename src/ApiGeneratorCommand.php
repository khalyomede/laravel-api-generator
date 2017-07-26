<?php

namespace Khalyomede\ApiGenerator;

use Illuminate\Console\Command;
use Exception;
use DB;

class ApiGeneratorCommand extends Command
{
    const QUERY_SHOW_TABLES = 'SHOW TABLES';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:generate {--table=} {--noTable=} {--suffix=} {--noCol=} {--fake=}';

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

        foreach( $this->tables as $this->table ) {
            $this->buildApi();
        }
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
        return $this->hasTheOption('suffix') ? ucfirst( preg_replace( "/^\b" . $this->getFirstOption('suffix') . "/i", '', $this->table ) ) : ucfirst( $this->table );
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
         * index
         */
        $code[16 - 1] = "\t\t" . 'return \\App\\' . $modelName . '::all();';
        
        /**
         * store
         */
        $code[37 - 1] = "\t\t" . "return \\App\\$modelName" . "::findOrFail( \\App\\$modelName" . '::create( $request->all() )->id );';
        
        /**
         * show
         */
        $code[48 - 1] = "\t\t" . 'return \\App\\' . $modelName . '::findOrFail( $id );';

        /**
         * update
         */
        $code[71 - 1] = "\t\t" . '$' . $name . ' = \\App\\' . $modelName .  '::findOrFail( $id );';
        array_splice( $code, 71 + 0, 0, "\t\t" . '' );
        array_splice( $code, 71 + 1, 0, "\t\t" . 'foreach( $request->input() as $key => $value ) {' );
        array_splice( $code, 71 + 2, 0, "\t\t\t" . '$' . $name . '->{ $key } = $value;' );
        array_splice( $code, 71 + 3, 0, "\t\t" . '}' );
        array_splice( $code, 71 + 4, 0, "\t\t" . '' );
        array_splice( $code, 71 + 5, 0, "\t\t" . '$' . $name . '->save();' );
        array_splice( $code, 71 + 6, 0, "\t\t" . '' );
        array_splice( $code, 71 + 7, 0, "\t\t" . 'return \\App\\' . $modelName . '::findOrFail( $id );' );

        /**
         * delete
         */
        /*
            $country = \App\Country::findOrFail( $id );

        $country->delete();

        return $country;
        */
        $code[90 - 1] = "\t\t" . '$' . $name . ' = \\App\\' . $modelName . '::findOrFail( $id );';
        array_splice( $code, 90 + 0, 0, "\t\t" . '' );
        array_splice( $code, 90 + 1, 0, "\t\t" . '$' . $name . '->delete();' );
        array_splice( $code, 90 + 2, 0, "\t\t" . '' );
        array_splice( $code, 90 + 3, 0, "\t\t" . 'return $' . $name . ';' );

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

        $lastLine = count($code) - 1;

        $name = $this->routeName();
        $controllerName = $this->controllerName();

        array_splice( $code, $lastLine + 0, 0, '' );
        array_splice( $code, $lastLine + 1, 0, "Route::resource('/$name', '" . $controllerName . "');" );

        $routesPath = $this->routesPath();

        file_put_contents( $routesPath, implode( $code, "\n" ) );

        echo "Ressource created successfully." . PHP_EOL;
    }
}