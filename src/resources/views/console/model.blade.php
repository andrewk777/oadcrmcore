
namespace App\Models{{ $name_space }};

use Uuid;
use App\Models\OAD\Field;
use App\Models\OAD\OADModel;

class {{ $model_name }} extends OADModel
{
	protected $table = '{{ $table_name }}';
	protected $guarded = ['hash'];
	protected $primaryKey = 'hash';
    public $incrementing = false;

	public $form_fields = [];

	public function buildFields($return_form_fields = false) {

		/* $this->form_fields['main'] = $this->buildFormFields([
             Field::init()->name('first_name')->label('First Name')->toArray(),
         ]);*/

        return $return_form_fields ? $this->form_fields[$return_form_fields] : $this;
    }

	public function scopeList($query)
    {
        return $query;
    }

    public function scopeExportList($query) {
		return $query->select('hash');
	}

	public static function boot() {
        parent::boot();

        self::creating(function($model) {

			$model->hash = Uuid::generate()->string;

        });

    }


}
