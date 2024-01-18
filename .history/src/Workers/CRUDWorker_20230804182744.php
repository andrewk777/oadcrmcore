<?php
namespace App\_oad_repo\Workers;

use App\Helpers\TableHelper;
use Illuminate\Database\Eloquent\Model;

class CRUDWorker {
    
    public static function genTableData(Object $data, Model $model) : array
    {

        return (new TableHelper(new $model))
                ->massAssignRequestData($data->params)
                // ->setCustomFilter('approved_for_processing', function ($q, $approved_for_processing) {
                //     if ($approved_for_processing) {
                //        return $q->where('approved_for_processing', false);
                //     }
                // })
                // ->setQueryExtension(function ($q) {
                //     return $q->joinTable('clients', 'clients_hash')
                //         ->selectClientName()->userFilter();
                // })
                // ->setCustomFieldSearch('clients_name', function ($q, $searchTerm) {
                //     return $q->orWhereRaw(" CONCAT_WS(' ', clients.first_name, clients.last_name) LIKE ?", ['%' . $searchTerm . '%']);
                // })
                ->prepareQuery()
                // ->ddQuery()
                ->runQuery()
                ->replaceFields([ 
                    'actions' => [ //action buttons
                        [
                            'action' => 'edit',
                            'text'   => 'View'
                        ],
                        [
                            'action'    => 'delete',
                            'text'      => 'Delete'
                        ]
                    ],
                    // 'lawfirm' => function($model) {
                    //     return User::isEmployee() ? $model->lawfirm : '';
                    // }
                ])
                ->getVuetableResponse(false);
    }

    public static function getShowData(Object $data, Model $model) : array
    {
        $model          = $model::findOrMkNew($data->hash);
        $modelsNvalues  = $model->getFieldsValues();

        return 
            [
                'status'    => 'success',
                'hash'      => $data->hash,
                'forms'    => [
                    'main'  => [
                        'fields'    => $model->form_fields['main'],
                        'values'    => $modelsNvalues
                    ]
                ]
            ];
    }

    public static function store(Object $data, Model $model, string $form_name = 'main') 
    {
        $model->validateForm($data->forms[$form_name]['values'])
               ->store([ 'hash' => $data->hash ], $data->forms[$form_name]['values']);
        return true;
    }

    public static function destroy(Model $model, string $hash) 
    {
        if ($model::destroy($hash)) {
            return ['status' => 'success', 'res' => 'Record deleted'];
        }
        return ['status' => 'error', 'res' => 'Failed to delete'];
    }

    public static function list(Object $data, Model $model, string $search_by = 'name')
    {
        //lookup matching pair for value
        if ($data->hash) {
            return $model->where('hash',$data->hash)->get()->pluck($search_by,'hash');
        }

        //perform search for results
        if ($data->search) {
            return $model->where($search_by,'LIKE','%' . $data->search . '%')->limit(10)->orderBy($search_by)->get()->pluck($search_by,'hash');
        }
        return $model->limit(10)->orderBy($search_by)->get()->pluck($search_by,'hash');
    }
    
}