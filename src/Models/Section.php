<?php

namespace App\_oad_repo\Models;

use App\Models\SectionUserType;

class Section extends OADModel
{
    protected $table = 'sections';
    protected $guarded = ['id'];
    public $incrementing = true;
    protected $hidden = ['parent_id','slug','vue_router_id'];
    public $timestamps = false;

	public function routes() {
        return $this->belongsTo('App\Models\OAD\Router', 'vue_router_id');
    }

    public function user_types() {
        return $this->hasMany(SectionUserType::class,'sections_id');
    }

    public function scopeIsMenu($q)
    {
        return $q->where('type','menu');
    }

    public function scopeIsParent($q)
    {
        return $q->whereNull('parent_id');
    }

    public function scopeSortList($q)
    {   
        return $q->orderBy('sort_order');
    }

    public function scopeHasParent($q,int $aprent_id)
    {   
        return $q->where('parent_id',$aprent_id);
    }

}
