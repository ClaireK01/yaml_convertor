<?php

namespace App\Controller;

use App\Entity\YamlFile;
use App\Form\YamlFileType;
use App\Service\YamlService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
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

        return $this->render('app/index.html.twig', [
            'controller_name' => 'AppController',
            'form' => $form->createView(),
        ]);
    }

//    //mise en place requete ajax pour loader
    #[Route('/process', name: 'app_yaml_process', methods: ['GET', 'POST'])]
    public function processYamlAction(Request $request, KernelInterface $kernel, YamlService $yamlService)
    {
        $yamlFile = new YamlFile();
        $form = $this->createForm(YamlFileType::class, $yamlFile);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $yamlService->setOrginal($yamlFile->getOriginalanguage());
            $yamlService->setTarget($yamlFile->getTargetLanguage());
            $yamlService->setSpace($yamlFile->getSpace());

            $file = $yamlFile->getFile();

            //check type form
            if($file instanceof UploadedFile){
                $response = $yamlService->uploadFile($file);

                //check type dans nom fichier
                if($response['status'] == 200){
                    $yaml = $response['path'];
                    try{
                        $arrayTrans = $yamlService->handleYaml($yaml, $yamlFile->getConcatenation());
                        if(count($arrayTrans) < 300 /* && !$this->getUser() || $this->getUser() ---- limite de ligne pour les non-abonnées */){
                            $fileTranslated = $yamlService->generateTranslationFile($arrayTrans, $kernel);
                            if($fileTranslated){
                                return $this->json(['file' => $fileTranslated], 200);
                            }
                        }else{
                            $error = new FormError("Votre fichier dépasse le nombre de ligne maximum pour un compte basique. Passez à un abonnement premium pour un plus grand nombre de traduction !");
                            $form->get('file')->addError($error);
                        }
                    }
                    catch(\Exception $e){
                        if($e instanceof ClientException){
                            return $this->json(['message' => "Une erreur est survenue lors du traitement de votre fichier (Usage max. API dépassé). Veuillez réesayer plus tard"], 500);
                        }else{
                            return $this->json(['message' => "Une erreur est survenue lors du traitement de votre fichier. Veuillez réesayer plus tard."], 500);
                        }
                    }
                }else{
                    return $this->json(['message' => $response['message'] ], 500);
                }
            }
        }

        return $this->json(['message' => 'Une erreur est survenue', 't1' => $form->isSubmitted(), 't2' => $form->isSubmitted() && $form->isValid()], 500);
    }

    #[Route('/yaml/download', name: 'app_yaml_download', methods: 'POST')]
    public function downloadFileAction(Request $request, KernelInterface $kernel, YamlService $yamlService)
    {
        $file = $request->request->get('file');

        return $this->render('app/ready.html.twig', ['file' => $file]);
    }
}
