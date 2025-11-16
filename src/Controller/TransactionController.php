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

    #[Route('/transaction/show/{id}', name: 'transaction_show', methods: ['GET'])]
    public function show(Request $request, TransactionRepository $repository): Response {
        $transaction = $this->getEntity($request, $repository);

        return $this->renderShow($repository, $transaction, 'show', [
            'actions' => [
                $this->getActionEdit($transaction),
                $this->getActionDelete($transaction),
            ],
        ]);
    }

    #[Route('/transaction/delete/{id}', name: 'transaction_delete', methods: ['DELETE'])]
    public function delete(Request $request, TransactionRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);

        $transaction = $this->getEntity($request, $repository);
        $repository->delete($transaction);

        return $this->redirectToRoute('transaction');
    }

    #[Route('/transaction/form/{id}', name: 'transaction_form', methods: ['GET', 'POST'])]
    public function form(Request $request, TransactionRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);
        $transaction = $this->getEntity($request, $repository);

        if (Request::METHOD_POST === $request->getMethod()) {
            $transaction->reason = $request->request->get('reason');
            $transaction->amount = floatval($request->request->get('amount'));
            $transaction->timestamp = new \DateTime($request->request->get('timestamp'));

            $repository->save($transaction);
            return $this->redirectToRoute('transaction_show', [ 'id' => $transaction->getId() ]);
        }

        return $this->renderForm($repository, $transaction);
    }
}
