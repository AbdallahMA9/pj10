<?php

namespace App\Controller;

use App\Repository\ProjectRepository;
use App\Repository\UserRepository;
use App\Repository\StatutRepository;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Task;
use App\Entity\Project;
use App\Form\TaskType;
use App\Form\ProjectType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class HomeController extends AbstractController
{
    #[IsGranted("ROLE_USER")]
    #[Route('/', name: 'app_home')]
    public function index(ProjectRepository $projectRepository): Response
    {
    
        // Récupérer l'utilisateur connecté
        $user = $this->getUser();
    
        // Vérifier si l'utilisateur est admin
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            // Si l'utilisateur est admin, il peut voir tous les projets
            $projects = $projectRepository->findAll();
        } else {
            // Si l'utilisateur n'est pas admin, il ne voit que les projets auxquels il est associé
            $projects = $projectRepository->findByUser($user);
        }
    
        return $this->render('home/index.html.twig', [
            'projects' => $projects,
        ]);
    }
    
    #[IsGranted("ROLE_ADMIN")]
    #[Route('/add-project', name: 'app_project_add')]
    public function addProject(Request $request, EntityManagerInterface $entityManager, ProjectRepository $projectRepository): Response
    {
        $project = new Project();
        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $project->setStartedAt(new \DateTimeImmutable());
            $project->setArchive(0);
            $entityManager->persist($project);
            $entityManager->flush();
            return $this->redirectToRoute('app_home');
        }

        return $this->render('home/addProject.html.twig', [
            'projectForm' => $form->createView()
        ]);
    }

    #[IsGranted("ROLE_ADMIN")]
    #[Route('/edit-project/{id}', name: 'app_project_edit')]
    public function editProject($id, Request $request, EntityManagerInterface $entityManager, ProjectRepository $projectRepository): Response
    {    
        $project = $projectRepository->find($id);
    
        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // Pas besoin de persister une entité existante
            $entityManager->flush();
    
            return $this->redirectToRoute('app_home');
        }
    
        return $this->render('home/editProject.html.twig', [
            'projectForm' => $form->createView(),
            'project' => $project
        ]);
    }

    #[IsGranted("ROLE_ADMIN")]
    #[Route('/delete-project/{id}', name: 'app_project_delete')]
    public function deleteProject($id, EntityManagerInterface $entityManager, ProjectRepository $projectRepository): Response
    {    
        $project = $projectRepository->find($id);

        $entityManager->remove($project);
        $entityManager->flush();

        return $this->redirectToRoute('app_home');

    }

    #[IsGranted("ROLE_USER")]
    #[Route('/project/{id}', name: 'app_detail')]
    public function projectDetail($id, StatutRepository $statutRepository, TaskRepository $taskRepository, ProjectRepository $projectRepository ): Response
    { 

        $project = $projectRepository->find($id);        
        $statuts = $statutRepository->findAll();
        $tasks = $taskRepository->findBy(['projectId' => $project]);

        return $this->render('home/project.html.twig', [
            'tasks' => $tasks,
            'project' => $project,
            'statuts' => $statuts,

        ]);

    }

    #[IsGranted("ROLE_USER")]
    #[Route('/add-task/{id}', name: 'app_task_add')]
    public function addTask(Project $Id, Request $request, EntityManagerInterface $entityManager, TaskRepository $taskRepository, ProjectRepository $projectRepository): Response
    {
        $task = new Task();
        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);
        
        
        $project = $Id;
        

        if ($form->isSubmitted() && $form->isValid()) {

            $task->setProjectId($project);

            
            $entityManager->persist($task);
            $entityManager->flush();

            return $this->redirectToRoute('app_home');
        }

        return $this->render('home/addTask.html.twig', [
            'taskForm' => $form->createView(),
            'project' =>$project,
        ]);
    }

    #[IsGranted("ROLE_ADMIN")]
    #[Route('/edit-task/{id}', name: 'app_task_edit')]
    public function editTask($id, Request $request, EntityManagerInterface $entityManager, TaskRepository $taskRepository): Response
    {    
        $task = $taskRepository->find($id);
    
        if (!$task) {
            throw $this->createNotFoundException('No task found for id '.$id);
        }
    
        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // Pas besoin de persister une entité existante
            $entityManager->flush();
    
            return $this->redirectToRoute('app_home');
        }
    
        return $this->render('home/editTask.html.twig', [
            'taskForm' => $form->createView(),
            'task' => $task,
        ]);
    }
    
    #[IsGranted("ROLE_ADMIN")]
    #[Route('/delete-task/{id}', name: 'app_task_delete')]
    public function deleteTask($id, EntityManagerInterface $entityManager, TaskRepository $taskRepository): Response
    {    
        $task = $taskRepository->find($id);
        $ProjectId = $task->getProjectId();
        $idP = $ProjectId->getId();

        $entityManager->remove($task);
        $entityManager->flush();

        return $this->redirectToRoute('app_detail', ['id' => $idP]);

    }
}
