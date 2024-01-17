<?php

namespace App\_oad_repo\Models;

use Carbon\Carbon;
use App\Models\OAD\File;
use Illuminate\Support\Collection;
use App\Exceptions\CustomException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class OADModel extends Model {

    public $form_fields = [];
    public $forms = [
        'main' => [
            'name'      => 'main',
            'primary'   => 'hash'
        ]
    ];

    protected $validated = false;
    protected $form_errors = [];
    protected $guarded = ['hash'];
    protected $storeRes;
    protected $error_msg_prefix = '';
    protected $storeData;
    protected $postSaveEvents = []; //events called after main model been saved
    public $incrementing = false;
    

    public function scopeJoinUser($query,$join_on_field) 
    {
        $this_table = with(new $this)->getTable();
        $query->leftJoin('users' , $this_table . '.' . $join_on_field, '=', 'users.hash');
    }
    
    public function scopeJoinTable($query,$join_table,$using_column = '',$joint_table_column = 'hash') 
    {
        $this_table = with(new $this)->getTable();
        $using_column = $using_column ? $using_column : $join_table . '_hash';
        $query->leftJoin($join_table , $this_table . '.' . $using_column, '=', $join_table.'.'.$joint_table_column );
    }

    public function setPostSaveEvents($event) 
    {
        $this->postSaveEvents = $event;
        return $this;
    }

    public function getFormsModelValues() 
    {
        $forms = [];
        foreach($this->forms as $form) {
            $forms[$form['name']] = $this->getFieldsValues($form['name'],$form['primary']);
        }
        return $forms;
    }

    public function files()
    {
        return $this->morphMany(File::class, 'attachment');
    }
    public function addresses() 
    {
        return $this->morphMany(\App\Models\Address::class, 'assignable');
    }
    public function phone_numbers() 
    {
        return $this->morphMany(\App\Models\Phone::class, 'assignable');
    }
    public function emails() 
    {
        return $this->morphMany(\App\Models\Email::class, 'assignable');
    }
    public function notes_list() 
    {
        return $this->morphMany(\App\Models\OAD\Note::class, 'assignable');
    }
    
    public function getAssignedAddressAttribute() 
    {
        $res = $this->addresses()->where('assignable_field','address')->first();
        return $res ?? [];
    }
    public function getAssignedPhoneAttribute() 
    {
        $res = $this->phone_numbers()->where('assignable_field','phone')->first();
        return $res ?? [];
    }
    public function getAssignedFaxAttribute() 
    {
        $res = $this->phone_numbers()->where('assignable_field','fax')->first();
        return $res ?? [];
    }
    public function getAssignedEmailAttribute() 
    {
        $res = $this->emails()->where('assignable_field','email')->first();
        return $res ?? [];
    }

    public function prepareAssignableSelectors($fields = [],$data = []) 
    {
        $selectors = [];
        foreach ($fields as $field_name)
            $selectors[$field_name] = [ 'hash' => !empty($data[$field_name]['hash']) ? $data[$field_name]['hash'] : '' ];
        return $selectors;
    }

    public static function getTableName()
    {
        return with(new static)->getTable();
    }

    public function getPrimaryKey() 
    {
        return $this->primaryKey;
    }

    public function isEmptyModel() 
    {
        $pKey = $this->primaryKey;
        return empty($this->$pKey);
    }

    public function buildFormFields($fields = []) 
    {
        $arr = [];

        foreach ($fields as $field) $arr[$field['key']] = $field;

        return $arr;
    }

    public function errPrefix($prefix) 
    {
        $this->error_msg_prefix = $prefix;
        return $this;
    }

    public static function findOrMkNew($hash)
    {
        $obj = self::find($hash);
        return $obj ?: new static;
    }

    public static function getFirstOrNew() 
    {
        $obj = self::get()->first();
        return $obj ?: new static;
    }

    public static function findByHash($hash) 
    {
        return self::where('hash',$hash)->first();
    }

    public function getFieldsValues($form_name = 'main', $primaryKey = 'hash', $PKValue = false) 
    {
        $return         = [];
        $fields_data    = $this->getFieldsData($form_name);
        $model          = $PKValue ? $this->where($primaryKey,$PKValue)->first() : $this;
        
        foreach ($fields_data  as $field) {

            $objKey = $field['db_name'];
            
            if ($model->$primaryKey) { //existing record
                if ($field['db_retrieve']) {
                    switch ($field['type']) {
                        case "file":
                            $files_arr = [];
                            for ($i = 0; $i < $model->files->count(); $i ++) {
                                if ($model->files[$i]->is_saved && $model->files[$i]->attachment_field == $objKey) {
                                    $files_arr[] = [
                                        'hash'      => $model->files[$i]->hash,
                                        'name'      => $model->files[$i]->file_name,
                                        'user_name' => $model->files[$i]->user->name,
                                        'date'      => Carbon::parse( $model->files[$i]->created_at )->format('d M, Y')
    
                                    ];
                                }
                            }
                            $return[$objKey]= $files_arr;  
                        break;
                        case "address":
                        case "phone":
                        case "email":
                        case "fax":
                            $attr = 'assigned_' . $field['type'];
                            $return[$objKey] = $model->$attr;
                        break;
                        default:
                            $return[$objKey] = $field['assignVal'] ? $model->$objKey : '';
                    }
                }

            } else { //new record
                $return[$objKey] = $field['dValue']!=='' ? $field['dValue'] : '';
            }
        }

        return $return;
    }

    public function getFieldsData(string $form_name = 'main') : array
    {
        $this->buildFields($form_name);
        return $this->form_fields[$form_name];
    }

    public function getFieldsByType(string $type,string $form_name = 'main') : Collection 
    {    
        return collect($this->getFieldsData($form_name))
                            ->filter(fn($field) => $field['type'] == $type );
    }

    public function getFieldsNames(string $form_name = 'main') : array
    {
        $fields = $this->getFieldsData($form_name);
        return array_keys($fields);
    }

    public function validateAllForms($forms_data) 
    {
        $last_key = array_key_last($this->forms);

        foreach ($this->forms as $key => $form) {
            $this->validateForm(
                $forms_data[$form['name']]['values'] ?? [],
                $form['name'],
                $key == $last_key //only return response on the last form
            );
        }
        return $this;
    }

    public function validateForm( $data, $form_name = 'main', $return_response = true, $rules = [], $attributes = [], $messages = []) 
    {
        $fields_data = $this->getFieldsData($form_name);

        $model_rules = [];
  
        foreach ($fields_data as $field) {
            if ($field['required']) {
                $model_rules[$field['name']] = $field['required'];
            }
        }

        $rules = array_merge($model_rules,$rules);
        $validator = Validator::make($data, $rules);

        $model_attributes = [];
        foreach ($fields_data as $field) 
        {
            $model_attributes[$field['name']] = $field['label'] ? $field['label'] : $field['placeholder']; //placeholder for checkboxes
        }
        $attributes = array_merge($model_attributes,$attributes);
        $validator->setAttributeNames($attributes);
        
        $validator->setCustomMessages(count($messages) ? $messages : ['required' => $this->error_msg_prefix . ':attribute is required']);

        $this->validated = !$validator->fails();

        if (!$this->validated) 
        {
            $this->form_errors = array_merge($this->form_errors,$validator->errors()->all());
        }

        if (count($this->form_errors) && $return_response) 
        {
            if (app()->runningInConsole())
            {
                throw new CustomException(implode(PHP_EOL,$this->form_errors));
            }
            else
            {
                abort(
                    response()->json([ 'status' => 'error', 'res' => implode('<br>',$this->form_errors) ], 200)
                );
            }
        }

        return $this;
    }

    public function validateWithClass()
    {
        
    }

    public function saveAllForms($forms_selector_values = [], $data = []) 
    {
        foreach ($this->forms as $form_name => $form) {
            $primaryKey     = $this->forms[$form_name]['primary'];
            //so we can pass custom primary key values to the function, 
            //if there no value is passed through this variable we are going to look for the value inside the form
            $primaryValue   = array_key_exists($form_name,$forms_selector_values) ? $forms_selector_values[$form_name] : $data[$form_name]['values'][$primaryKey];
            $msg = $form_name == 'main' ? 'Saved' : false;
            $this->store( [ $primaryKey => $primaryValue ] , $form, $msg, $form_name );
        }
    }

    public function store($selector, $data, $successMsg = 'Saved', $form_name = 'main') 
    {
     
        $primaryKey     = $this->primaryKey;
        $fields_data    = $this->getFieldsData($form_name);
        
        //do not store empty fields that are marked
        foreach ($fields_data as $key => $field) 
        {
            if ($field['storeEmpty'] == false && empty($data[$key])) {
                unset($data[$key]);
            }
        }
        $this->storeData = $data;
        
        try 
        {
            $this->storeRes = $this->updateOrCreate($selector, $data);

            foreach ($this->postSaveEvents as $event) {
                event(new $event($this->storeRes,$this->storeData,$form_name ) );
            }                
        } 
        catch (Exception $e) 
        {
            abort(
                response()->json([ 'status' => 'error', 'res' => $e->getMessage() ], 200)
            );
        }
        
        if ($successMsg) 
        {
            abort(       
                response()->json([ 
                    'status' => 'success',
                    'obj'       => $this->storeRes,
                    'hash'      => $this->storeRes->$primaryKey, 
                    'res'       => $successMsg ], 
                200)
            );
        } 
        else 
        {
            return $this->storeRes;
        }

    }

    public function toggleColumn($column) 
    {
        $this->update([
            $column => \DB::raw('NOT ' . $column)
        ]);
        return $this;
    }

}
