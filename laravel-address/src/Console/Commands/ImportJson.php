<?php

namespace App\Console\Commands;

use App\Drivers\Json;
use Illuminate\Console\Command;


class ImportJson extends Command
{
   
    protected $signature = 'import:json {address-map} {data-file}';

   
    protected $description = 'Run import data';

   
    public function __construct()
    {
        parent::__construct();
    }

  
    public function handle()
    {
        $import = new Json($this->argument('data-file'));
        $import->loadAddressFromFile($this->argument('address-map'));
        $import->run($this);
    }
}
