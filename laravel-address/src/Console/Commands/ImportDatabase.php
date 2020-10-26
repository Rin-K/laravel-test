<?php

namespace App\Console\Commands;

use App\Drivers\Database;
use Illuminate\Console\Command;


class ImportDatabase extends Command
{
   
    protected $signature = 'import:database {address-map} {--prefix=}';

   
    protected $description = 'Run the import using Database';

  
    public function __construct()
    {
        parent::__construct();
    }

 
    public function handle()
    {
        $import = new Database($this->option('prefix'));
        $import->loadAddressFromFile($this->argument('address-map'));
        $import->run($this);
    }
}
