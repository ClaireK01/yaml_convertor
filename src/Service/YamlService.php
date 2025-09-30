<?php

namespace App\Service;

use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\VarDumper\VarDumper;

class YamlService{

    //TODO: ESSAYER DE FORCER REFORMATTAGE PAR LIGNE D'INDENTATION

    private $kernel;

    public  function __construct(KernelInterface $kernel){
        $this->kernel = $kernel;
    }

    private $original;

    private $target;

    private $space;

    public function setOrginal($original){
        $this->original = $original;
    }

    public function setTarget($target){
        $this->target = $target;
    }

    public function setSpace($space){
        $this->space = $space;
    }


    public function handleYaml(string $path, bool $concatMultiligne){
        $yaml = file_get_contents($path);
        $arrayYaml = explode("\n",$yaml);
        $arrayYaml = preg_replace("#(?= )(?<= )( )|^ #",'->', $arrayYaml);
        $arrayYaml = $this->reformat($arrayYaml);
        $i = 0;
        $arrayTrans = [];

        if(!$concatMultiligne){
            $arrayTrans = $this->processYaml($arrayTrans, $i, 0, $arrayYaml)["array"];
        }else{
            $arrayTrans = $this->processYamlWithConcatMultiligne($arrayTrans, $i, 0, $arrayYaml)["array"];
        }

        return $arrayTrans;

    }

    public function reformat($arrayYaml){

        $indentation = 0;
        $oldIndentation = 0;

        foreach ($arrayYaml as $i => $line){
            $indentationRegex = "#^(?<!->)(->){". $indentation + 1 .",}(?!->)#";
            $desindentationRegex = "#^(?<!->)(->){0,". $indentation - 1 ."}(?!->)#";

            $matchIndentation = preg_match($indentationRegex, $line);
            $matchDesindentation = ($indentation > 0 ?  preg_match($desindentationRegex, $line) : 0);
            $currentIndentation = preg_match_all("#->#", $line, $match);

            if($matchIndentation == 1 &&  $currentIndentation != $oldIndentation){
                $indentation++;
                $arrayYaml[$i] = preg_replace($indentationRegex, str_repeat('->', $indentation), $line);
            }elseif ($matchDesindentation == 1  &&  $currentIndentation != $oldIndentation){
                $indentation = $currentIndentation == 0 ?  0 : $indentation - 1;
                $arrayYaml[$i] = preg_replace($desindentationRegex, str_repeat('->', $indentation), $line);
            }else{
                $arrayYaml[$i] = str_replace("->", str_repeat('->', $indentation), $line);
            }

            $oldIndentation = $currentIndentation;
        }

        return $arrayYaml;
    }

    public function processYaml($array, $index, $indentation, $yaml, $multiligne = false, $sentence = null) {
        $space = 1;
        //recuperation ligne actu, prec et suiv
        $prev = key_exists($index - 1, $yaml) ? $yaml[$index - 1] : null;
        $ligne = $yaml[$index];
        $next = key_exists($index + 1, $yaml) ? $yaml[$index + 1] : null;

        //check si indentation
        $matchIndentationNextLine = preg_match("#^(?<!->)(->){". $indentation + $space .",}(?!->)#", $next);
        $matchDesindentationNextLine =  preg_match("#^(?<!->)(->){0,". $indentation - $space ."}(?!->)#", $next, $match);
        $realIndentationNext = preg_match_all("#->#", $ligne, $match);

        if(!ctype_space($ligne) && $ligne != "\r" && $ligne != ""){
            $trans = $multiligne ? [$ligne] : preg_split("#(:)#", $ligne, 2);
            $word = $multiligne || !key_exists(1, $trans) ? $trans[0] : $trans[1];
        }else{
            $trans = [$ligne, $ligne];
            $word = $trans[0] ;
        }

        if($matchIndentationNextLine == 1) {
            $indentation += $space;
            $index++;
            if(!$multiligne && key_exists(1, $trans) && str_replace([" ", "\s", "\r"], "", $trans[1]) == "|"){
                $multiligne = true;
            }elseif($multiligne && $matchDesindentationNextLine == 1){
                $multiligne = false;
            }

            $res = $this->processYaml($trans, $index, $indentation, $yaml, $multiligne);
            $trans = $res["array"];
            $index = $res['index'];
            $indentation = $res["indentation"];
            $multiligne = $res["multiligne"];
            $trans['ind'] = $indentation;
            if($word && !ctype_space($word) && $word != "\r"){
                $translated = $this->getTranslation($word, "FR", "EN");
                $trans[1] = $translated;
            }
            $array[] = $trans;
        }else{
            if(!ctype_space($word) && $word != "\r"){
                $translated = $this->getTranslation($word, "FR", "EN");
                if(!$multiligne && key_exists(1, $trans)){
                    $trans[1] = $translated;
                }else{
                    $trans[0] = $translated;
                }
                //multiligne pr prochaine
                if(!$multiligne && key_exists(1, $trans) && str_replace(["\w", "->", "\r"], "", $trans[1]) == "|"){
                    $multiligne = true;
                }elseif($multiligne && $matchDesindentationNextLine == 1){
                    $multiligne = false;
                }

                if($matchDesindentationNextLine == 1 && $realIndentationNext == 0){
                    $trans['ind'] = $realIndentationNext;
                }else{
                    $trans['ind'] = $indentation;
                }

                $array[] = $trans;
                $index++;
            }else{
                $array[] = $trans;
                $index++;
            }
        }

        if(key_exists($index, $yaml) && $matchDesindentationNextLine == 0) {
            $res = $this->processYaml($array, $index, $indentation, $yaml, $multiligne, $sentence);
            $index = $res["index"];
            $array = $res["array"];
            $indentation = $res["indentation"];
            $multiligne = $res["multiligne"];
        }

        if($matchDesindentationNextLine == 1){
            $indentation -= $space;
        }

        return ["array" => $array, "index" => $index, 'indentation' => $indentation, 'multiligne' => $multiligne];
    }

