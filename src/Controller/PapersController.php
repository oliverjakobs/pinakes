<?php

namespace App\Controller;

use App\Entity\Author;
use App\Entity\Paper;
use App\Form\PaperFormType;
use App\Repository\PaperRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PapersController extends PinakesController {

    protected function getName(): string {
        return 'papers';
    }

    #[Route('/papers', name: 'papers', methods: ['GET'])]
    public function index(PaperRepository $repository): Response {
        return $this->renderTable($repository->findAll(), $repository->getFields());
    }

    #[Route('/papers/search', name: 'paper_search', methods: ['GET'])]
    public function search(Request $request, PaperRepository $repository): Response {
        $title = $request->get('search');
        return $this->renderTableContent($repository->findLikeTitle($title), $repository->getFields());
    }

    #[Route('/papers/{id}', name: 'paper_show', methods: ['GET'])]
    public function show($id, PaperRepository $repository): Response {
        return $this->render('/papers/show.html.twig', [
            'paper' => $repository->find($id),
        ]);
    }
    
    #[Route('/papers/{id}', name: 'paper_delete', methods: ['DELETE'])]
    public function delete($id, PaperRepository $repository): Response {
        $paper = $repository->find($id);
        $repository->delete($paper);

        return $this->redirectHx('/papers');
    }

    private function handleForm(Paper $paper, Request $request, PaperRepository $repository): Response
    {
        $form = $this->createForm(PaperFormType::class, $paper);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $authorRep = $this->em->getRepository(Author::class);

            $authors = $form->get('authors')->getData();

            foreach (explode(';', $authors) as $name) {
                $author = $authorRep->getOrCreate($name);
                $paper->addAuthor($author);
            }

            $repository->save($paper);
            return $this->redirectToRoute('papers');
        }

        return $this->render('/papers/form.html.twig', [
            'form' => $form,
            'paper' => $paper
        ]);
    }

    #[Route('/papers/form', name: 'paper_add')]
    public function add(Request $request, PaperRepository $repository): Response
    {
        $paper = new Paper();
        return $this->handleForm($paper, $request, $repository);
    }
    
    #[Route('/papers/form/{id}', name: 'paper_edit')]
    public function edit($id, Request $request, PaperRepository $repository): Response
    {
        $paper = $repository->find($id);
        return $this->handleForm($paper, $request, $repository);
    }
}
