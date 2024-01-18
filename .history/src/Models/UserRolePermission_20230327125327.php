<?php

namespace App\_oad_repo\Models;


use App\Models\OAD\Section;

class UserRolePermission extends OADModel
{
	protected $guarded = ['id'];
	public $timestamps = false;
	protected $table = 'users_roles_permissions';

    public function section()
    {
        return $this->belongsTo(Section::class, 'sections_id');
    }
}
