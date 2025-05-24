<?php declare(strict_types=1);

namespace App\Twig;

use App\Entity\PinakesEntity;
use App\Repository\PinakesRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use App\Pinakes\Html;

class AppExtension extends AbstractExtension {

    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    public function getFunctions(): array {
        return [
            new TwigFunction('get_value', [$this, 'getValue']),
            new TwigFunction('get_form', [$this, 'getForm']),
        ];
    }

    private static function getData(array $field, PinakesEntity $entity): mixed {
        assert(isset($field['data']), 'No data specified');
        $data = $field['data'];

        if (is_callable($data)) {
            return $data($entity);
        }

        $name = 'get' . str_replace('_', '', ucwords($data, '_'));
        return $entity->{$name}();
    }

    public function getValue(array $field, PinakesEntity $entity): string {
        $data = self::getData($field, $entity);

        if (null === $data) return $field['default'] ?? '-';

        $link = $field['link'] ?? null;

        if ($data instanceof Collection) {
            assert(null === $link || PinakesRepository::LINK_DATA === $link, 'Collections can only link to data');

            return PinakesEntity::toHtmlList($data, null !== $link);
        }

        if (null === $link) return (string) $data;

        if (PinakesRepository::LINK_SELF === $link) {
            return $entity->getLinkSelf()->getHtml();
        }

        if (PinakesRepository::LINK_DATA === $link) {
            assert($data instanceof PinakesEntity, 'Can only link to entities');
            return $data->getLinkSelf()->getHtml();
        }

        throw new Exception('Unkown link type');
    }

    public function getForm(string $name, array $field, PinakesEntity $entity): array {
        $data = self::getData($field, $entity);

        if ($data instanceof Collection) {
            $repository = $this->em->getRepository($data->first()::class);
            return [
                'path' => '/component/autocomplete.html.twig',
                'name' => $name,
                'options' => $repository->getOptions(),
                'values' => $data,
            ];
        }

        if ($data instanceof PinakesEntity) {
            $repository = $this->em->getRepository($data::class);
            return [
                'path' => '/component/autocomplete.html.twig',
                'name' => $name,
                'options' => $repository->getOptions(),
                'values' => $data,
            ];
        }

        return [
            'path' => '/component/input.html.twig',
            'name' => $name,
            'type' =>'text',
            'value' => $data,
        ];
    }
}
