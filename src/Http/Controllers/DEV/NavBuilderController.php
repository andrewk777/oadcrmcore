<?php

namespace App\_oad_repo\Http\Controllers\DEV;

use Exception;
use App\Models\OAD\VueRouter;
use App\Models\OAD\Router;
use App\Models\OAD\Section;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class NavBuilderController extends Controller 
{

    public function getSectionsTree(Request $request)
    {
        if (!auth()->user()->isDev()) throw new Exception("Access Denied");

        $tree = $this->getSectionChildren(null);

        return response()->json( $tree->values() );
    }

    private function getSectionChildren($parent_id)
    {
        $items = Section::with('routes')->where('parent_id',$parent_id)->isMenu()->sortList()->get()->makeVisible(['parent_id','slug','vue_router_id']);
        if ($items)
        {
            foreach ($items as $item)
            {
                $item['children'] = $this->getSectionChildren($item->id);
            }
        }
        return $items->values();
    }

    public function getRoutesTree(Request $request)
    {
        if (!auth()->user()->isDev()) throw new Exception("Access Denied");

        $tree = $this->getRouteChildren(null);

        return response()->json( $tree->values() );
    }

    private function getRouteChildren($parent_id)
    {
        $items = VueRouter::where('parent_id',$parent_id)->sortList()->get();
        if ($items)
        {
            foreach ($items as $item)
            {
                $item['children'] = $this->getRouteChildren($item->id);
            }
        }
        return $items->values();
    }

    public function storeSectionsTree(Request $request)
    {
        if ($request->type == 'section')
        {
            $this->updateTreeItemOrder($request->tree,null,new Section());
        } 
        else 
        {
            $this->updateRouterTree($request->tree,null,new VueRouter());
        }
        
        return response()->json( [
            'status'    => 'success',
        ]);
    }

    private function updateTreeItemOrder($items,$parent_id,$model)
    {
        foreach ($items as $order => $item)
        {
            $model = $model->find($item['id']);
            $model->update([ 
                        'parent_id'  => $parent_id,
                        'sort_order' => ($order + 1) 
                    ]);
            $this->updateTreeItemOrder($item['children'],$model->id,$model);
        }
    }

    private function updateRouterTree($items,$parent_id,$model)
    {
        foreach ($items as $order => $item)
        {
            VueRouter::updateOrCreate(['id' => $item['id']],
                [
                        'name' =>          $item['name'],
                        'metaTitle' =>     $item['metaTitle'],
                        'path' =>          $item['path'],
                        'componentPath' => $item['componentPath'],
                        'parent_id'  =>    $parent_id,
                        'sort_order' =>    ($order + 1) 
                ]);
            $this->updateRouterTree($item['children'],$item['id'],$model);
        }
    }

    public function listRoutes(Request $request)
    {
        return response()->json( 
            Router::query()
                        ->when($request->search,function($q) use ($request) {
                            $q->where('name','LIKE','%'.$request->search.'%');
                        })
                        ->get()
                        ->transform(function($item){
                        return 
                        [
                            'id'    => $item->id,
                            'path'  => $item->id . ': (' . $item->name. ') ' . $item->path
                        ];
                    })->pluck('path','id')
                );
    }

    //save section information
    public function storeSection(Request $request)
    {
        $section = Section::updateOrCreate(['id' => $request->id > 0 ?? null ],
        [
            'text'              => $request->text,
            'vue_router_id'     => $request->vue_router_id,
            'cssClass'          => $request->cssClass,
            'access_options'    => $request->access_options,
            'slug'              => $request->slug,
            'type'              => 'menu'
        ]);
        return response()->json( [
            'status'    => 'success',
            'data'      => Section::where('id',$section->id)->with('routes')->get()->makeVisible(['parent_id','slug','vue_router_id'])->first()
        ]);
    } 

    public function updateSeeds()
    {
        \Artisan::call('iseed', ['tables' => 'sections,vue_router', '--force' => true]);
        return response()->json( [
            'status'    => 'success'
        ]);
    }

    public function generatevuejs()
    {
        \Artisan::call('oad genVueRoutes');
        return response()->json( [
            'status'    => 'success'
        ]);
    }

    public function delete(Request $request)
    {
        $model = $request->type == 'section' ? new Section() : new VueRouter();
        $model::destroy($request->id);
        return response()->json( [
            'status'    => 'success'
        ]);
    }

}

