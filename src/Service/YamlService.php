<?php

namespace App\Service;

use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\VarDumper\VarDumper;

class YamlService{

    private $kernel;

    public  function __construct(KernelInterface $kernel){
        $this->kernel = $kernel;
    }


//    public function handleYaml(string $path, string $original, string $target){
//
//        $yaml = file_get_contents($path);
//        $array = explode("\n", $yaml);
//        $arrayTrans = [];
//
//        $key = null;
//        $multiline = false;
//
//        foreach ($array as $line){
//
//            $word = $multiline ? [$line] : explode(':', $line);
//            $word = str_replace(["\r"], " ", $word);
////            $word = str_replace(" ", "->", $word);
//            $word = preg_replace("#^[\s.]+#", "->", $word);
//
//            if((key_exists(1, $word) && !ctype_space($word[1]) && $word[1] != "->") ||  (!key_exists(1, $word) && $multiline) ){
//
//                if(!$multiline){
//                    $multiline = str_contains($word[1], "|");
//                    $wordToTranslate = $word[1];
//                    preg_match("#^[->.]+#", $word[1], $matches);
//                }else{
//                    $wordToTranslate = $word[0];
//                    $matchReg = preg_match("#^[->.]+#", $word[0], $matches);
//                    if($matchReg == 0){
//                        $multiline = false;
//                    }
//                }
//                $indentation = count($matches) > 0 ? $matches[0] : '';
//
//                if($wordToTranslate != "" && $wordToTranslate != "->" && !ctype_space($wordToTranslate) ){
//
//                    $json = $this->getTransaltion(str_replace('->', '', $wordToTranslate), $original, $target);
//                    $trans = null;
//
//                    //faire un array reduce ou un truc du genre?
//                    foreach ($json->matches as $m){
//                        if($trans == null){
//                            $trans = $m;
//                        }else if($trans instanceof \stdClass && $m->match > $trans->match){
//                            $m->match = $trans->match;
//                            $trans = $m;
//                        }
//                    }
//
//                    if(!$multiline || ( key_exists(1, $word) && $multiline && str_contains($word[1], "|") ) ){
//                        $key = $word[0];
//                        $arrayTrans[] = $key . " : " . $trans->translation;
//                    }else{
//                        $arrayTrans[] = $indentation. $trans->translation;
//                    }
//                }
//
//            }else{
//                if($word[0] != "" && !ctype_space($word[0]) && $word[0] != "->"){
//                    $arrayTrans[] = $word[0] . " : " ;
//                }
//            }
//        }
//
//        return  str_replace('->', ' ', $arrayTrans);
//    }
    
    public function handleYaml(string $path, string $original, string $target){
        $yaml = file_get_contents($path);
        $arrayYaml = explode("\n",$yaml);
        $arrayYaml = preg_replace("#(?<!\S.)( )#", '->', $arrayYaml);
        $i = 0;
        $arrayTrans = [];

        $arrayTrans = $this->processYaml($arrayTrans, $i, 0, $arrayYaml)["array"];

        die(VarDumper::dump($arrayTrans));

    }

    public function processYaml($array, $index, $indentation, $yaml, $multiligne = false, $multi = null){
        //recuperation ligne actu, prec et suiv
        $prev = key_exists($index - 1, $yaml) ? $yaml[$index - 1] : null;
        $ligne = $yaml[$index];
        $next = key_exists($index + 1, $yaml) ? $yaml[$index + 1] : null;

        //check si indentation
        $matchIndentationNextLine = preg_match("#^(?<!->)(->){". $indentation + 2 .",}(?!->)#", $next);
        $matchDesindentationNextLine =  preg_match("#^(?<!->)(->){0,". $indentation - 2 ."}(?!->)#", $next, $match);

        if(!ctype_space($ligne) && $ligne != "\r" && $ligne != ""){
            $trans = $multiligne ? [$ligne] : preg_split("#(:)#", $ligne, 2);
            $word = $multiligne ? $trans[0] : $trans[1];
        }else{
            $trans = [$ligne, $ligne];
            $word = $trans[0] ;
        }

        if($matchIndentationNextLine == 1) {
            $indentation += 2;
            $index++;
            if(!$multiligne && str_replace([" ", "\s", "\r"], "", $trans[1]) == "|"){
                $multiligne = true;
            }elseif($multiligne && $matchDesindentationNextLine == 1){
                $multiligne = false;
            }

            $res = $this->processYaml($trans, $index, $indentation, $yaml, $multiligne);
            $trans = $res["array"];
            $index = $res['index'];
            $indentation = $res["indentation"];
            $multiligne = $res["multiligne"];
            $array[] = $trans;

        }else{
            if(!ctype_space($word) && $word != "\r"){
                $translated = $this->getTransaltion(str_replace('->', " ", $word), "FR", "EN");
                if(!$multiligne){
                    $trans[1] = $translated;
                }else{
                    $trans[0] = $translated;
                }
                //multiligne pr prochaine
                if(!$multiligne && str_replace(["\w", "->", "\r"], "", $trans[1]) == "|"){
                    $multiligne = true;
                }elseif($multiligne && $matchDesindentationNextLine == 1){
                    $multiligne = false;
                }
                $array[] = $trans;
                $index++;
            }else{
                $array[] = $trans;
                $index++;
            }
        }

        if(key_exists($index, $yaml) && $matchDesindentationNextLine == 0) {
            $res = $this->processYaml($array, $index, $indentation, $yaml, $multiligne);
            $index = $res["index"];
            $array = $res["array"];
            $indentation = $res["indentation"];
            $multiligne = $res["multiligne"];
        }

        if($matchDesindentationNextLine == 1){
            $indentation -= 2;
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
            foreach ($data as $ligne) {
                fwrite($stream, $ligne);
                fwrite($stream, "\n");
            }
            fclose($stream);

            return $file . $name;
        }

        return false;
    }


    public function uploadFile($file, KernelInterface $kernel){
        $now = new \DateTime();
        $allowed = array('yaml', 'yml');
        $dir = 'uploads/';
        $src = $kernel->getProjectDir() . '/public/' . $dir;

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
//            if (file_exists($src . $name)) {
                $name = $now->format('U') . '-' . $name;
                $file->move($src, $name);

                return ["status" => 200, 'path' => $src.$name];
//            }
        }

        return ["status" => 500, 'message' => "Format non valide. Les formats accepté sont : yaml, yml"];

    }

    public function getTransaltion($word, $original, $target){


        if($word != ""){
            $pem = $this->kernel->getProjectDir() . '\cacert.pem';

            $url = 'https://api.mymemory.translated.net/';
            $fullUrl = $url . 'get?q='.$word.'&langpair=' . $original. '|' . $target ;
            $client = new Client([
                'verify' => $pem,
                'base_uri' => $url,
                'timeout' => 2.0,
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


}