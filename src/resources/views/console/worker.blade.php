
namespace App\Workers{{ $folder }};

use App\Helpers\TableHelper;
use Illuminate\Database\Eloquent\Model;
use App\_oad_repo\Workers\CRUDWorker;

class {{ $worker_name }} extends CRUDWorker
{
    public static function genTableData(object $data, Model $model): array
    {
        return (new TableHelper($model))
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
            ->runQuery()
            ->replaceFields([
                'actions' => [ //action buttons
                        [
                            'action' => 'edit',
                            'text'   => 'View'
                        ],
                        [
                            'action'    => 'archive',
                            'text'      => 'Archive'
                        ]
                    ],
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
                'forms'     => [
                    'main'  => [
                        'fields'    => $model->buildFields()->form_fields['main'],
                        'values'    => $modelsNvalues
                    ]   
                ],
                // 'data'      => []
            ];
    }

    // public static function store(Object $data, Model $model, string $form_name = 'main') 
    // {
    //     $model->validateForm($data->forms[$form_name]['values'])
    //            ->store([ 'hash' => $data->hash ], $data->forms[$form_name]['values']);
    //     return true;
    // }

    // public static function destroy(Model $model, string $hash) 
    // {
    //     if ($model::destroy($hash)) {
    //         return ['status' => 'success', 'res' => 'Record deleted'];
    //     }
    //     return ['status' => 'error', 'res' => 'Failed to delete'];
    // }
}
