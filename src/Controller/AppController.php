<?php

namespace App\Controller;

use App\Entity\YamlFile;
use App\Form\YamlFileType;
use App\Service\YamlService;
use GuzzleHttp\Client;
use Statickidz\GoogleTranslate;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\VarDumper\VarDumper;

class AppController extends AbstractController
{
    #[Route('/', name: 'app_homepage')]
    public function index(Request $request, KernelInterface $kernel, YamlService $yamlService): Response
    {
        $yamlFile = new YamlFile();
        $form = $this->createForm(YamlFileType::class, $yamlFile);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){

            $file = $yamlFile->getFile();

            //check type form
            if($file instanceof UploadedFile){
                $response = $yamlService->uploadFile($file, $kernel);

                //check type dans nom fichier
                if($response['status'] == 200){
                    $yaml = $response['path'];
                    $arrayTrans = $yamlService->handleYaml($yaml, $yamlFile->getOriginalanguage(), $yamlFile->getTargetLanguage());
                    if(count($arrayTrans) < 300 /* && !$this->getUser() || $this->getUser() ---- limite de ligne pour les non-abonnées */){
                        $fileTranslated = $yamlService->generateTranslationFile($arrayTrans, $kernel);
                        if($fileTranslated){
                            return $this->file($fileTranslated);
                        }

                    }else{
                        $error = new FormError("Votre fichier dépasse le nombre de ligne maximum pour un compte basique. Passez à un abonnement premium pour un plus grand nombre de traduction !");
                        $form->get('file')->addError($error);
                    }
                }else{
                    $error = new FormError($response['message']);
                    $form->get('file')->addError($error);
                }
            }else{
                $error = new FormError('Format du fichier invalide.');
                $form->get('file')->addError($error);
            }
        }

        return $this->render('app/index.html.twig', [
            'controller_name' => 'AppController',
            'form' => $form->createView(),
        ]);
    }
}
