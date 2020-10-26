<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Support\Collection;


trait Import_address_table
{
 
   
    public function importableRelationships()
    {
        if (!empty($this->importable_relationships)) {
            return $this->importable_relationships;
        }

        return [];
    }
    
	 public function importableColumns($prefix = '')
    {
        if (!empty($this->Import_address_table)) {
            return $this->formatImportFields($this->Import_address_table, $prefix);
        }

        if (!empty($this->fillable)) {
            return $this->formatImportFields($this->fillable, $prefix);
        }

        $columns = $this->getConnection()
            ->getSchemaBuilder()
            ->getColumnListing($this->getTable());

        return $this->formatImportFields(collect($columns)->reject($this->primaryKey), $prefix);
    }

   
     public function getFallbackValueForField($field)
    {
        if (isset($this->Import_address_table_defaults[$field])) {
            return $this->Import_address_table_defaults[$field] === 'NOW' ? Carbon::now() : $this->Import_address_table_defaults[$field];
        }

        return null;
    }
   
    protected function formatImportFields($fields, $prefix = '')
    {
        if (!$fields instanceof Collection) {
            $fields = collect($fields);
        }
        return $fields
            ->keyBy(function ($field_name) use ($prefix) {
                return !empty($prefix) ? $this->getTable() . '.' . $field_name : $field_name;
            })
            ->map(function ($field_name) use ($prefix) {
                return $prefix . ' ' . title_case(str_replace('_', ' ', $field_name));
            });
    }

    
   

}
