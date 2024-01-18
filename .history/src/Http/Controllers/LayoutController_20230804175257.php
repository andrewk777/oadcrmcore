<?php

namespace App\_oad_repo\Http\Controllers;

use App\Models\OAD\Section;
use App\Http\Controllers\Controller;

class LayoutController extends Controller
{

    protected $check_permissions;
    protected $user_type;

    public function full_menu($check_permissions = true, $include_secions = false)
    {
        $this->check_permissions    = auth()->user()->roles_id == 1 ? false : $check_permissions;
        $this->user_type            = auth()->user()->user_type;

        return response()->json([
            'menu_primary'      => $this->primary_menu(),
            'menu_secondary'    => $this->secondary_menu()
        ]);
    }

    public function set_permission_filter($val = true)
    {
        $this->check_permissions = $val;
        return $this;
    }

    public function primary_menu()
    {
        return $this->permission_filter(
            Section::with('routes')
                    ->whereNull('parent_id')
                    ->whereHas('user_types',function($q_user_types) {
                        $q_user_types->where('user_type',$this->user_type);
                    })
                    ->where('type','menu')
                    ->orderBY('sort_order')
                    ->get()
        );

    }

    public function secondary_menu()
    {
        $sections = $this->permission_filter(
            Section::with('routes')
                    ->whereNotNull('parent_id')
                    ->whereHas('user_types',function($q_user_types) {
                        $q_user_types->where('user_type',$this->user_type);
                    })
                    ->where('type','menu')
                    ->orderBY('parent_id')
                    ->orderBY('sort_order')
                    ->get()
        );

        return $sections->groupBy('parent_id');
    }

    protected function permission_filter($sections)
    {
        if ($this->check_permissions) {
            $permissions = \User::get_permissions();

            $sections = $sections->filter(function($record) use ($permissions) {
                return !empty($permissions[$record->id]) && $permissions[$record->id] != 'none';
            });

        }
        return $sections;
    }

    public function sections_tree($parent_id = null, $user_type = null)
    {
        $items = Section::where('parent_id',$parent_id)
                        ->when($user_type,function($q) use ($user_type) {
                            $q->whereHas('user_types',function($q_user_types) use ($user_type) {
                                if ($user_type)
                                    $q_user_types->where('user_type',$user_type);
                            });
                        })
                        ->orderBy('sort_order')->get();
        $permissions = \User::get_permissions();

        if ($items->count()) {

            return $items->transform(function($item) use ($permissions,$user_type) {

                if (!$this->check_permissions || (!empty($permissions[$item->id]) && $permissions[$item->id] != 'none' )) {

                    $item->access_options   = collect(explode(',',$item->access_options))->mapWithKeys(function($item) {
                        return [$item => ucfirst($item) ];
                    });

                    $item->children         = $this->sections_tree($item->id,$user_type);

                    return $item;

                }

            });
        }

        return false;
    }

}
