<?php

namespace App\DataFixtures;

use App\Entity\Author;
use App\Entity\Book;
use App\Entity\Paper;
use App\Repository\AuthorRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    private function loadPaper(ObjectManager $manager, string $title, array $authors, int $year, ?string $doi = null, ?string $abstract = null): void
    {
        $paper = new Paper();
        $paper->setTitle($title);

        $authorRep = $manager->getRepository(Author::class);
        foreach ($authors as $author) {
            $paper->addAuthor($authorRep->getOrCreate($author));
        }
        $paper->setReleaseYear($year);

        if ($doi) $paper->setDoi($doi);
        if ($abstract) $paper->setAbstract($abstract);

        $manager->persist($paper);
    }

    private function loadBook(ObjectManager $manager, string $title, array $authors, int $year, ?string $isbn = null): void
    {
        $book = new Book();
        $book->setTitle($title);

        $authorRep = $manager->getRepository(Author::class);
        foreach ($authors as $author) {
            $book->addAuthor($authorRep->getOrCreate($author));
        }
        $book->setReleaseYear($year);

       if ($isbn) $book->setIsbn($isbn);

        $manager->persist($book);
    }

    public function load(ObjectManager $manager): void
    {
        // authors
        $berners_lee = 'Tim Berners-Lee';
        $ritchie = 'Dennis M. Ritchie';
        $kernighan = 'Brian Kernighan';
        $thompson = 'Ken Thompson';
        $wirth = 'Niklaus Wirth';
        $turing = 'A. M. Turing';
        $knuth = 'Donald E. Knuth';

        // load papers
        $this->loadPaper($manager, 'Information Management: A Proposal', [$berners_lee], 1990);
        $this->loadPaper($manager, 'The Development of the C Language', [$ritchie], 1996, '10.1145/234286.1057834');
        $this->loadPaper($manager, 'The UNIX Time-Sharing System', [$ritchie, $thompson], 1974, '10.1145/361011.361061');
        $this->loadPaper($manager, 'Reflection on Trusting Trust', [$thompson], 1995);
        $this->loadPaper($manager, 'A Plea for Lean Software', [$wirth], 1974, '10.1145/361011.361061');
        $this->loadPaper($manager, 'On Computable Numbers, with an Application to the Entscheidungsproblem', [$turing], 1936);
        $this->loadPaper($manager, 'On the Translation of Languages from Left to Right', [$knuth], 1965, null, 'There has been much recent interest in languages whose grammar is sufficiently simple that an efficient left-to-right parsing algorithm can be mechanically produced from the grammar. In this paper, we define LR(k) grammars, which are perhaps the most general ones of this type, and they provide the basis for understanding all of the special tricks which have been used in the construction of parsing algorithms for languages with simple structure, e.g. algebraic languages. We give algorithms for deciding if a given grammar satisfies the LR(k) condition, for given k, and also give methods for generating recognizers for LR(k) grammars. It is shown that the problem of whether or not a grammar is LR(k) for some k is undecidable, and the paper concludes by establishing various connections between LR(k) grammars and deterministic languages. In particular, the LR(k) condition is a natural analogue, for grammars, of the deterministic condition, for languages.');

        // load books
        $this->loadBook($manager, 'The C Programming Language', [$ritchie, $kernighan], 1978, '9780131101630');
        $this->loadBook($manager, 'Algorithms + Data Structures = Programs', [$wirth], 1976);

        $manager->flush();
    }
}
