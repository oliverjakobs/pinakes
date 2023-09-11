<?php

namespace App\Controller;

use App\Entity\Author;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AuthorsController extends AbstractController
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[Route('/authors', name: 'authors', methods: ['GET'])]
    public function index(): Response
    {
        $repository = $this->em->getRepository(Author::class);
        return $this->render('authors.html.twig', [
            'authors' => $repository->findAll()
        ]);
    }

    #[Route('/authors/{id}', name: 'author_detail', methods: ['GET'])]
    public function detail($id): Response
    {
        $repository = $this->em->getRepository(Author::class);
        return $this->render('author_detail.html.twig', [
            'author' => $repository->find($id),
        ]);
    }
}
