<?php

namespace App\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Carbon\Carbon;


trait AddressMapsData
{
    
    protected function mapData($field_list, $data, $depth = 0)
    {
        return collect($field_list)
            ->map(function ($requested_field) use ($data, $depth) {

          
                if (is_string($requested_field)) {
                    return $this->findValueInData($requested_field, $data);
                }

                
                if ($this->isLoopedArray($requested_field)) {
                    return $this->mapLoop($requested_field[0], $data);
                }

               
                return $this->mapData($requested_field, $data, ++$depth);
            });
    }

   
    protected function isLoopedArray($requested_field)
    {
        if (is_array($requested_field) && count($requested_field) == 1) {
            return collect($requested_field[0])
                    ->filter(function ($value) {
                        return strpos($value, config('import.loop_operator')) !== false;
                    })
                    ->count() > 0;
        }
        return false;
    }

    
    protected function mapLoop($incoming_field_list, $data)
    {
        
        $incoming_field_list = collect($incoming_field_list);

       
        $first_key_with_loop = $incoming_field_list
            ->filter(function ($value) {
                return strpos($value, config('import.loop_operator')) !== false;
            })
            ->keys()
            ->first();

        
        $loop_data = $this->findValueInData($incoming_field_list[$first_key_with_loop], $data);

        
        if (!$loop_data) {
            return [];
        }

        $identifier_length = strlen(config('import.loop_operator'));

        
        $incoming_field_list = $incoming_field_list
            ->map(function ($field_name) use ($identifier_length) {
                $loop_identifier_position = strpos($field_name, config('import.loop_operator'));
                
                return substr($field_name, $loop_identifier_position + $identifier_length + 1);
            });

       
        return $loop_data
            ->map(function ($data_entry) use ($incoming_field_list) {
               
                return $incoming_field_list
                    ->map(function ($requested_field) use ($data_entry) {
                        return $this->findValueInData($requested_field, $data_entry);
                    });

            });
    }
    protected function findValueInData($requested_field, $data)
    {
        if (strpos($requested_field, '~') === 0) {
            return $this->parseContextFunction($requested_field, $data);
        }

        
        $tree = explode('.', $requested_field);
        $tree_length = count($tree);

        do {
           
            $field_to_access = array_shift($tree);

            
            if ($field_to_access === config('import.loop_operator')) {
                return $data;
            }

            if ($this->isValueFilter($field_to_access)) {
                $data = $this->filterValueCollection($field_to_access, $data);
                continue;
            }

            
            if (is_numeric($field_to_access) and $data instanceof Collection) {
                if (empty($data[$field_to_access])) {
                    return null;
                }
                $data = $data[$field_to_access];
                continue;
            } elseif ($data instanceof Collection && $data->count() == 1) {
                
                $data = $data->first();
            }

            if ($data && property_exists($data, $field_to_access)) {
                $data = $data->{$field_to_access};
            } elseif ($tree_length > 1) {
               
                $data = null;
            } elseif (defined($field_to_access)) {
                $data = constant($field_to_access);
            } else {
                $data = $field_to_access;
            }
        } while (!empty($tree));

        
        if (is_object($data) && !(array)$data) {
            return null;
        }

        return is_string($data) ? trim($data) : $data;
    }

   
    protected function parseContextFunction($requested_method, $data)
    {
        if (strpos($requested_method, '~compare') !== false) {
            $compare_args = explode(',', trim(str_replace('~compare', '', $requested_method), '()'));
            return $this->compare($data, ...$compare_args);
        }

        if (strpos($requested_method, '~int') !== false) {
            $value_to_process = trim(str_replace('~int', '', $requested_method), '()');
            return (int)$this->findValueInData($data, $value_to_process);
        }

        if (strpos($requested_method, '~float') !== false) {
            $value_to_process = trim(str_replace('~float', '', $requested_method), '()');
            return (float)$this->findValueInData($data, $value_to_process);
        }

        if (strpos($requested_method, '~now') !== false) {
            $date_format = trim(str_replace('~now', '', $requested_method), '()');
            if (empty($date_format)) {
                $date_format = Carbon::DEFAULT_TO_STRING_FORMAT;
            }
            return Carbon::now()->format($date_format);
        }

        if (strpos($requested_method, '~concat') !== false) {
            $concatenate_values = explode(',', trim(str_replace('~concat', '', $requested_method), '()'));
            return collect($concatenate_values)
                ->map(function ($value) use ($data) {
                    return $this->findValueInData($value, $data);
                })
                ->implode('');

        }

        if (strpos($requested_method, '~external') !== false) {
            $callable_parts = explode(',', trim(str_replace('~external', '', $requested_method), '()'));

            
            $callable = [
                array_shift($callable_parts),
                array_shift($callable_parts),
            ];
            $callable_parts['data'] = $data;
            return App::call($callable, $callable_parts);
        }

        
        return $requested_method;
    }

    
    protected function compare($data, $field, $compare_value, $compare_operator = '==', $true_value = false, $false_value = false)
    {
        $comparison_is_true = false;
        $field_value = $this->findValueInData($field, $data);

        switch ($compare_operator) {
            case '==':
                $comparison_is_true = $field_value == $compare_value;
                break;
            case '>':
                $comparison_is_true = $field_value > $compare_value;
                break;
            case '<':
                $comparison_is_true = $field_value < $compare_value;
                break;
            case '<=':
                $comparison_is_true = $field_value <= $compare_value;
                break;
            case '>=':
                $comparison_is_true = $field_value >= $compare_value;
                break;
            case '!=':
                $comparison_is_true = $field_value != $compare_value;
                break;
        }

        return $comparison_is_true ? $this->findValueInData($true_value, $data) : $this->findValueInData($false_value, $data);
    }

   
    protected function isValueFilter($requested_field)
    {
        return strpos($requested_field, '(') === 0
            && substr($requested_field, -1) === ')';
    }

   
    protected function filterValueCollection($filter, $data)
    {
        if (!$data instanceof Collection) {
            $data = collect($data);
        }

        list($filter_column, $filter_value) = explode(',', trim($filter, '()'));
        return $data->where($filter_column, $filter_value);
    }
}
