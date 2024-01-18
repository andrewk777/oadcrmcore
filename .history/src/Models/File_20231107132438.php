<?php

namespace App\_oad_repo\Models;

use Uuid;
use Illuminate\Support\Facades\File as LaravelFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File as FileFacade;

class File extends Model {

    protected $table = 'files';
    protected $guarded = ['hash'];
    protected $primaryKey = 'hash';
    public $incrementing = false;

    public static function boot() {
        parent::boot();

        self::creating(function($model) 
        {
            $model->hash            = Uuid::generate()->string;
            $model->user_updated    = auth()->user() ? auth()->user()->id : 1;
            $model->user_created    = auth()->user() ? auth()->user()->id : 1;
            $model->is_saved        = $model->is_saved === true ? true : false;
        });
        // });

        self::updating(function($model) 
        {
            $model->user_updated    = auth()->user() ? auth()->user()->id : 1;
        });

        self::deleting(function($model) 
        {
            if (file_exists(storage_path($model->path)))
                unlink(storage_path($model->path));
        });
    }

    public function attachment() {
        return $this->morphTo();
    }

    public function user() {
        return $this->belongsTo(User::class,'user_created');
    }

    public static function randName() {
        return time() . '_' . \Illuminate\Support\Str::random(40);
    }

    public static function del(string $hash) {
        $file = self::find($hash);
        unlink(storage_path($file->path));
        $file->delete();
    }

    public static function softDel(string $hash) {
        $file = self::find($hash);
        $file->is_saved = 0;
        $file->save();
    }

    public function scopeIsSaved($q) {
        $q->where('is_saved',1);
    }

    public static function store(string $file_path, string $dest_path = '', array $fileInfo = [], string $action = 'move') 
    {           
        $model             = new self;
        
        $model->file_name  = !empty($fileInfo['file_name']) ? $fileInfo['file_name'] : basename($file_path);
        $model->ext        = !empty($fileInfo['ext']) ? $fileInfo['ext'] : FileFacade::extension($file_path);
        $model->size       = !empty($fileInfo['size']) ? $fileInfo['size'] : FileFacade::size($file_path);
        $model->mime       = !empty($fileInfo['mime']) ? $fileInfo['mime'] : FileFacade::mimeType($file_path);
        $model->is_saved   = array_key_exists('is_saved',$fileInfo) ? $fileInfo['is_saved'] : false;
        $dest_path         = $dest_path ? $dest_path : config('project.storageTree.tmp') . self::randName() . '.' . $model->ext;  
        $model->path       = $dest_path;

        if ($action == 'move') 
        {
            rename($file_path, storage_path( $dest_path ));
        } 
        else 
        {
            copy($file_path, storage_path( $dest_path ));
        }

        $model->save();

        return $model;
    }

    /**
     * recordFileToDB
     * takes information of a physical file and creates a record in the db  
     *
     * @param  mixed $path - has to be an abs path
     * @return void
     */
    public static function recordFileToDB(string $path, string $custom_file_name = '', string $class = '', string $field_name = '', string $record_id = '', bool $is_saved = true)
    {
        $model                  = new self();

        $model->file_name       = $custom_file_name ?? FileFacade::name($path);
        $model->ext             = FileFacade::extension($path);
        $model->size            = FileFacade::size($path);
        $model->mime            = FileFacade::mimeType($path);
        $model->is_saved        = $is_saved;
        $model->path            = str_replace(storage_path().'/','',$path);
        $model->attachment_type = $class;
        $model->attachment_field= $field_name;
        $model->attachment_id   = $record_id;
        $model->save();

        return $model;
    }
        
    /**
     * works from existing file store in the system. 
     * copies physical file and creates a db record of that file as newly uploaded (not saved/attached)
     *
     * @param  mixed $model
     * @param  mixed $storage_dir
     * @param  mixed $file_name
     * @return void
     */
    public static function copySavedFileAsUpload(Model $model, string $storage_dir = '', string $file_name = '') 
    {    
        $file_name      = $file_name ? $file_name . '.' . $model->ext : $model->file_name;
        $storage_dir    = $storage_dir ?? dirname($model->path);
        $dest_path      = storage_path($storage_dir . '/' . self::randName() . '.' . $model->ext );
        
        LaravelFile::copy(storage_path($model->path),$dest_path);

        return self::recordFileToDB($dest_path,$file_name);
    }

}
