<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

abstract class PinakesController extends AbstractController {

    protected EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    abstract protected function getName(): string;

    public function renderTable(array $data, array $fields, bool $allowAdd = true): Response {
        return $this->render('table.html.twig', [
            'name' => $this->getName(),
            'fields' => $fields,
            'data' => $data,
            'allow_add' => $allowAdd
        ]);
    }

    public function renderTableContent(array $data, array $fields): Response {
        return $this->render('tablecontent.html.twig', [
            'name' => $this->getName(),
            'fields' => $fields,
            'data' => $data,
        ]);
    }

    public function redirectHx(string $url): Response {
        return new Response(headers: [
            'HX-Redirect' => $url
        ]);
    }
}
