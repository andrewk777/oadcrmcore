<?php

namespace App\_oad_repo\Http\Controllers;

use App\Models\User;
use App\Helpers\TableHelper;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;

class UserController extends Controller
{

    protected $model = 'App\Models\User';

    public function index(Request $request)
    {
        
        User::checkAccess('users',['view','full']);

        return (new TableHelper(new $this->model))
                ->massAssignRequestData($request->params)
                ->setQueryExtension(function($q) {
                    return $q->list()->joinTable('users_roles','roles_id','id');
                })
                ->prepareQuery()
                ->runQuery()
                ->replaceFields([ 
                    'actions' => [ [ 'action' => 'edit', 'text'   => 'Edit'] ]
                ])
                ->getVuetableResponse();
    }

    public function show(Request $request)
    {

        User::checkAccess('users',['view','full']);
  
        $model = $request->hash ? $this->model::where('hash',$request->hash)->first() : new $this->model();
        $modelsNvalues = $model->buildFields()->getFieldModelValues('main','id');

        return response()->json(
            [
                'status'    => 'success',
                'hash'      => $request->hash,
                'forms'    => [
                    'main'  => [
                        'fields'    => $model->form_fields['main'],
                        'values'    => $modelsNvalues
                    ]
                    
                ]
            ], 
            200
        );
    }

    public function store(Request $request) {

        User::checkAccess('users','full');
        
        $model = $request->hash ? $this->model::where('hash',$request->hash)->first() : new $this->model();
        
        $valid_roles = [
                        'email' => [
                                'required',
                                'email',
                                Rule::unique('users')->ignore($model),
                            ],
                        'password' => config('project.password_rules')
                    ];
        if ($request->hash) { 
            // for existing users - can keep field blank
            $valid_roles['password'] = array_merge(config('project.password_rules'),['sometimes','nullable']);
        }
        // dump($model);
        $model->validateForm($request->forms['main']['values'],'main',true,$valid_roles)
              ->store([ 'hash' => $request->hash ], $request->forms['main']['values']);

    }

    public function destroy($hash) {
        return false;
    }

    public function list(Request $request) {
        $model = $this->model::list()->active()->select(['hash','name']);

        //lookup matching pair for value
        if ($request->hash) {
            return $model->whereIn('hash', explode(',', $request->hash))->get()->pluck('name','hash');
        }
        
        //perform search for results
        if ($request->search) {
            $model->where('name','LIKE','%' . $request->search . '%');
        }
        
        return $model->limit(10)->orderBy('name')->get()->pluck('name','hash');
    }

}
