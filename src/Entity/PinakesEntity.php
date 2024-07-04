<?php

namespace App\Entity;

use Doctrine\Common\Collections\Collection;

class PinakesEntity {

    public function renderField(array $field): string {
        $result = $this->{$field['name']} ?? $field['default'];

        if ($result instanceof Collection) $result = implode('; ', $result->toArray());

        if (isset($field['link'])) {
            $result = sprintf('<a href="%s">%s</a>', $field['link']($this), $result);
        }

        return $result ?? '';
    }
}