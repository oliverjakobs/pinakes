<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\TransactionRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TransactionController extends PinakesController {

    #[Route('/transaction', name: 'transaction', methods: ['GET'])]
    public function list(Request $request, TransactionRepository $repository): Response {
        return $this->renderList($request, 'Transactions', $repository->createTable());
    }

    #[Route('/transaction/modal/{id}', name: 'transaction_modal', methods: ['GET', 'POST'])]
    public function modal(Request $request, TransactionRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);
        return $this->renderModal($request, $repository, 'transaction');
    }

    #[Route('/transaction/delete/{id}', name: 'transaction_delete', methods: ['DELETE'])]
    public function delete(Request $request, TransactionRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);
        return $this->deleteEntityAndRedirect($request, $repository, 'transaction');
    }
}
