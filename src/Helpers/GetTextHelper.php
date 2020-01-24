<?php

namespace aruszala\Laraberg\Helpers;

use stdClass;

class GetTextHelper
{
    /**
     * Converts POT file format to JED file format
     * @param String $potFile
     * @return String $jedContents
     */
    public static function convertPOTtoJED($locale, $potFile) {
        $instance = (new self);
        $contents = $instance->readPOTFile($potFile);
        if($contents) {
            return $instance->buildJED($locale, $contents);
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

    /**
     * Gets content from a single POT line with a specific header type
     * @param String $str
     * @param String $type
     * @return String
     */
    private function getSingleLineStr($str, $type) {
        return preg_replace("/^\s*" . $type . "\s*\"(.+)\"\s*$/", "$1", $str, 1);
    }

    /**
     * Gets content from an indexed POT line like msgstr[0], msgstr[n]
     * @param String str
     * @return Array
     */
    private function getIndexedLineStr($str)
    {
        $matches = [];
        preg_match("/^\s*msgstr\[(\d+)\]\s*\"(.+)\"\s*$/", $str, $matches);
        return ["index" => $matches[1], "text" => $matches[2]];
    }

    /**
     * Reads multiline string starting from POT file pointer
     * @param FilePtr $fp
     * @param Boolean $concatenate
     * @return String|Array
     */
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

    /**
     * Builds JED file from parsed POT file array structure
     * @param String $locale
     * @param Array $potArray
     * @return String $json
     */
    private function buildJED($locale, $potArray)
    {
        $header = $this->parseHeader($potArray["header"]["translation"]);

        $jed = new stdClass();

        $jed->{"translation-revision-date"} = $header["PO-Revision-Date"];
        $jed->generator = $header["X-Generator"];
        $jed->domain = "default";
        $jed->locale_data = new StdClass();
        $jed->locale_data->default = new StdClass();
        $jed->locale_data->default->{""} = new StdClass();
        $jed->locale_data->default->{""}->domain = "default";
        $jed->locale_data->default->{""}->{"plural-forms"} = $header["Plural-Forms"];
        $jed->locale_data->default->{""}->lang = $locale;

        foreach($potArray["translations"] as $translation)
        {
            $context = $translation["context"] ?? false;
            $key = $translation["source"];
            $previous = $translation["previous"] ?? false;
            $plural_key = $translation["source_plural"] ?? false;
            $translations = $translation["translation"];

            if(!$translations || !$key) continue;

            if($context){
                $contextSeparator = json_decode('"\u0004"');
                $key = $context . $contextSeparator . $key;
                if($previous){
                    $previous = $context . $contextSeparator . $previous;
                }
                if($plural_key){
                    $plural_key = $context . $contextSeparator . $plural_key;
                }
            }

            $jed->locale_data->default->{$key} = is_array($translations) ? $translations : [$translations];
            if($plural_key) {
                $jed->locale_data->default->{$plural_key} = is_array($translations) ? $translations : [$translations];
            }
            if($previous) {
                $jed->locale_data->default->{$previous} = is_array($translations) ? $translations : [$translations];
            }
        }

        return $jed;
    }

    /**
     * Parses POT file header array
     * @param Array $header
     * @return Array $headers
     */
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

    /**
     * Merges second Jed object onto first one
     * @param Object $jed1
     * @param Object $jed2
     * @return Object $jed1
     */
    public static function mergeJeds($jed1, $jed2)
    {
        //die(print_r($jed1));
        $destination_domain = $jed1->domain;
        $source_domain = $jed2->domain;
        $source_contents = $jed2->locale_data->{$source_domain};
        $source_keys = array_filter(array_keys(get_object_vars($source_contents)));
        foreach($source_keys as $key)
        {
            if(trim($key) !== ""){
                $jed1->locale_data->{$destination_domain}->{$key} = $source_contents->{$key};
            }
        }
        return $jed1;
    }


}
