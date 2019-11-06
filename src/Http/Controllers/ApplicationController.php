<?php

namespace VanOns\Laraberg\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Storage as Storage;
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

        if($locale == null) return $this->ok();

        $poFileName = "$locale.po";
        $jedFileName = "$locale.jed";
        $poFilePath = dirname(__FILE__)."/../../resources/lang/$poFileName";
        $jedFilePath = dirname(__FILE__)."/../../resources/lang/$jedFileName";
        if ( $locale && \Str::startsWith($locale, "en_") == false ) {
            // check if translation exists
            if(file_exists($poFilePath) == false) {
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
                            $poFileContents = $zip->getFromName($poFileName);
                            if($poFileContents) {
                                file_put_contents($poFilePath, $poFileContents);
                            }
                            $zip->close();
                        }
                        unlink(storage_path(basename($currentLocaleData->package)));
                    }
                }
            }

            if(file_exists($poFilePath) == true) {
                try {
                    if(file_exists($jedFilePath) == false){
                        if(GetTextHelper::convertPOTtoJED($locale, $poFilePath, $jedFilePath)){
                            throw new \Exception("POT File conversion failed!");
                        }
                    }

                    $jedContents = json_decode(file_get_contents($jedFilePath));

                    if($jedContents){
                        return $this->response(["message" => "ok", "jed" => $jedContents], 200);
                    }
                }
                catch(\Exception $ex) {
                    return $this->response(["message" => $ex->getMessage(), "trace" => $ex->getTrace()], 401);
                }
            }
        }
        return $this->ok();
    }
}
