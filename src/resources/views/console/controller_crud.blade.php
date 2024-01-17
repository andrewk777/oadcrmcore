
namespace App\Http\Controllers{{ $folder }};

use Illuminate\Http\Request;
use App\Workers\{{$worker_name}};
use App\Models\{{$model_name}};

class {{ $controller_name }} extends Controller
{

    public function index(Request $request)
    {
        return response()->json(
            {{$worker_name}}::genTableData(
                $request,
                new {{$model_name}}()
            )
        );
    }


    public function show(Request $request)
    {
        return response()->json(
            {{$worker_name}}::getShowData(
                $request,
                new {{$model_name}}()
            )
        );
    }

    public function store(Request $request) 
    {
        return response()->json(
            {{$worker_name}}::store(
                $request,
                new {{$model_name}}()
            )
        );
    }

    public function destroy($hash)
    {
        return response()->json(
            {{$worker_name}}::destroy(
                new {{$model_name}}(),
                $hash
            )
        );
    }

}
