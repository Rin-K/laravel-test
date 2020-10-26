<?php

namespace App\Drivers;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Addressimport;
use App\Contracts\Import_address_interface;


class Json extends Addressimport implements Import_address_interface
{
  
    protected $data;

  
    public function __construct($data_file_path = '')
    {
        $this->loadDataFromFile($data_file_path);
        $this->data = json_decode($this->data);
    }

 
    public function run($console = null)
    {
        $this->setDataToIterator();

        if ($console instanceof Command) {
            $progress_bar = $console->getOutput()->createProgressBar($this->data->count());
        }

        $this->data
        ->each(function ($data_object) use ($progress_bar) {
            $this->mapped_data = $this->mapData($this->data_map, $data_object);
            $this->populateData($this->mapped_data);

            $progress_bar->advance();
        });
    }

    protected function setDataToIterator()
    {
       
		if (!empty($this->import_address_data['path_to_model_iterator'])) {
            $this->data = collect($this->findValueInData($this->import_address_data['path_to_model_iterator'], $this->data));	
        }
    }
}
