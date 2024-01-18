<?php

namespace Oadsoft\Crmcore\Workers;

use App\Models\OAD\File;
use Illuminate\Support\Str;
use mikehaertl\pdftk\Pdf as PDFTK;

class DocumentWorker
{
    // $morthData = [ 'attachment_id','attachment_type','attachment_field']
    public static function storeIntoSystem(string $absOrigPath = '', null|array $morphData = [], string $storageTreeKey = 'tmp', string|null $newName = null) : File
    {
        $file               = new File();
        $ext                = pathinfo($absOrigPath, PATHINFO_EXTENSION);
        $fileName           = $newName ? $newName . '.' . $ext : basename($absOrigPath);
        $storagePath        = config('project.storageTree.' . $storageTreeKey) . self::genTmpFileName($ext);

        $file->file_name    = $fileName;
        $file->ext          = $ext;
        $file->size         = filesize( $absOrigPath );
        $file->mime         = mime_content_type( $absOrigPath );
        $file->is_saved     = 1;
        $file->path         = $storagePath;

        if ($morphData)
        {
            $file->attachment_id    = $morphData[0];
            $file->attachment_type  = $morphData[1];
            $file->attachment_field = $morphData[2];
        }
        $file->save();

        rename($absOrigPath, storage_path( $storagePath ));

        return $file;
    }

    public static function genTmpFileName(string $ext)
    {
        return time() . '-' . Str::random(10) . '.' . $ext;
    }

    public static function splitPDFIntoPages(string $absOriginalFilePath = '', bool|null $delSrouce = false) : array
    {
        $burstPattern   = 'page-%d.pdf';
        $outPuthDir     = storage_path( config('project.storageTree.tmp') );
        $absPaths       = [];

        if (Str::endsWith($outPuthDir, '/') == false) 
        {
            $outPuthDir .= '/';
        }    
        $outPuthDir       = $outPuthDir . time() .'-' . Str::random(10) . '/';       
        mkdir( $outPuthDir );

        $pdftk = new PDFTK($absOriginalFilePath);
        $pdftk->burst($outPuthDir . $burstPattern);
        $page = 1;

        while (file_exists($outPuthDir . sprintf($burstPattern, $page)))
        {
            $fileName       = sprintf($burstPattern, $page);
            $files[]        = $fileName;
            $absPaths[]     = $outPuthDir . $fileName;
            $page++;
        }

        if ($delSrouce)
        {
            unlink($absOriginalFilePath);
        }

        return [
            'absDir'        => $outPuthDir,
            'files'         => $files,
            'absPaths'      => $absPaths
        ];
    }

    public static function mergePDFs(array $filesPath = [], bool $abs = false, bool $delSource = false) : string
    {
        $pdftk          = new PDFTK();
        $fileName       = self::genTmpFileName('pdf');
        $storagePath    = config('project.storageTree.tmp') . $fileName;
        $absResultPath  = storage_path( $storagePath );

        foreach ($filesPath as $path)
        {
            $path = $abs ? $path : storage_path( $path );
            $pdftk->addFile($path);
        }
        $pdftk->flatten()->saveAs( $absResultPath );

        if ($delSource)
        {
            foreach ($filesPath as $path)
            {
                unlink( $path );
            }        
        }

        return $absResultPath;
    }

    public static function ocrDocument()
    {
        return [
            'text'              => '',
            'textLines'         => '',
            'dataCollections'   => ''
        ];
    }
}