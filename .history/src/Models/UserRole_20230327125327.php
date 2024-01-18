<?php

namespace App\_oad_repo\Models;

use App\Models\UserRolePermission;
use Field, Auth;
use App\Models\OAD\OADModel;


class UserRole extends OADModel
{
	protected $table = 'users_roles';
	protected $primaryKey = 'id';
	protected $guarded = ['id'];
	// protected $fillable = ['items','name','user_created','user_updated'];

	public $form_fields = [];
    public $validated = false;
	public $form_errors = [];

	public function buildFields($return_form_fields = false) {

        $this->form_fields['main'] = $this->buildFormFields([
            Field::init()->name('name')->label('Name')->required()->toArray()
        ]);

        return $return_form_fields ? $this->form_fields[$return_form_fields] : $this;
    }

	public function scopeList($query)
    {
        return $query->where('id', '>', 1);
	}

	public function items() {
		return $this->hasMany('App\Models\UserRolePermission','users_roles_id');
	}
	public function users() {
		return $this->hasMany('App\Models\User','roles_id');
	}

	public function savePermissions($selector,$forms, $successMsg = 'Saved') {

		$role = $this->updateOrCreate($selector,$forms['main']['values']);
		if (!$role->id) $role = $this->where($forms['main']['values'])->first();

		foreach ($forms['tree']['values'] as $item) {
			UserRolePermission::updateOrCreate([
					'users_roles_id'	=> $role->id,
					'sections_id'		=> $item['sections_id']
				],
				[
					'users_roles_id'	=> $role->id,
					'permission' => $item['permission']
				]
			);
		}

		abort(
            response()->json([ 
                'status'        => 'success',              
                'obj'           => $role,
                'hash'          => $role->id, 
                'res'           => $successMsg 
            ], 
            200)
		);
		
	}

	public static function boot() {
		parent::boot();

		self::creating(function($model) {
			
			$model->user_created = app()->runningInConsole() ? '' : Auth::user()->hash;
			$model->user_updated = app()->runningInConsole() ? '' : Auth::user()->hash;
			
		});

		self::updating(function($model) {
			
			$model->user_updated = app()->runningInConsole() ? '' : Auth::user()->hash;
			
		});

		self::deleted(function($model) {
			$model->items()->delete();
		});
		
    }

}
