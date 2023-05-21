<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/profile')]
class UserController extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $em){
        $this->em = $em;
    }

    #[Route('/', name: 'app_user')]
    public function index(Request $request): Response
    {
        $user = $this->getUser();
        if(!$user){
            $this->redirectToRoute('app_homepage');
        }
        $form = $this->createForm('App\Form\UserType', $user);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $this->em->persist($user);
            $this->em->flush();
        }

        return $this->render('user/index.html.twig', [
            'form'=>$form->createView(),
        ]);
    }

}
