<?php

namespace App\Traits;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Collection;

trait AddressDatabase
{
   
    protected function populateData($data)
    {
        $import_object = $this->setDataOnObject($this->model, $data);
        $import_object->save();

        collect($import_object->importableRelationships())
            ->each(function ($relationship, $relationship_name) use ($data, $import_object) {

                
                if ($this->shouldSkipPopulatingRelationship($data, $relationship, $relationship_name)) {
                    return;
                }

                $this->ensureRelationshipDataCollection($data[$relationship_name])
                    ->each(function ($collection, $relationship_data) use ($relationship, $import_object, $relationship_name) {
                        $related_object = $this->setDataOnObject($relationship['model'], $relationship_data);
                        $import_object->{$relationship_name}()->save($related_object);
                    });
            });
    }

   
    protected function setDataOnObject($model_name, $data)
    {
        $object = App::make($model_name);
        $object->id = $data['id'] ?? null;
        $object->importableColumns()
            ->each(function ($column_name, $column) use ($data, $object) {
               
                $fallback_value = $object->getFallbackValueForField($column);

                if (isset($data[$column])) {
                    $object->{$column} = $data[$column];
                } elseif ($fallback_value !== null) {
                    $object->{$column} = $fallback_value;
                }

            });

        return $object;
    }

   
    protected function shouldSkipPopulatingRelationship($data, $relationship, $relationship_name)
    {
        
        if (empty($data[$relationship_name]) || empty($relationship['model'])) {
            return true;
        }

        $relationship_data_collection = $data[$relationship_name];
        return $relationship_data_collection instanceof Collection && $relationship_data_collection->isEmpty();
    }

   
    protected function ensureRelationshipDataCollection($data)
    {
        return ($data instanceof Collection) ? $data : collect([$data]);
    }
}
