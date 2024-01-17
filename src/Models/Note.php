<?php

namespace App\_oad_repo\Models;

use Webpatser\Uuid\Uuid;
use App\Models\OAD\OADModel;
use Illuminate\Support\Facades\Auth;

class Note extends OADModel
{
	protected $table = 'notes';
	protected $guarded = ['hash'];
	protected $primaryKey = 'hash';
    public $incrementing = false;
    protected $hidden = ['assignable_id', 'assignable_type', 'assignable_field', 'assignable_type_slug','user','user_created','updated_at'];
    protected $appends = ['user_name','html_text'];

    public function user() {
        return $this->belongsTo(\App\Models\User::class,'user_created','hash');
    }

    public function getUserNameAttribute() {        
        return $this->user->name;
    }
    
    public function getHtmlTextAttribute() {        
        return nl2br($this->text);
    }

	public static function boot() {
        parent::boot();

        self::creating(function($model) {
            if (empty($model->text)) return false;
			$model->hash                = Uuid::generate()->string;
            if (auth()->user()) {
                $model->user_created        = app()->runningInConsole() ? null : Auth::user()->hash;
            }

        });

        self::created(function($model) {
            
            $model_path = explode('\\',$model->assignable_type);
            
            $note = self::find($model->hash);
            $note->assignable_type_slug = strtolower(end( $model_path ));
            $note->save();

        });
        
    }


}
