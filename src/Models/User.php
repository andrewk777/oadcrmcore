<?php

namespace App\_oad_repo\Models;

use Exception;
use Field, Uuid;
use App\Models\UserRole;

use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\UserRolePermission;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Validator;

class User extends OADModel implements
    AuthenticatableContract,
    AuthorizableContract,
    CanResetPasswordContract
{
    use HasApiTokens, Notifiable, \Illuminate\Auth\Authenticatable, Authorizable, CanResetPassword, MustVerifyEmail;

    public $form_fields = [];
    public $validated = false;
    public $form_errors = [];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'roles_id','sys_access'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public static function getClassName() 
    {
        return substr(__CLASS__, strrpos(__CLASS__, '\\') + 1);
    }

    public static function get_permissions() {
      
        return UserRolePermission::where('users_roles_id',auth()->user()->roles_id)
                                    ->get()
                                    ->pluck('permission','sections_id')->toArray();

    }

    public function scopeList($query)
    {
        return $query->where('roles_id', '>', 1);
    }

    public function role()
    {
       return $this->belongsTo('App\Models\UserRole', 'roles_id');
    }

    public static function checkAccess($section_slug = '', $allowed_permissions = [], $msg = 'no_permission', $doAbort = true) {
        if (auth()->user()->roles_id > 1) {

            $section = \App\Models\OAD\Section::where('slug', $section_slug )->first();
            
            if (!$section) throw new Exception("Invalid section slug: '" . $section_slug . "'");
            
            $allowed_permissions    = is_string($allowed_permissions) ? [$allowed_permissions] : $allowed_permissions;
            $user_permissions       = self::get_permissions();
            
            if (!array_key_exists($section->id,$user_permissions) || 
                    !in_array($user_permissions[$section->id],$allowed_permissions)) {
                        abort(
                            response()->json([ 'status' => 'error', 'msg' => __('permissions.' . $msg) ], 251)
                        );
                if ($doAbort) {
                    abort(
                        response()->json([ 'status' => 'error', 'msg' => __('permissions.' . $msg) ], 251)
                    );
                } else {
                    return false;
                }
            }
        }
        

        return true;
    }

    public function scopeActive($query)
    {
        return $query->where('sys_access', 1);
    }
    public static function getTableName()
    {
        return with(new static)->getTable();
    }

    public function buildFields($return_form_fields = false) {

        $role_options = UserRole::select('id','name')->where('id', '>', 1)->get()->pluck('name','id');
        $fields = [
            Field::init()->name('name')->label('Name')->required()->toArray(),
            Field::init()->name('email')->label('Email')->required()->toArray(),
            Field::init()->name('password')->type('password')->label('Password')->description( __('passwords.description') )->assignVal(false)->toArray(),
            Field::init()->name('roles_id')->type('select')->label('Access Level')->placeholder('Please select')->options($role_options)->required()->toArray(),
            Field::init()->name('sys_access')->type('echeck')->label(false)->placeholder('System Access')->toArray()
        ];

        $this->form_fields['main'] = $this->buildFormFields($fields);

        return $return_form_fields ? $this->form_fields[$return_form_fields] : $this;
    }

    public function getFieldModelValues($form_name = 'main', $primaryKey = 'hash') {
        $return         = [];
        $fields_data    = $this->buildFields($form_name);
        
        foreach ($fields_data  as $field) {
            
            $objKey = $field['db_name'];
            
            if ($this->$primaryKey) { //existing record

                switch ($field['type']) {
                    case "file":
                        $files_arr = [];
                        for ($i = 0; $i < $this->files->count(); $i ++) {
                            if ($this->files[$i]->is_saved && $this->files[$i]->attachment_field == $objKey) {
                                $files_arr[] = [
                                    'hash'  => $this->files[$i]->hash,
                                    'name'  => $this->files[$i]->file_name
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
                        // dump($field['type']);
                        // dump($attr);
                        $return[$objKey] = $this->$attr;
                        // dump($return[$objKey]);
                    break;
                    default:
                    $return[$objKey] = $field['assignVal'] ? $this->$objKey : '';
                }

            } else { //new record
                $return[$objKey] = $field['dValue']!=='' ? $field['dValue'] : '';
            }
        }

        return $return;
    }

    public static function boot() 
    {
        parent::boot();

        self::saving(function($model) 
        {
            if (!$model->hash) {
				$model->hash = Uuid::generate()->string;
				$model->user_created = app()->runningInConsole() ? '' : auth()->user()->id;
			}
            if (!empty($model->password)) {
                $model->password = bcrypt($model->password);
            } else {
                unset($model->password);
            }
			$model->user_updated = app()->runningInConsole() ? '' : auth()->user()->id;

        });

    }

    public function buildFormFields($fields = []) 
    {
        $arr = [];
        foreach ($fields as $field) {
           $arr[$field['key']] = $field;
        }

        return $arr;
    }

    public function validateForm( $data, $form_name = 'main', $return_response = true, $rules = [], $attributes = [], $messages = []) {

        $this->buildFields();

        $model_rules = [];
        foreach ($this->form_fields['main'] as $field) {
            if ($field['required']) {
                $model_rules[$field['name']] = $field['required'];
            }
        }

        $rules = array_merge($model_rules,$rules);
        $validator = Validator::make($data, $rules);

        $model_attributes = [];
        foreach ($this->form_fields['main'] as $field) {
            $model_attributes[$field['name']] = $field['label'] ? $field['label'] : $field['placeholder']; //placeholder for checkboxes
        }
        $attributes = array_merge($model_attributes,$attributes);
        $validator->setAttributeNames($attributes);

        $validator->setCustomMessages(count($messages) ? $messages : [
            'required' => ':attribute is required',
            'password.min' => ':attribute has to be minimum 8 characters',
            'password.regex' => ':attribute has to be alphanumeric',
            'password.regex' => ':attribute must contain at least one:
            <ul>
                <li>- One digit</li>
                <li>- One lower case letter</li>
                <li>- One upper case letter</li>
            </ul>'
        ]);

        $this->validated = !$validator->fails();

        if (!$this->validated && $return_response) {

            abort(
                response()->json([ 'status' => 'error', 'res' => implode('<br>',$validator->errors()->all()) ], 200)
            );

        }

        return $this;

    }

    public function store($selector, $data, $successMsg = 'Saved', $form_name = 'main') 
    {
        $primaryKey     = $this->primaryKey;
        $fields_data    = $this->getFieldsData($form_name);

        //do not store empty fields that are marked
        foreach ($fields_data as $key => $field) {
            if ($field['storeEmpty'] == false && empty($data[$key])) {
                unset($data[$key]);
            }
        }
        $this->storeData= $data;
        
        try 
        {
            $this->storeRes = $this->updateOrCreate($selector, $data);
        }
        catch (Exception $e) 
        {
            abort(
                response()->json([ 'status' => 'error', 'res' => $e->getMessage() ], 200)
            );
        }

        #attaching files
        foreach ($fields_data as $field_data) 
        {
            switch ($field_data['type']) 
            {
                case "file":
                    $files_hashes   = [];
                    if (is_array($data[$field_data['name']]) && $files_hashes = $data[$field_data['name']]) 
                    {
                        File::whereIn('hash', $files_hashes)->update([
                            'attachment_id'    => $this->storeRes->$primaryKey,
                            'attachment_type'  => get_class($this),
                            'attachment_field' => $field_data['db_name'],
                            'is_saved'         => true
                        ]);
                    }                    
                    
                    //delete removed files
                    File::where([
                        'attachment_id' => $this->storeRes->$primaryKey,
                        'attachment_type' => get_class($this),
                        'attachment_field'  => $field_data['db_name']
                    ])
                    ->whereNotIn('hash', $files_hashes)
                    ->update(['is_saved' => 0 ]);
                break;
            }
        }

        if ($successMsg) 
        {
            abort(
                response()->json([ 
                    'status' => 'success',
                    'obj'       => $this->storeRes,
                    'hash'      => $this->storeRes->hash, 
                    'res'       => $successMsg ], 
                200)
            );
        } 
        else 
        {
            return $this->storeRes;
        }
    }

    public static function isDev()
    {
        return auth()->user()->roles_id == 1;
    }

}
