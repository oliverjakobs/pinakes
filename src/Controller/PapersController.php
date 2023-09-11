<?php

namespace App\Controller;

use App\Entity\Author;
use App\Entity\Paper;
use App\Form\PaperFormType;
use App\Repository\PaperRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PapersController extends AbstractController
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[Route('/papers', name: 'papers', methods: ['GET'])]
    public function index(PaperRepository $repository): Response
    {
        return $this->render('papers.html.twig', [
            'papers' => $repository->findAll()
        ]);
    }

    #[Route('/papers/search', name: 'paper_search', methods: ['GET'])]
    public function search(Request $request, PaperRepository $repository): Response
    {
        $title = $request->get('search');
        return $this->render('paper_list.html.twig', [
            'papers' => $repository->findLikeTitle($title)
        ]);
    }

    #[Route('/papers/form', name: 'paper_form')]
    public function form(Request $request): Response
    {
        $paper = new Paper();
        $form = $this->createForm(PaperFormType::class, $paper);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $authorRep = $this->em->getRepository(Author::class);

            $authors = $form->get('authors')->getData();

            foreach (explode(';', $authors) as $name) {
                $author = $authorRep->findOneBy(['name' => $name]);
                if (is_null($author)) {
                    $author = new Author();
                    $author->setName($name);
                    $this->em->persist($author);
                }
                $paper->addAuthor($author);
            }

            $this->em->persist($paper);
            $this->em->flush();

            return $this->redirectToRoute('papers');
        }

        return $this->render('paper_form.html.twig', [
            'form' => $form
        ]);
    }

    #[Route('/papers/{id}', name: 'paper_detail', methods: ['GET'])]
    public function detail($id): Response
    {
        $repository = $this->em->getRepository(Paper::class);
        return $this->render('paper_detail.html.twig', [
            'paper' => $repository->find($id),
        ]);
    }
}
