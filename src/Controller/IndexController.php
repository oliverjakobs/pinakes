<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Book;
use App\Entity\Transaction;
use App\Mapper\PinakesMapper;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class IndexController extends PinakesController {

    #[Route('/', name: 'pinakes')]
    public function index(): Response {

        $newest = PinakesMapper::getMapper(Book::class)->createTable('newest');
        $newest->setOrder('created_at', 'DESC');
        $newest->setPP(5);
        $newest->allow_ordering = false;
        $newest->allow_pagination = false;
        
        // $newest = DataTable::fromData($books->getNewest(), $books->getDataFields('newest'));
        // // $newest = new DataTable($books, $books->getDataFields('newest'));
        // $newest->allow_ordering = false;
        // $newest->allow_pagination = false;
        // $newest->use_new = true;

        return $this->render('index.html.twig', [
            'newest' => $newest,
            'balance' => Transaction::getBalance()
        ]);
    }

    #[Route('/booksearch', name: 'booksearch', methods: ['GET'])]
    public function booksearch(): Response {
        return $this->render('booksearch.html.twig', [ ]);
    }
}
