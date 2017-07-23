<?php

namespace Khalyomede\LaravelApiGenerator;

use Illuminate\Console\Command;

class ApiGeneratorCommand extends Command
{
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
        echo 'hello console world!';
    }
}
