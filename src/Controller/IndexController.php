<?php declare(strict_types=1);

namespace App\Controller;

use App\Mapper\BookMapper;
use App\Mapper\TransactionMapper;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class IndexController extends PinakesController {

    #[Route('/', name: 'pinakes')]
    public function index(): Response {

        $newest = BookMapper::getInstance()->createTable('newest');
        $newest->setOrder('created_at', 'DESC');
        $newest->setPP(5);
        $newest->allow_ordering = false;
        $newest->allow_pagination = false;

        return $this->render('index.html.twig', [
            'newest' => $newest,
            'balance' => TransactionMapper::getBalance()
        ]);
    }

    #[Route('/booksearch', name: 'booksearch', methods: ['GET'])]
    public function booksearch(): Response {
        return $this->render('booksearch.html.twig', [ ]);
    }
}
