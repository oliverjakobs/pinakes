<?php declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

use Psr\Log\LoggerInterface;

class IndexController extends AbstractController {


    #[Route('/', name: 'pinakes')]
    public function index(): Response {

        // $client = HttpClient::create();
        // $response = $client->request('GET', 'https://openlibrary.org/isbn/0330258648.json');

        // return JsonResponse::fromJsonString($response->getContent());
        


        return $this->render('index.html.twig', [
            'results' => ''
        ]);
    }

    // #[Route('/login', name: 'app_login')]
    // public function login(AuthenticationUtils $authenticationUtils): Response {
    //     // get the login error if there is one
    //     $error = $authenticationUtils->getLastAuthenticationError();
    //
    //     // last username entered by the user
    //     $lastUsername = $authenticationUtils->getLastUsername();
    //
    //     return $this->render('login.html.twig', [
    //         'last_username' => $lastUsername,
    //         'error'         => $error,
    //     ]);
    // }
    
    #[Route('/search', name: 'pinakes_search')]
    public function search(Request $request, LoggerInterface $logger): Response {

        $client = HttpClient::create();

        // return JsonResponse::fromJsonString($response->getContent());

        $isbn = $request->get('isbn', '');
        $title = $request->get('title', '');
        $author = $request->get('author', '');


        $results = [];
        if (!empty($isbn)) {
            $response = $client->request('GET', 'https://openlibrary.org/isbn/' . str_replace('-', '', $isbn) . '.json?fields=key,title,author_name,editions');
            //$results[] = $response->toArray()['title'];
            $results[] = json_decode($response->getContent(), flags: JSON_OBJECT_AS_ARRAY);
        }

        // $query = [];
        // if (!empty($title)) $query[] = 'title:"' . $title . '"';
        // if (!empty($author)) $query[] = 'author:"' . $author . '"';

        // if (!empty($query)) {
        //     $query_str = urlencode(implode(' AND ', $query));
        //     $response = $client->request('GET', 'https://openlibrary.org/search.json?q=' . $query_str . '&fields=key,title,author_name,editions');
        //     $results[] = $response->getContent();
        // }

        return $this->render('searchresult.html.twig', [
            'results' => $results
        ]);
    }
}