    public function generateTranslationFile($data, $kernel)
    {
        $now = new \DateTime();
        $file = $kernel->getProjectDir() . "/public/translationFiles/";
        $name = 'yaml-translator-' . $now->getTimestamp() .'.fr.yaml';

        $stream = fopen($file . $name , 'w+');
        if ($stream) {
            function loop($array, $stream)
            {
                $key = null;
                $val = null;
                $ind = null;

                foreach ($array as $idx => $d){
                    if($idx <= 1 || $idx == "ind"){
                        if($idx == 0){
                            $key = str_replace("->", "", $d);
                        }elseif($idx == 1){
                            $val = $d;
                        }elseif ($idx == "ind"){
                            $ind = $d > 0 ? str_repeat(" ", $d) : "";
                        }
                    }
                }

                if($key && !ctype_space($key) ){
                    $ligne =  $ind . ($val != null ? $key . " : ". $val : $key);
                }else{
                    $ligne = "";
                }
                fwrite($stream, $ligne);
                fwrite($stream, "\n");

                foreach ($array as $idx => $d){
                    if ($idx > 1 ){
                        if(is_array($d)){
                            loop($d, $stream);
                        }
                    }
                }

                return $ligne;
            }
            foreach ($data as $index => $array){
               loop($array, $stream);
            }

            fclose($stream);

            return 'translationFiles/'.$name;
        }

        return false;
    }


    public function uploadFile($file){
        $now = new \DateTime();
        $allowed = array('yaml', 'yml');
        $dir = 'uploads/';
        $src = $this->kernel->getProjectDir() . '/public/' . $dir;

        if (!file_exists($src)) {
            mkdir($src, 0777, true);
        }

        $ogName = str_replace('.' . $file->getClientOriginalExtension(), '',  $file->getClientOriginalName());
        $name = $ogName . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $name = $file->getClientOriginalName();
        $search = array(' ', '+', "(", ")", "'", '"', '/', '&', '<', '>', '€', '‘', '’', '“', '”', '–', '—', '¡', '¢', '£', '¤', '¥', '¦', '§', '¨', '©', 'ª', '«', '¬', '®', '¯', '°', '±', '²', '³', '´', 'µ', '¶', '·', '¸', '¹', 'º', '»', '¼', '½', '¾', '¿', 'À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', '×', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'Þ', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ð', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', '÷', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'þ', 'ÿ', 'Œ', 'œ', '‚', '„', '…', '™', '•', '˜');
        $name = str_replace($search, '_', $name);
        $name = str_replace(" ", "_", $name);

        if (in_array(strtolower($file->getClientOriginalExtension()), $allowed)) {
                $name = $now->format('U') . '-' . $name;
                $file->move($src, $name);

                return ["status" => 200, 'path' => $src.$name];
        }

        return ["status" => 500, 'message' => "Format non valide. Les formats acceptés sont : yaml, yml"];

    }

