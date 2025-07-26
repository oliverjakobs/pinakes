<?php declare(strict_types=1);

namespace App\Pinakes;

use App\Entity\PinakesEntity;
use App\Repository\PinakesRepository;
use Doctrine\ORM\EntityManagerInterface;

class DataType {
    public function renderValue(PinakesEntity $entity, mixed $data, mixed $link): string {
        $value = (string) $data;

        if (PinakesRepository::LINK_SELF === $link) {
            return $entity->getLinkSelf($value)->getHtml();
        }

        if (PinakesRepository::LINK_DATA === $link) {
            assert($data instanceof PinakesEntity, 'Can only link to entities');
            return $data->getLinkSelf($value)->getHtml();
        }

        assert(null === $link, 'Unkown link type');
        return $value;
    }
}
