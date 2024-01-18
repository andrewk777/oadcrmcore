<?php

namespace Oadsoft\Crmcore\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VueRouter extends Model
{
    use HasFactory;

    protected $table = 'vue_router';
    public $timestamps = false;
    protected $guarded = ['id'];

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
