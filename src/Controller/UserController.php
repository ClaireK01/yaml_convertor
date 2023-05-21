<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/profile')]
class UserController extends AbstractController
{
    #[Route('/', name: 'app_user')]
    public function index(): Response
    {

        if(!$this->getUser()){
            $this->redirectToRoute('app_homepage');
        }
        return $this->render('user/index.html.twig', [

        ]);
    }
}
