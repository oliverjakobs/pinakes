<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Transaction;
use App\Repository\TransactionRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BookfundController extends PinakesController {

    public static function getModelName(): string {
        return 'transaction';
    }

    #[Route('/bookfund', name: 'bookfund', methods: ['GET'])]
    public function bookfund(TransactionRepository $repository): Response {
        return $this->render('bookfund.html.twig', [
            'name' => static::getModelName(),
            'transactions' => $repository->findAll(['timestamp' => 'desc'], 6),
            'balance' => $repository->getBalance()
        ]);
    }

    #[Route('/bookfund/modal/{type}', name: 'bookfund_modal', methods: ['GET', 'POST'])]
    public function modal(Request $request, string $type, TransactionRepository $repository): Response {

        if ($request->getMethod() === Request::METHOD_POST) {
            $amount = floatval($request->request->get('amount'));
            if ($type === 'withdrawal') $amount *= -1.0;

            $reason = $request->request->get('reason');

            $transaction = new Transaction();
            $transaction->amount = $amount;
            $transaction->reason = $reason;
            $transaction->timestamp = new \DateTime();

            $repository->save($transaction);

            return $this->redirectHx('bookfund');
        }

        return $this->render('modal.html.twig', [
            'type' => $type
        ]);
    }

    #[Route('/transaction', name: 'transaction', methods: ['GET'])]
    public function list(Request $request, TransactionRepository $repository): Response {
        return $this->renderList($request);
    }

    #[Route('/transaction/filter', name: 'transaction_filter', methods: ['GET'])]
    public function filter(Request $request, TransactionRepository $repository): Response {
        return $this->renderFilter($request, $repository);
    }

    #[Route('/transaction/show/{id}', name: 'transaction_show', methods: ['GET'])]
    public function show(Request $request, TransactionRepository $repository): Response {
        return $this->render('show.html.twig', [
            'name' => self::getModelName(),
            'entity' => $this->getEntity($request, $repository),
            'fields' => $repository->getDataFields('list'),
        ]);
    }
}
