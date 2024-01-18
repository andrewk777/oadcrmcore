<?php
namespace App\_oad_repo\Engine;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class FormHelper {

    //genereate field data from a form
    protected $key = 'hash';
    protected $fields = [];
    protected $validation_rules = [];
    protected $validated = false;
    protected $data_submitted = [];
    protected $model = null;

    public function __construct($model = null)
    {
        if ($model) $this->model = $model;
    }

    public function setModel(Model $model) 
    {
        $this->model = $model;
        return $this;
    }

    public function setFields(array $fields_arr, bool $reset_fields = false)
    {
        if ($reset_fields) $this->fields = [];

        foreach ($fields_arr as $field)  
            $this->fields[$field->getKey()] = $field->toArray();
            
        return $this;
    }

    public function setKey(string $key) 
    {
        return $this->key = $key;
    }

    public function getKey() : string
    {
        return $this->key;
    }

    public function getFieldsStructure() : array
    {
        return $this->fields;
    }

    public function getFieldsValues() : array
    {
        $values = [];
        
        foreach ($this->fields as $field) {

            $objKey = $field['db_name'];
            
            if ($this->model->{$this->key}) { //existing record

                switch ($field['type']) {
                    case "file":
                        $files_arr = [];
                        for ($i = 0; $i < $this->model->files->count(); $i ++) {
                            if ($this->model->files[$i]->is_saved && $this->model->files[$i]->attachment_field == $objKey) {
                                $files_arr[] = [
                                    'hash'      => $this->model->files[$i]->hash,
                                    'name'      => $this->model->files[$i]->file_name,
                                    'user_name' => $this->model->files[$i]->user->name,
                                    'date'      => Carbon::parse( $this->model->files[$i]->created_at )->format('d M, Y')

                                ];
                            }
                        }
                        $values[$objKey]= $files_arr;  
                    break;
                    case "address":
                    case "phone":
                    case "email":
                    case "fax":
                        $attr = 'assigned_' . $field['type'];
                        $values[$objKey] = $this->model->$attr;
                    break;
                    default:
                        $values[$objKey] = $field['assignVal'] ? $this->model->$objKey : '';
                }

            } else { //new record
                $values[$objKey] = $field['dValue']!=='' ? $field['dValue'] : '';
            }
        }

        return $values;
    }
}