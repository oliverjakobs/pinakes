<?php declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Author;
use App\Entity\Book;
use App\Entity\Publisher;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    private function loadBooks(ObjectManager $manager): void {
        $author_rep = $manager->getRepository(Author::class);
        $publisher_rep = $manager->getRepository(Publisher::class);

        $row = 0;
        if (($handle = fopen("books.csv", "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (0 === $row++) continue; // skip header

                $title = $data[0];
                $authors = $data[1];
                $isbn = $data[2];
                $publisher = $data[3];
                $year_published = $data[4];
                $first_published = $data[5];

                $book = new Book();
                $book->setTitle($title);
                $book->setPublisher($publisher_rep->getOrCreate($publisher));
                $book->setPublished(empty($year_published) ? null : intval($year_published));
                $book->setFirstPublished(empty($first_published) ? null : intval($first_published));
                $book->setIsbn($isbn);

                $manager->persist($book);

                foreach (explode(';', $authors) as $author) {
                    $author = trim($author);
                    if (empty($author)) continue;
                    $book->addAuthor($author_rep->getOrCreate($author));
                }

                $manager->persist($book);

                //if ($row > 60) break;
            }
            fclose($handle);
        }

        $manager->flush();

    }

    public function load(ObjectManager $manager): void {
        $this->loadBooks($manager);

        $user = new User();
        $user->setUsername('admin');
        $user->setRoles([User::ROLE_ADMIN]);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'pinakes'));

        $manager->persist($user);

        $user = new User();
        $user->setUsername('librarian');
        $user->setRoles([User::ROLE_LIBRARIAN]);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'pinakes'));

        $manager->persist($user);

        $manager->flush();
    }
}
