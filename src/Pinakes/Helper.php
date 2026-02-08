<?php declare(strict_types=1);

namespace App\Pinakes;

use Doctrine\Common\Collections\Collection;

class Helper {

    public static function isEmpty(mixed $data): bool {
        if (null === $data) return true;
        if ($data instanceof Collection) return $data->isEmpty();
        return !is_scalar($data) && empty($data);
    }
}
