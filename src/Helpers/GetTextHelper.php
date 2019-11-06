<?php

namespace VanOns\Laraberg\Helpers;

use stdClass;

class GetTextHelper
{
    /**
     * Converts POT file format to JED file format
     * @param String $potFile
     * @return String $jedContents
     */
    public static function convertPOTtoJED($locale, $potFile, $jedFile) {
        $instance = (new self);
        $contents = $instance->readPOTFile($potFile);
        if($contents) {
            $jed = $instance->buildJED($locale, $contents);
            file_put_contents($jedFile, $jed);
            return true;
        }
        return false;
    }

    /**
     * Reads the POT file into an array
     * @param String $potFile
     * @return Array $struct
     */
    private function readPOTFile($potFile)
    {
        $struct = ["header" => [], "translations" => []];
        $current = ["comments" => [], "references" => [], "flags" => []];
        $struct["locale"] = preg_replace("/\.po$/", "", basename($potFile), 1);
        $fp  = fopen($potFile, "r");
        $is_header = false;
        while(!feof($fp))
        {
            $line = trim(fgets($fp));
            if(\Str::startsWith($line, "# ")) {
                // translator comments
                $current["comments"][] = $this->getSingleLineStr($line, "# ");
            } elseif(\Str::startsWith($line, "#. ")) {
                // extracted comments
                $current["comments"][] = $this->getSingleLineStr($line, "#. ");
            } elseif(\Str::startsWith($line, "#: ")) {
                // reference
                $current["references"][] = $this->getSingleLineStr($line, "#: ");
            } elseif(\Str::startsWith($line, "#, ")) {
                // flag
                $current["flags"][] = $this->getSingleLineStr($line, "#, ");
            } elseif(\Str::startsWith($line, "#| ")) {
                // previous untranslated string
                $current["previous"] = $this->getSingleLineStr($line, "#| ");
            } elseif(\Str::startsWith($line, "msgid \"\"")) {
                if(count($struct["translations"]) == 0){
                    // header comment
                    $is_header = true;
                    $current["header"] = $this->getMultilineStr($fp, false);
                }else{
                    // untranslated multiline string
                    $current["source"] = $this->getMultilineStr($fp);
                }
            } elseif(\Str::startsWith($line, "msgid ")) {
                // untranslated string
                $current["source"] = $this->getSingleLineStr($line, "msgid ");
            } elseif(\Str::startsWith($line, "msgid_plural ")) {
                // untranslated plural string
                $current["source_plural"] = $this->getSingleLineStr($line, "msgid_plural ");
            } elseif(\Str::startsWith($line, "msgstr \"\"")) {
                // translated multiline string
                $current["translation"] = $this->getMultilineStr($fp, !$is_header);
            } elseif(\Str::startsWith($line, "msgstr ")) {
                // translated string
                $current["translation"] = $this->getSingleLineStr($line, "msgstr ");
            } elseif(\Str::startsWith($line, "msgstr[")) {
                    // translated string
                    $translation = $this->getIndexedLineStr($line);
                    $current["translation"][$translation["index"]] = $translation["text"];
            } elseif(\Str::startsWith($line, "msgctxt ")) {
                // context
                $current["context"] = $this->getSingleLineStr($line, "msgctxt ");
            } elseif($line == "") {
                // record separator
                if(!$is_header) {
                    $struct["translations"][] = $current;
                }else{
                    $struct["header"] = $current;
                    $is_header = false;
                }
                $current = ["comments" => [], "references" => [], "flags" => []];
            }
        }

        return $struct;

    }

    private function getSingleLineStr($str, $type) {
        return preg_replace("/^\s*" . $type . "\s*\"(.+)\"\s*$/", "$1", $str, 1);
    }

    private function getIndexedLineStr($str)
    {
        $matches = [];
        preg_match("/^\s*msgstr\[(\d+)\]\s*\"(.+)\"\s*$/", $str, $matches);
        return ["index" => $matches[1], "text" => $matches[2]];
    }

    private function getMultilineStr($fp, $concatenate = true) {
        $terminators = ["msgstr \"\"", ""];
        $str = [];
        $pos = ftell($fp);
        while(true) {
            $line = trim(fgets($fp));
            if(in_array($line, $terminators) == false) {
                $str[] = $this->getSingleLineStr($line, "");
            } else {
                fseek($fp, $pos, SEEK_SET);
                return $concatenate ? implode("", $str) : $str;
            }
            $pos = ftell($fp);
        }
    }

    private function buildJED($locale, $potArray)
    {
        $header = $this->parseHeader($potArray["header"]["translation"]);

        $jed = new stdClass();

        $jed->{"translation-revision-date"} = $header["PO-Revision-Date"];
        $jed->generator = $header["X-Generator"];
        $jed->domain = "messages";
        $jed->locale_data = new StdClass();
        $jed->locale_data->messages = new StdClass();
        $jed->locale_data->messages->{""} = new StdClass();
        $jed->locale_data->messages->{""}->domain = "messages";
        $jed->locale_data->messages->{""}->{"plural-forms"} = $header["Plural-Forms"];
        $jed->locale_data->messages->{""}->lang = $locale;

        foreach($potArray["translations"] as $translation)
        {
            $context = $translation["context"] ?? false;
            $key = $translation["source"];
            $plural_key = $translation["source_plural"] ?? false;
            $translations = $translation["translation"];

            if(!$translations || !$key) continue;

            if($context){
                $contextSeparator = json_decode('"\u0004"');
                $key = $context . $contextSeparator . $key;
                if($plural_key){
                    $plural_key = $context . $contextSeparator . $plural_key;
                }
            }

            $jed->locale_data->messages->{$key} = is_array($translations) ? $translations : [$translations];
            if($plural_key) {
                $jed->locale_data->messages->{$plural_key} = is_array($translations) ? $translations : [$translations];
            }
        }

        return json_encode($jed);
    }

    private function parseHeader($header)
    {
        $headers = [];
        for($i = 0, $len = count($header); $i < $len; $i++)
        {
            $matches = [];
            preg_match("/^([^:]+): (.+)$/", $header[$i], $matches);
            if(count($matches) == 3){
                $headers[$matches[1]] = str_replace("\\n", "", $matches[2]);
            }
        }
        return $headers;
    }


}
