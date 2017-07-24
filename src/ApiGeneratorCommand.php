<?php

namespace Khalyomede\ApiGenerator;

use Illuminate\Console\Command;
use DB;

class ApiGeneratorCommand extends Command
{
    const QUERY_SHOW_TABLES = 'SHOW TABLES';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate models, controllers and API routes from a database.';

    protected $table;
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
        $tables = $this->tables();

        foreach( $tables as $this->table ) {
            $this->deleteModel();
            $this->createModel();
        }
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
        $table = $this->table;
        $path = $this->modelPath();

        if( self::unavailable_file( $path ) ) {
            throw new Exception("the command process stoped because the file $path is already opened in another program and cannot be removed");
        }

        if( file_exists( $path ) ) {
            unlink( $path );    
        }
    }

    private function modelPath() {
        return app_path($this->modelName() . '.php');
    }

    /**
     * @param string $path
     * @return bool
     */
    private static function unavailable_file( $path ) {
        return file_exists( $path ) && ! is_writable( $path );
    }

    /**
     * @return void
     */
    private function createModel() {
        $columns = $this->columns();
        $name = $this->modelName();

        $this->call("make:model", ['name' => $name]);
        $this->nextModelRowIndex = 8;
        /**
         * @todo change $this->createModelProperty to be able to call following methods in any order (currently bug)
         */
        $this->createModelProperty('protected', 'table', $this->table);        
        $this->createModelProperty('protected', 'fillable', $columns);
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
            $code[ $this->nextModelRowIndex ] = "\t$scope $name = [";
            $valueCount = count($value);

            $index = 0;

            foreach( $value as $index => $item ) {
                $string = "\t\t'$item'";

                if( $valueCount !== $index + 1 ) {
                    $string .= ',';
                }

                array_splice( $code, $this->nextModelRowIndex + $index + 1, 0, $string );
            }

            array_splice( $code, $this->nextModelRowIndex + 1 + $index + 1, 0, "\t];");
        }
        else if ( is_numeric($value) ) {
            
        }
        else {
            $code[ $this->nextModelRowIndex ] = "\t$scope $name = '$value';";
            
            array_splice( $code, $this->nextModelRowIndex, 0, '' );
            array_splice( $code, $this->nextModelRowIndex, 0, '' );
        }

        file_put_contents( $this->modelPath(), implode($code, "\n") );
    }

    private function getModelCodeToArray() {
        return explode( "\n", file_get_contents( $this->modelPath() ) );
    }

    private function modelName() {
        return ucfirst( $this->table );
    }

    /**
     * @return string[]
     */
    private function columns() {
        return DB::getSchemaBuilder()->getColumnListing( $this->table );
    }
}
