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

    private function getFields(PinakesRepository $repository, string $fields): array {
        $func = 'getDataFields' . ucwords($fields);

        assert(method_exists($repository, $func), $func . ' missing for ' . $repository::class);
        return $repository->$func();
    }

    public function renderTable(array $data, PinakesRepository $repository, string $fields): Response {
        return $this->render('table.html.twig', [
            'repository' => $repository,
            'fields' => $this->getFields($repository, $fields),
            'data' => $data,
            'allow_add' => false
        ]);
    }

    public function renderTableContent(array $data, PinakesRepository $repository, string $fields): Response {
        return $this->render('tablecontent.html.twig', [
            'repository' => $repository,
            'fields' => $this->getFields($repository, $fields),
            'data' => $data,
        ]);
    }

    public function redirectHx(string $url): Response {
        return new Response(headers: [
            'HX-Redirect' => $url
        ]);
    }
}
