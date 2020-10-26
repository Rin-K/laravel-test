<?php

namespace App\Drivers;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\BaseImportProcessor;
use App\Contracts\ImportDriverContract;


class Database extends BaseImportProcessor implements ImportDriverContract
{
    
	protected $query;
	
    protected $prefix = '';

    
    public function loadAddressFromFile($map_file)
    {
        parent::loadAddressFromFile($map_file);

        $this->query = $this->import_address_data['query'];
    }

    public function __construct($prefix = '')
    {
        $this->prefix = $prefix;
    }
   
    public function run($console = null)
    {
       
        if ($console instanceof Command) {
            $total_num_rows = DB::connection(config('import.import_db_connection'))
                ->table($this->prefix . $this->query['table'])
                ->count();
            $progress_bar = $console->getOutput()->createProgressBar($total_num_rows);
        }

        DB::connection(config('import.import_db_connection'))
            ->table($this->prefix . $this->query['table'])
            ->orderBy($this->data_map['id'])
            ->chunk(config('import.db_chunk_size'), function ($data_chunk) use ($progress_bar) {
                $data_chunk
                    ->each(function ($data_object) use ($progress_bar) {
                        if (!empty($this->query['relationships'])) {
                            $this->getRelationshipData($data_object, $this->query['relationships']);
                        }
                        $this->mapped_data = $this->mapData($this->data_map, $data_object);
                        $this->populateData($this->mapped_data);

                        $progress_bar->advance();
                    });
            });
    }

    protected function getRelationshipData($data, $relationship_list)
    {
        foreach ($relationship_list as $relationship_name => $relationship) {
            $data->{$relationship_name} = DB::connection(config('import.import_db_connection'))
                ->table($this->prefix . $relationship['table'])
                ->where($relationship['child_column'], $data->{$relationship['parent_column']})
                ->get()
                ->each(function ($child_relationship) use ($relationship) {
                    if (!empty($relationship['relationships'])) {
                        $this->getRelationshipData($child_relationship, $relationship['relationships']);
                    }
                });
        }
    }
}
