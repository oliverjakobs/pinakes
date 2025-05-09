<?php declare(strict_types=1);

namespace App\Pinakes;

class Html {
    public static function renderElement(string $element, string $content, array $attributes=[]) {
        $attributes = array_map(function($key, $value) {
            return sprintf('%s="%s"', $key, $value);
        }, array_keys($attributes), $attributes);

        return sprintf('<%s %s>%s</%s>', $element, implode(' ', $attributes), $content, $element);
    }

    public static function renderInput(string $type, string $name, $value): string {
        return <<<XML
            <input type="$type" id="$name" name="$name" value="$value">
        XML;
    }

    public static function renderAutocomplete(string $type, string $name, $value, array $autocomplete=[]): string {
        $list = [];
        foreach ($autocomplete as $value) {
            $list[] = sprintf('<option value="%s"></option>', $value);
        }

        $options_str = implode(PHP_EOL, $list);
        return <<<XML
        <input type="$type" id="$name" name="$name" value="$value" list="options-$name">
        <datalist id="options-$name">
        $options_str
        </datalist>
        XML;
    }

    public static function renderSelect(string $name, array $options, int $selected): string {
        $list = [];
        foreach ($options as $id => $caption) {
            $list[] = sprintf('<option value="%s" %s>%s</option>', $id, $id === $selected ? 'selected' : '', $caption);
        }

        $options_str = implode(PHP_EOL, $list);
        return <<<XML
            <select id="$name" name="$name">
            $options_str
            </select>
        XML;
    }
}
