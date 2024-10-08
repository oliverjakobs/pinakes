<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Author;
use App\Entity\Paper;
use App\Form\PaperFormType;
use App\Repository\PaperRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PaperController extends PinakesController {

    #[Route('/paper', name: 'paper_list', methods: ['GET'])]
    public function index(PaperRepository $repository): Response {
        return $this->renderTable($repository, 'list');
    }

    #[Route('/paper/filter', name: 'paper_filter', methods: ['GET'])]
    public function filter(Request $request, PaperRepository $repository): Response {
        return $this->renderTablecontent($repository, 'list', $this->parseOptions($request));
    }

    #[Route('/paper/{id}', name: 'paper_show', methods: ['GET'])]
    public function show(int $id, PaperRepository $repository): Response {
        return $this->renderShow($repository, $id, 'show');
    }
    
    #[Route('/paper/{id}', name: 'paper_delete', methods: ['DELETE'])]
    public function delete(int $id, PaperRepository $repository): Response {
        $repository->delete($repository->find($id));
        return $this->redirectHx('paper_list');
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

    #[Route('/paper/form', name: 'paper_add')]
    public function add(Request $request, PaperRepository $repository): Response
    {
        $paper = new Paper();
        return $this->handleForm($paper, $request, $repository);
    }
    
    #[Route('/paper/form/{id}', name: 'paper_edit')]
    public function edit($id, Request $request, PaperRepository $repository): Response
    {
        $paper = $repository->find($id);
        return $this->handleForm($paper, $request, $repository);
    }
}
