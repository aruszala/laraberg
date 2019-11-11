<?php

namespace VanOns\Laraberg\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use VanOns\Laraberg\Helpers\GetTextHelper;
use ZipArchive;

class ApplicationController extends BaseController
{
    public function ok($data = ['message' => 'ok'], $code = 200)
    {
        return $this->response($data, $code);
    }

    public function notFound($code = 404)
    {
        return $this->response(['message' => 'not_found'], $code);
    }

    public function response($data, $code)
    {
        return response($data, $code)->header('Content-Type', 'application/json');
    }

    public function getTranslations()
    {
        $locale = config("laraberg.locale", null);

        if(!$locale) return $this->ok();

        $jedFileName = "$locale.jed";
        $jedContents = null;

        $zippath = storage_path($locale).DIRECTORY_SEPARATOR;
        $jedFilePath = $this->normalizePath(
            dirname(__FILE__) .
            DIRECTORY_SEPARATOR .
            implode( DIRECTORY_SEPARATOR, ["..","..","resources","lang","vendor","laraberg", $jedFileName] )
        );

        if ( \Str::startsWith($locale, "en_") == false ) {

            // check if translation exists
            if(file_exists($jedFilePath) == false) {

                // check if zip files are already downloaded
                if(file_exists($zippath) == false) {

                    // try to fetch the language packages
                    $languages = json_decode(file_get_contents("http://api.wordpress.org/translations/core/1.0/"));

                    if($languages) {
                        $localeData = array_values(array_filter($languages->translations, function($translation) use ($locale){
                            return $translation->language == $locale;
                        }));

                        if(count($localeData) > 0) {
                            $currentLocaleData = $localeData[0];
                            $zipContents = file_get_contents($currentLocaleData->package);
                            file_put_contents(storage_path(basename($currentLocaleData->package)), $zipContents);
                            $zip = new ZipArchive();
                            if($zip->open(storage_path(basename($currentLocaleData->package)))) {
                                $zip->extractTo($zippath);
                                $zip->close();
                            }
                            unlink(storage_path(basename($currentLocaleData->package)));
                        }
                    }
                }

                if(file_exists($zippath) == true && is_dir($zippath)) {
                    try {

                        /**
                         * Merge PO files
                         */
                        $files = scandir($zippath);
                        $poFilePaths = array_filter($files, function($file){ return pathinfo($file, PATHINFO_EXTENSION) == "po"; });
                        foreach($poFilePaths as $poFilePath){
                            $converted = GetTextHelper::convertPOTtoJED($locale, $zippath . $poFilePath, $jedFilePath);
                            if($converted == false){
                                throw new \Exception("POT File conversion failed!");
                            }
                            if(!$jedContents){
                                $jedContents = $converted;
                            }else{
                                $jedContents = GetTextHelper::mergeJeds($jedContents, $converted);
                            }
                        }

                        /**
                         * Merge Jed Files
                         */
                        $jsons = array_filter($files, function($file){ return pathinfo($file, PATHINFO_EXTENSION) == "json"; });
                        foreach($jsons as $json)
                        {
                            $contents = json_decode(file_get_contents($zippath . $json));
                            if($contents){
                                $jedContents = GetTextHelper::mergeJeds($jedContents, $contents);
                            }
                        }

                        /**
                         * Save Jed object to file
                         */
                        if(!is_dir(dirname($jedFilePath))) mkdir(dirname($jedFilePath), 0777, true);
                        file_put_contents($jedFilePath, json_encode($jedContents));
                        \Storage::deleteDirectory($zippath);

                    }
                    catch(\Exception $ex) {
                        return $this->response(["message" => $ex->getMessage(), "trace" => $ex->getTrace()], 401);
                    }
                }
            }
        } else {

            /**
             * Read the previously saved file
             */
            $jedContents = json_decode(file_get_contents($jedFilePath));
        }

        if($jedContents) {

            /**
             * Append override translations from laravel language files
             */

            $translations = trans("laraberg::".$locale);

            // Populate Jed with the overrides
            if(is_array($translations))
            {
                $domain = $jedContents->domain;
                foreach($translations as $key => $value){
                    if(!is_array($value)) {
                        $jedContents->locale_data->{$domain}->{$key} = [$value];
                    } else {
                        $jedContents->locale_data->{$domain}->{$key} = $value;
                    }
                }
            }


            /**
             * Send Jed object to frontend
             */
            return $this->response(["message" => "ok", "jed" => $jedContents], 200);
        }

        return $this->ok();
    }

    private function normalizePath($path){
        $route = explode(DIRECTORY_SEPARATOR, $path);
        $newPath = [];
        foreach($route as $waypoint){
            if($waypoint == ".."){
                array_pop($newPath);
            }else{
                array_push($newPath, $waypoint);
            }
        }
        return implode(DIRECTORY_SEPARATOR, $newPath);
    }
}
