<?php

namespace App\Controller;


use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\UserType;
use Symfony\Component\HttpFoundation\Request;

class UserController extends AbstractController
{

    #[Route('/users', name: 'app_users')]
    public function listUsers(UserRepository $userRepository): Response
    { 

        $users = $userRepository->findAll();
        return $this->render('user/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/users/edit-user/{id}', name: 'app_user_edit')]
    public function editUser($id, Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository): Response
    {    
        $user = $id;
    
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_users');
        }
    
        return $this->render('user/edit.html.twig', [
            'userForm' => $form->createView(),
        ]);
    }

    #[Route('/users/delete-user/{id}', name: 'app_user_delete')]
    public function deleteUser($id, EntityManagerInterface $entityManager, UserRepository $userRepository): Response
    {   
        $user = $userRepository->find($id);

        $entityManager->remove($user);
        $entityManager->flush();

        return $this->redirectToRoute('app_users');

    }

    #[Route('/users/make-admin/{id}', name: 'make_admin')]
    public function makeAdmin($id, UserRepository $userRepository, EntityManagerInterface $em): Response
    {

        // Récupérez l'utilisateur à partir de la base de données
        $user = $userRepository->find($id);
    
        // Vérifiez si l'utilisateur existe
        if (!$user) {
            throw $this->createNotFoundException('Aucun utilisateur trouvé avec cet identifiant');
        }
    
        // Mettez à jour les rôles de l'utilisateur
        $user->setRoles(['ROLE_ADMIN']);
        $em->flush();
    
        // Redirigez vers la page de liste des utilisateurs
        return $this->redirectToRoute('app_users');
    }
}