    public function getTranslation($word){
        if($word != ""){
            $pem = $this->kernel->getProjectDir() . '/cacert.pem';

            $url = 'https://api.mymemory.translated.net/';
            $fullUrl = $url . 'get?q='.$word.'&langpair=' . $this->original. '|' . $this->target ."&de=shishou@gmail.com";
            $client = new Client([
                'verify' => $pem,
                'base_uri' => $url,
                'timeout' => 6.0,
            ]);

            $output = $client->get($fullUrl)->getBody()->getContents();

            $json = json_decode($output);
            $trans = null;
            //faire un array reduce
                foreach ($json->matches as $m){
                    if($trans == null){
                        $trans = $m;
                    }else if($trans instanceof \stdClass && $m->match > $trans->match){
                        $m->match = $trans->match;
                        $trans = $m;
                    }
                }

            return $trans->translation;
        }

        return "";
    }

    //même func avec concat multigne. Voir laquelle garder
    public function processYamlWithConcatMultiligne($array, $index, $indentation, $yaml, $multiligne = false, $sentence = null)
    {
        $space = $this->space;
        //recuperation ligne actu, prec et suiv
        $prev = key_exists($index - 1, $yaml) ? $yaml[$index - 1] : null;
        $ligne = $yaml[$index];
        $next = key_exists($index + 1, $yaml) ? $yaml[$index + 1] : null;

        //check si indentation
        $matchIndentationNextLine = preg_match("#^(?<!->)(->){" . $indentation + $space . ",}(?!->)#", $next);
        $matchDesindentationNextLine = preg_match("#^(?<!->)(->){0," . $indentation - $space . "}(?!->)#", $next, $match);

        if (!ctype_space($ligne) && $ligne != "\r" && $ligne != "") {
            $trans = $multiligne ? [$ligne] : preg_split("#(:)#", $ligne, 2);
            $word = $multiligne ? $trans[0] : $trans[1];
        } else {
            $trans = [$ligne, $ligne];
            $word = $trans[0];
        }

        if ($matchIndentationNextLine == 1) {
            $indentation += $space;
            $index++;
            if (!$multiligne && str_replace([" ", "\s", "\r"], "", $trans[1]) == "|") {
                $multiligne = true;
            } elseif ($multiligne && $matchDesindentationNextLine == 1) {
                $multiligne = false;
            }

            $res = $this->processYamlWithConcatMultiligne($trans, $index, $indentation, $yaml, $multiligne);
            $trans = $res["array"];
            $index = $res['index'];
            $indentation = $res["indentation"];
            $multiligne = $res["multiligne"];
            $trans['ind'] = $indentation;
            $array[] = $trans;
        } else {
            if (!ctype_space($word) && $word != "\r") {
                if (!$multiligne) {
                    $translated = $this->getTranslation($word, "FR", "EN");
                    $trans[1] = $translated;
                    $trans['ind'] = $indentation;
                    $array[] = $trans;
                } else {
                    $word = str_replace(["\r", "->"], "", $word);
                    $sentence .= " " . $word;
                }
                //multiligne pr prochaine
                if (!$multiligne && str_replace(["\w", "->", "\r"], "", $trans[1]) == "|") {
                    $multiligne = true;
                } elseif ($multiligne && $matchDesindentationNextLine == 1) {
                    $translated = $this->getTranslation($sentence);
                    $trans[0] = $translated;
                    $sentence = null;
                    $multiligne = false;
                    $trans['ind'] = $indentation;
                    $array[] = $trans;
                }

                $index++;
            } else {
                $array[] = $trans;
                $index++;
            }
        }

        if(key_exists($index, $yaml) && $matchDesindentationNextLine == 0) {
            $res = $this->processYamlWithConcatMultiligne($array, $index, $indentation, $yaml, $multiligne, $sentence);
            $index = $res["index"];
            $array = $res["array"];
            $indentation = $res["indentation"];
            $multiligne = $res["multiligne"];
        }

        if($matchDesindentationNextLine == 1){
            $indentation -= $space;
        }

        return ["array" => $array, "index" => $index, 'indentation' => $indentation, 'multiligne' => $multiligne];
    }

}
