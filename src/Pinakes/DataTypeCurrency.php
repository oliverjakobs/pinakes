<?php declare(strict_types=1);

namespace App\Pinakes;

use App\Entity\PinakesEntity;

class DataTypeCurrency extends DataType {
    public function renderValue(PinakesEntity $entity, mixed $data, mixed $link): string {
        return parent::renderValue($entity, sprintf('%.2f €', $data), $link);
    }
}
