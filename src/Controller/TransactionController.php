<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Transaction;
use App\Entity\User;
use App\Repository\TransactionRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TransactionController extends PinakesController {

    #[Route('/transaction', name: 'transaction', methods: ['GET'])]
    public function list(Request $request, TransactionRepository $repository): Response {
        return $this->renderListFilter($request, $repository, 'Transactions');
    }

    #[Route('/transaction/modal/{id}', name: 'transaction_modal', methods: ['GET', 'POST'])]
    public function modal(Request $request, TransactionRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);

        $transaction = $this->getEntity($request, $repository);

        if (Request::METHOD_POST === $request->getMethod()) {
            $this->updateFromRequest($request, $repository, $transaction);
            return $this->redirectToRoute('transaction');
        }

        return $this->renderModal($repository, $transaction);
    }

    #[Route('/transaction/delete/{id}', name: 'transaction_delete', methods: ['DELETE'])]
    public function delete(Request $request, TransactionRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);

        $transaction = $this->getEntity($request, $repository);
        $repository->delete($transaction);

        return $this->redirectToRoute('transaction');
    }
}
