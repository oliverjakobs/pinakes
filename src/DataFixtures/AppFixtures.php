<?php declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Author;
use App\Entity\Book;
use App\Entity\Publisher;
use App\Entity\Series;
use App\Entity\Tag;
use App\Entity\Transaction;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\Collections\ArrayCollection;
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
        $series_rep = $manager->getRepository(Series::class);
        $tag_rep = $manager->getRepository(Tag::class);

        $row = 0;
        if (($handle = fopen("books.csv", "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (0 === $row++) continue; // skip header

                $created_at = $data[0];
                $title = $data[1];
                $author_names = $data[2];
                $publisher = $data[3];
                $tag_names = $data[4];
                $year_published = $data[5];
                $first_published = $data[6];
                $isbn = $data[7];
                $series = $data[8];
                $series_volume = $data[9];

                $book = new Book();
                $book->created_at = \DateTime::createFromFormat('d.m.Y', $created_at);
                $book->title = $title;
                $book->publisher = empty($publisher) ? null : $publisher_rep->getOrCreate($publisher);
                $book->published = empty($year_published) ? null : intval($year_published);
                $book->first_published = empty($first_published) ? null : intval($first_published);
                $book->isbn = $isbn;
                $book->series = empty($series) ? null : $series_rep->getOrCreate($series);
                $book->series_volume = empty($series_volume) ? null : intval($series_volume);

                $manager->persist($book);

                $authors = [];
                foreach (explode(';', $author_names) as $author) {
                    $author = trim($author);
                    if (empty($author)) continue;
                    $authors[] = $author_rep->getOrCreate($author);
                }
                $book->authors = new ArrayCollection($authors);
                $manager->persist($book);

                $tags = [];
                foreach (explode(';', $tag_names) as $tag) {
                    $tag = trim($tag);
                    if (empty($tag)) continue;
                    $tags[] = $tag_rep->getOrCreate($tag);
                }
                $book->tags = new ArrayCollection($tags);
                $manager->persist($book);

                //if ($row > 60) break;
            }
            fclose($handle);
        }

        $manager->flush();

    }

    private function loadTransactions(ObjectManager $manager): void {
        $rep = $manager->getRepository(Transaction::class);

        $row = 0;
        if (($handle = fopen("bookfund.csv", "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (0 === $row++) continue; // skip header

                $amount = $data[1];
                $reason = $data[2];
                $timestamp = $data[3];

                $transaction = new Transaction();
                $transaction->amount = empty($amount) ? null : floatval($amount);
                $transaction->reason = $reason;
                $transaction->timestamp = new \DateTime($timestamp);

                $manager->persist($transaction);
            }
            fclose($handle);
        }

        $manager->flush();
    }

    public function load(ObjectManager $manager): void {
        $this->loadBooks($manager);
        $this->loadTransactions($manager);

        $user = new User();
        $user->username = 'admin';
        $user->setRoles([User::ROLE_ADMIN]);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'pinakes'));

        $manager->persist($user);

        $user = new User();
        $user->username = 'librarian';
        $user->setRoles([User::ROLE_LIBRARIAN]);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'pinakes'));

        $manager->persist($user);

        $manager->flush();
    }
}
