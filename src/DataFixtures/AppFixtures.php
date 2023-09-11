<?php

namespace App\DataFixtures;

use App\Entity\Author;
use App\Entity\Paper;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    private function loadPaper(ObjectManager $manager, string $title, array $authors, int $year, ?string $doi): void
    {
        $paper = new Paper();
        $paper->setTitle($title);

        foreach ($authors as $author)
        {
            $paper->addAuthor($author);
        }
        $paper->setReleaseYear($year);
        $paper->setDoi($doi);

        $manager->persist($paper);
    }

    public function load(ObjectManager $manager): void
    {
        // load authors
        $berners_lee = new Author();
        $berners_lee->setName("Tim Berners-Lee");
        $manager->persist($berners_lee);

        $rithchie = new Author();
        $rithchie->setName("Dennis M. Ritchie");
        $manager->persist($rithchie);

        $thompson = new Author();
        $thompson->setName("Ken Thompson");
        $manager->persist($thompson);

        $manager->flush();

        // load papers
        $this->loadPaper($manager, "Information Management: A Proposal", [$berners_lee], 1990, null);
        $this->loadPaper($manager, "The Development of the C Language", [$rithchie], 1996, "10.1145/234286.1057834");
        $this->loadPaper($manager, "The UNIX Time-Sharing System", [$rithchie, $thompson], 1974, "10.1145/361011.361061");

        $manager->flush();
    }
}
