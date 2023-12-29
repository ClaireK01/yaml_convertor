<?php

namespace App\Service;

use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\VarDumper\VarDumper;

class YamlService{

    public  function __construct(){}


    public function handleYaml(string $path, string $original, string $target){

        $yaml = file_get_contents($path);
        $array = explode("\n", $yaml);
        $arrayTrans = [];

        $key = null;
        $multiline = false;

        foreach ($array as $line){

            $word = $multiline ? [$line] : explode(':', $line);
            $word = str_replace(["\r"], " ", $word);
//            $word = str_replace(" ", "->", $word);
            $word = preg_replace("#^[\s.]+#", "->", $word);

            if((key_exists(1, $word) && !ctype_space($word[1]) && $word[1] != "->") ||  (!key_exists(1, $word) && $multiline) ){

                if(!$multiline){
                    $multiline = str_contains($word[1], "|");
                    $wordToTranslate = $word[1];
                    preg_match("#^[->.]+#", $word[1], $matches);
                }else{
                    $wordToTranslate = $word[0];
                    $matchReg = preg_match("#^[->.]+#", $word[0], $matches);
                    if($matchReg == 0){
                        $multiline = false;
                    }
                }
                $indentation = count($matches) > 0 ? $matches[0] : '';

                if($wordToTranslate != "" && $wordToTranslate != "->" && !ctype_space($wordToTranslate) ){

                    $json = $this->getTransaltion(str_replace('->', '', $wordToTranslate), $original, $target);
                    $trans = null;

                    //faire un array reduce ou un truc du genre?
                    foreach ($json->matches as $m){
                        if($trans == null){
                            $trans = $m;
                        }else if($trans instanceof \stdClass && $m->match > $trans->match){
                            $m->match = $trans->match;
                            $trans = $m;
                        }
                    }

                    if(!$multiline || ( key_exists(1, $word) && $multiline && str_contains($word[1], "|") ) ){
                        $key = $word[0];
                        $arrayTrans[] = $key . " : " . $trans->translation;
                    }else{
                        $arrayTrans[] = $indentation. $trans->translation;
                    }
                }

            }else{
                if($word[0] != "" && !ctype_space($word[0]) && $word[0] != "->"){
                    $arrayTrans[] = $word[0] . " : " ;
                }
            }
        }

        return  str_replace('->', ' ', $arrayTrans);
    }
    
    public function handleYaml2(string $path, string $original, string $target){
        $yaml = file_get_contents($path);
        $array = explode("\n",$yaml);
        $array = str_replace(" ", '->', $array);
        $i = 0;
        $arrayTrans = [];

        $res = $this->getBlock($array, $i, 0);
        foreach($res['arr'] as $b){
            $arrayTrans[] = $this->getArrayFromBlockValue($b);
        }

        die(VarDumper::dump($arrayTrans));

        die(VarDumper::dump($res));

    }
    
    public function getBlock($array, $index, $indentation){
        
/*        die(VarDumper::dump($array));*/
        $pause = false;
        $bloc = [];
        $bloc[] = $array[$index];
        $index++;
        $arrows = str_repeat("->", $indentation + 1);
        do{
            $nbMatches = preg_match("#^[ ->.]{".($indentation + 1 ).",}#", $array[$index]);
            //faire passer l'indentation (actuellemnt a 0) en param
            if($nbMatches == 1 || $array[$index] == "\r"){
                $bloc[] = $array[$index];
                $index++;
            }else{
                $pause = true;
            }
        }while(!$pause);
        
        return ['index' => $index, 'arr' => $bloc];
    }

    public function getArrayFromBlockValue($string){
        $string =  preg_replace("#^[->.]+#", "", $string);
        //prendre seulement le premier :
        $arr = explode(':', $string);
        return $arr;
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
            if (file_exists($src . $name)) {
                $name = $now->format('U') . '-' . $name;
                $file->move($src, $name);

                return ["status" => 200, 'path' => $src.$name];
            }
        }

        return ["status" => 500, 'message' => "Format non valide. Les formats accepté sont : yaml, yml"];

    }

    public function getTransaltion($word, $original, $target){

        $url = 'https://api.mymemory.translated.net/';
        $fullUrl = $url . 'get?q='.$word.'&langpair=' . $original. '|' . $target ;
        $client = new Client([
            'verify' => 'C:\Program Files\Common Files\SSL\cert.pem',
            'base_uri' => $url,
            'timeout' => 2.0,
        ]);

        $output = $client->get($fullUrl)->getBody()->getContents();

        return json_decode($output);
    }


}