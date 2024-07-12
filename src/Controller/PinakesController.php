<?php

namespace App\Controller;

use App\Repository\PinakesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

abstract class PinakesController extends AbstractController {

    protected EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    public function renderTable(array $data, PinakesRepository $repository, bool $allowAdd = true): Response {
        return $this->render('table.html.twig', [
            'repository' => $repository,
            'fields' => $repository->getFields(),
            'data' => $data,
            'allow_add' => $allowAdd
        ]);
    }

    public function renderTableContent(array $data, PinakesRepository $repository): Response {
        return $this->render('tablecontent.html.twig', [
            'repository' => $repository,
            'fields' => $repository->getFields(),
            'data' => $data,
        ]);
    }

    public function redirectHx(string $url): Response {
        return new Response(headers: [
            'HX-Redirect' => $url
        ]);
    }
}
