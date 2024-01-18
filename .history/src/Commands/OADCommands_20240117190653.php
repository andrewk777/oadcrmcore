<?php

namespace Oadsoft\Crmcore\Commands;

use Carbon\Carbon;
use App\Models\OAD\File;
use App\Models\OAD\VueRouter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class OADCommands extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'oad {oad_command}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'OAD Dev Console Commands';

    protected $path = false;
    protected $apiKey = false;
    protected $fileName = false;
    protected $storageFolders = [];
    protected $commands = [
        'seed'              => 'Seeds DB',
        'reseed'            => 'Drops All tables & Seeds DB, but keeps the sessions live',
        'delAllStorageFolders' => 'Delets All Folders in the storage',
        'dropTables'        => 'Drops ALL tables on ALL connections',
        'genEnv'            => 'Generate Env File from oad-conf.json',
        'genVueRoutes'      => 'Generates Routes.js file from db data',
        'mkController'      => 'Create Controller from predefined template',
        'mkModel'           => 'Create Model from predefined template',
        'mkVueResource'     => 'Create vue index and form templates as a resource',
        'mkVueIndex'        => 'Create Index vue file from a predefined template',
        'mkVueForm'         => 'Create Form vue file from a predefined template',
        'mkMigration'       => 'Create migration',
        'mkCrud'            => 'Creates a set of files for full CRUD including: migration, model, controller, vueIndex & vueForm',
        'iseedNav'          => 'generate seeds for sections,vue_router tables',
        'iseedRoles'        => 'generate seeds for users_roles,users_roles_permissions tables',
        'mkWorker'          => 'Generate worker from predefined template',
        'storagePerm'       => 'Set storage app folder ownership to www-data',
        'genStorageTree'    => 'Generate storage folder tree',
    ];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->storageFolders = config('project.storageTree');
        if (!$this->storageFolders)
        {
            $this->error('storageTree config not found');
        }
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->setViewPath();
        $this->routeCommand();
    }

    protected function setViewPath()
    {
        View::getFinder()->setPaths([app_path('_oad_repo/resources/views')]);
    }

    protected function routeCommand()
    {
        $command = $this->argument('oad_command');
        switch ($command) {
            case 'help':
                $this->showHelp();
                break;
            case 'seed':
                $this->seed();
                break;
            case 'reseed':
                $this->reseed();
                break;
            case 'dropTables':
                $this->dropTables();
                break;
            case 'genEnv':
                $this->genenv();
                break;
            case 'genVueRoutes':
                $this->genVueRoutes();
                break;
            case 'mkController':
                $this->mkController();
                break;
            case 'mkModel':
                $this->mkModel();
                break;
            case 'mkVueResource':
                $this->mkVueIndex();
                $this->mkVueForm();
                break;
            case 'mkVueIndex':
                $this->mkVueIndex();
                break;
            case 'mkVueForm':
                $this->mkVueForm();
                break;
            case 'mkMigration':
                $this->mkMigration();
                break;
            case 'mkCrud':
                $this->mkCrud();
//                $this->mkMigration();
//                $this->mkModel();
//                $this->mkController();
//                $this->mkWorker();
//                $this->mkVueIndex();
//                $this->mkVueForm();
                break;
            case 'iseedNav':
                $this->iseedNav();
                break;
            case 'iseedRoles':
                $this->iseedRoles();
                break;
            case 'mkWorker':
                $this->mkWorker();
                break;
            case 'storagePerm':
                $this->setStorageFolderPermissions();
                break;
            default:
                if (method_exists($this, $command)) {
                    $this->$command();
                } else {
                    $this->info("Unrecognized command: '{$command}'");
                }

        }
    }

    protected function reseed()
    {
        
        if (Schema::hasTable('personal_access_tokens')) {
            $this->call('iseed', ['tables' => 'personal_access_tokens', '--force' => true]);
            $this->delAllStorageFolders();
            $this->dropTables();
            $this->call('migrate');
            $this->info('Migration done');
        } else {
            $this->call('migrate');
        }
        $this->seed();
    }

    protected function genStorageTree()
    {
        $this->info('Setting up directories tree');
        if (config('project.storageTree'))
        foreach ($this->storageFolders as $folder)
        {
            dump('Folder: ' . $folder);
            if (!is_dir(storage_path($folder)))
            {
                $folder = preg_match('/^(app\/)(.+)/',$folder,$matches) ? $matches[2] : $folder;
                Storage::makeDirectory($folder);
            }
        }
      
        $this->info('storage directories installed');
        return $this;
    }

    protected function setStorageFolderPermissions()
    {
        $defStoragePath = storage_path('app');
        $storageAppDefaultFolderOwner = shell_exec('ls -ld '.$defStoragePath.' | awk \'{print $3}\'');
        if ($storageAppDefaultFolderOwner != 'www-data') 
        {
            shell_exec('chown -R www-data:www-data '.$defStoragePath);
            dump('!!! PERMISSION FIXED !!!');
        }
    }

    protected function iseedNav()
    {
        $this->call('iseed', ['tables' => 'sections,vue_router', '--force' => true]);
    }

    protected function iseedRoles()
    {
        $this->call('iseed', ['tables' => 'users_roles,users_roles_permissions', '--force' => true]);
    }

    protected function showHelp()
    {
        $table_content = collect($this->commands)->transform(function ($item, $key) {
            return [
                'command' => $key,
                'description' => $item
            ];
        });
        $this->table(['Command', 'Description'], $table_content);
    }

    public function mkMigration()
    {
        $table = $this->ask('Enter table name: ');
        $this->call('make:migration', ['name' => 'create_' . $table . '_table']);
        return $table;
    }

    protected function dropTables()
    {

        $colname = 'Tables_in_' . config('database.connections.mysql.database');
        $droplist = [];

        $tables = DB::select('SHOW TABLES');
        if (count($tables)) {
            foreach ($tables as $table) {
                $droplist[] = $table->$colname;
            }
            $droplist = implode(',', $droplist);
            DB::beginTransaction();
            DB::statement('SET FOREIGN_KEY_CHECKS = 0');
            DB::statement("DROP TABLE $droplist");
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
        }

        $this->info('The following tables dropped:' . $droplist);

    }

    protected function seed()
    {
        $this->info('-> Seeding Started ...');
        $this->call('db:seed', ['--class' => 'InitSeeder']);
        $this->info('|| Seeding Completed');
        $this->setStorageFolderPermissions();
    }

    protected function genenv()
    {
        $json = file_get_contents(base_path('oad-conf.json'));
        file_put_contents(base_path('.env'), View::make('console.env', json_decode($json, true)));
    }

    public function mkController($modelName = null, $worker_name = null)
    {

        $params_folder = '';

        $controller = ($modelName) ? $modelName . 'Controller' : $this->ask('Name of the controller (without word Controller)') . 'Controller';
        $model = ($modelName) ? $modelName : $this->ask('Enter Model (to update controller)');
        if (!$modelName) {
            $folder = $this->ask('Enter Name of a folder or leave blank for no folder (path to the folder past Controller folder)');
        } else {
            $folder = null;
        }
        $apiKey = null;
        if ($updateRoute = $this->confirm('Update api.php?', true)) {
            $apiKey = $this->ask('Enter api key');
            $routeType = $this->choice('Route Type (press up / down to select): ', ['addResourceRoute', 'addSingleRoute']);
        }

        if ($folder) {
            $dir = app_path('Http/Controllers/' . $folder);
            if (!file_exists($dir) && !is_dir($dir)) {
                mkdir($dir);
            }
            $params_folder = '\\' . $folder;
            $folder .= '/';
        }
        $params = [
            'controller_name' => $controller,
            'model_name' => $model,
            'folder' => $params_folder,
            'section_slug' => $apiKey,
            'worker_name' => $worker_name
        ];
        $view = ($worker_name) ? 'console.controller_crud' : 'console.controller';
        file_put_contents(
            app_path('Http/Controllers/' . $folder . $controller . '.php'),
            '<?php' . PHP_EOL . View::make($view, $params)
        );

        if ($updateRoute) {
            $api_routes_file = file_get_contents(base_path('routes/api.php'));

            file_put_contents(
                base_path('routes/api.php'),
                str_replace(
                    '/*last_line*/;',
                    PHP_EOL . "    ->{$routeType}('{$apiKey}','{$controller}')/*last_line*/;"
                    , $api_routes_file)
            );
        }

        $this->info('Controller ' . $folder . $controller . ' created');

        return $params;

    }

    public function mkModel($table = null)
    {

        $model_name = $this->ask('Model Name');
        if (!$table) {
            $folder = $this->ask('Model Folder (leave blank for root) ');
        } else {
            $folder = null;
        }

        $table_name = ($table) ? $table : $this->ask('Table Name');
        $name_space = '';

        if ($folder) {
            $dir = app_path('Models/' . $folder);
            if (!file_exists($dir) && !is_dir($dir)) {
                mkdir($dir);
            }
            $folder .= '/';
            $name_space = '\\' . $folder;

        }
        $params = [
            'model_name' => $model_name,
            'table_name' => $table_name,
            'name_space' => $name_space
        ];
        file_put_contents(
            app_path('Models/' . $folder . $model_name . '.php'),
            '<?php' . PHP_EOL . View::make('console.model', $params)
        );

        $this->info('Model ' . $folder . $model_name . ' created');

        return $params;

    }

    public function mkVueIndex($apiKey = null, $pageTitle = null)
    {
        $this->path = ($apiKey) ? $apiKey : $this->ask("Folder or Path From js/views/backend/");
        if ($this->path) $this->path .= '/';
        $this->apiKey = ($apiKey) ? $apiKey : $this->ask('API key: ');
        $this->fileName = $this->apiKey . 'Index';
        $title = ($pageTitle) ? $pageTitle : $this->ask('Vue Index Page Title: ');
        $dir = resource_path('js/views/backend/' . $this->path);

        if (!file_exists($dir) && !is_dir($dir)) {
            mkdir($dir);
        }

        $params = [
            'title' => $title,
            'resource' => $this->apiKey,
            'ref' => \Str::camel($this->apiKey)
        ];

        file_put_contents(
            $dir . $this->fileName . '.vue',
            View::make('console.vueIndex', $params)
        );

        $this->info("Vue Index 'js/views/backend/{$this->path}{$this->fileName}.vue' created");

    }

    public function mkVueForm($name = null, $pageTitle = null)
    {

        $this->fileName = ($name) ? $name . 'Form' : $this->ask('Vue Form File Name (without .vue) - leave blank for default: ');

        if ($this->path === false) {
            $this->path = $this->ask("Folder or Path From @/views/backend/");
            if ($this->path) $this->path .= '/';
        }

        $title = ($pageTitle) ? $pageTitle : $this->ask('Vue Form Page Title: ');
        if ($this->apiKey === false)
            $this->apiKey = $this->ask('Resource: ');
        if (!$this->fileName)
            $this->fileName = $this->apiKey . 'Form';

        $dir = resource_path('js/views/backend/' . $this->path);
        if (!file_exists($dir) && !is_dir($dir)) {
            mkdir($dir);
        }

        $params = [
            'title' => $title,
            'resource' => $this->apiKey
        ];

        file_put_contents(
            $dir . $this->fileName . '.vue',
            View::make('console.vueForm', $params)
        );

        $this->info("Vue Form '@/views/backend/{$this->path}{$this->fileName}.vue' created");

    }

    protected function delAllStorageFolders()
    {
        $this->info('Deleting all storage folders....');
        $folders = Storage::directories();
        foreach ($folders as $folder)
        {
            dump($folder . ' deleted');
            Storage::deleteDirectory($folder);
        }
        dump('All storage folders deleted');
    }

    public function deleteUnusedTmpFiles()
    {
        //find records with File model that have been created more than 2 days ago
        File::where('created_at', '<', Carbon::now()->subDays(2))->get()
            ->each(function ($file) 
            {
                //delete the file
                Storage::delete($file->path);
                $file->delete();
        });
    }

    public function mkWorker($workerName = null)
    {
        $params_folder = '';

        $worker = ($workerName) ? $workerName . 'Worker' : $this->ask('Name of the worker (without word Workers)') . 'Worker';
        if (!$workerName) {
            $folder = $this->ask('Enter Name of a folder or leave blank for no folder (path to the folder past Workers folder)');
        } else {
            $folder = null;
        }

        if ($folder) {
            $dir = app_path('Workers/' . $folder);
            if (!file_exists($dir) && !is_dir($dir)) {
                mkdir($dir);
            }
            $params_folder = '\\' . $folder;
            $folder .= '/';
        } else {
            $dir = app_path('Workers');
            if (!file_exists($dir) && !is_dir($dir)) {
                mkdir($dir);
            }
        }
        $params = [
            'worker_name' => $worker,
            'folder' => $params_folder,
        ];

        file_put_contents(
            app_path('Workers/' . $folder . $worker . '.php'),
            '<?php' . PHP_EOL . View::make('console.worker', $params)
        );

        $this->info('Worker ' . $folder . $worker . ' created');

        return $params;
    }

    public function mkCrud()
    {
        $title = $this->ask('Enter Page Title');
        $table = $this->mkMigration();
        $modelParams = $this->mkModel($table);
        $workerParams = $this->mkWorker($modelParams['model_name']);
        $controllerParams = $this->mkController($modelParams['model_name'], $workerParams['worker_name']);
        $this->mkVueIndex($table, $title);
        $this->mkVueForm($table, $title);
        $this->info('CRUD created');
    }

    protected function genVueRoutes()
    {
        //this line is used to change the view files path to local not submodule, so that i can modify the blade for this project
        View::getFinder()->setPaths(resource_path('views'));

        file_put_contents(
            resource_path('/js/routes.js'), 
            View::make('vueRoutesjs', [ 
                    'content' => $this->RoutesJsContent()
            ])
        );
        $this->info('Route File generated');
    }

    private function RoutesJsContent($parent_id = null, $tb = '        ')
    {
        $content    = '';
        $stb        = '    ';
        $tb         = $tb.$stb;

        if ($items = VueRouter::where('parent_id',$parent_id)->sortList()->get())
        {
            foreach ($items as $item)
            {
                $content .= $tb.'{'.PHP_EOL;
                $content .= $tb.$stb.'path: \''.$item->path.'\','.PHP_EOL;
                $content .= $tb.$stb.'name: "'.$item->name.'",'.PHP_EOL;
                
                $content .= $tb.$stb.'component: () => import("@'.$item->componentPath.'"),'.PHP_EOL;
                $content .= $tb.$stb.'meta: { title: "'.$item->metaTitle.'" + " | " + CONF.APP_NAME },'.PHP_EOL;
                    
                if (VueRouter::where('parent_id',$item->id)->count()) 
                {
                    $content .= $tb.$stb.'children: ['.PHP_EOL;
                    $content .= $this->RoutesJsContent($item->id,$tb . $stb);
                    $content .= $tb.$stb.']'.PHP_EOL;
                }

                $content .= $tb.'},'.PHP_EOL;
            }
        }
        return $content;
    }

}
