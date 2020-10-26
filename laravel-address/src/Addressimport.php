<?php

namespace App;

use Illuminate\Support\Facades\File;


class Addressimport
{
    use Traits\sData;
    use Traits\PopulatesDatabase;

    
    protected $path_to_import__file;

    
    protected $import__data;

    
    protected $data_;

   
    protected $ped_data;

    
    protected $model;

    
    protected $raw_import_data;

    
    public function loadFromFile($_file)
    {
        $this->path_to_import__file = $_file;
        $import_source_path = storage_path(config('import.storage__path') . $_file);

        if (!File::exists($import_source_path)) {
            throw new \Exception("Failed to load at $import_source_path");
        }

        $this->import__data = json_decode(File::get($import_source_path), true);
        $this->data_ = $this->import__data['data_'];
        $this->model = $this->import__data['model'];
    }

    public function loadDataFromFile($data_file)
    {
        $this->path_to_import__file = $data_file;
        $import_file_path = storage_path(config('import.storage_path') . $data_file);

        if (!File::exists($import_file_path)) {
            throw new \Exception("Failed to load at $import_file_path");
        }

        $this->raw_import_data = File::get($import_file_path);
    }

}