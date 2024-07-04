<?php

namespace App\Controller;

use App\Entity\Author;
use App\Entity\Paper;
use App\Form\PaperFormType;
use App\Repository\PaperRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PapersController extends PinakesController {

    private function get_fields(): array {
        return [
            array(
                'name' => 'title',
                'caption' => 'Title',
                'link' => fn(Paper $p) => '/papers/' . $p->id,
            ),
            array(
                'name' => 'authors',
                'caption' => 'Author(s)',
            ),
            array(
                'name' => 'releaseYear',
                'caption' => 'Release Year',
            ),
            array(
                'name' => 'doi',
                'caption' => 'DOI',
                'default' => '-'
            ),
        ];
    }

    protected function getName(): string {
        return 'papers';
    }

    #[Route('/papers', name: 'papers', methods: ['GET'])]
    public function index(PaperRepository $repository): Response {
        return $this->renderTable($repository->findAll(), $this->get_fields());
    }

    #[Route('/papers/search', name: 'paper_search', methods: ['GET'])]
    public function search(Request $request, PaperRepository $repository): Response {
        $title = $request->get('search');
        return $this->renderTableContent($repository->findLikeTitle($title), $this->get_fields());
    }

    private function handleForm(Paper $paper, Request $request): Response
    {
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

        return $this->render('/papers/form.html.twig', [
            'form' => $form,
            'paper' => $paper
        ]);
    }

    #[Route('/papers/form', name: 'paper_add')]
    public function add(Request $request): Response
    {
        $paper = new Paper();
        return $this->handleForm($paper, $request);
    }
    
    #[Route('/papers/form/{id}', name: 'paper_edit')]
    public function edit($id, Request $request, PaperRepository $repository): Response
    {
        $paper = $repository->find($id);
        return $this->handleForm($paper, $request);
    }
    
    #[Route('/papers/{id}', name: 'paper_show', methods: ['GET'])]
    public function show($id, PaperRepository $repository): Response
    {
        return $this->render('/papers/show.html.twig', [
            'paper' => $repository->find($id),
        ]);
    }
    
    #[Route('/papers/{id}', name: 'paper_delete', methods: ['DELETE'])]
    public function delete($id, PaperRepository $repository, EntityManagerInterface $em): Response
    {
        $paper = $repository->find($id);
        $em->remove($paper);
        $em->flush();

        return new Response(headers: [
            'HX-Redirect' => '/papers'
        ]);
    }
}
