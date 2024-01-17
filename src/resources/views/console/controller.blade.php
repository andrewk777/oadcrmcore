
namespace App\Http\Controllers{{ $folder }};

use Illuminate\Http\Request;
use App\_oad_repo\Workers\CRUDWorker;
use App\Models\{{$model_name}};

class {{ $controller_name }} extends Controller
{

    public function index(Request $request)
    {
        return response()->json(
            CRUDWorker::genTableData(
                $request,
                new {{$model_name}}()
            )
        );
    }


    public function show(Request $request)
    {
        return response()->json(
            CRUDWorker::getShowData(
                $request,
                new {{$model_name}}()
            )
        );
    }

    public function store(Request $request) 
    {
        return response()->json(
            CRUDWorker::store(
                $request,
                new {{$model_name}}()
            )
        );
    }

    public function destroy($hash)
    {
        return response()->json(
            CRUDWorker::destroy(
                new {{$model_name}}(),
                $hash
            )
        );
    }

}
