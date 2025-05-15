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

    public static function renderAutocomplete(string $type, string $name, array $values, array $options): string {
        $value_list = [];
        foreach ($values as $idx => $value) {
            $value_list[] = sprintf('<input type="%s" id="%s" name="%s" value="%s" list="options-%s">', $type, $name . $idx, $name, (string) $value, $name);
        }

        $value_str = implode(PHP_EOL, $value_list);

        $option_list = [];
        foreach ($options as $option) {
            $option_list[] = sprintf('<option value="%s"></option>', $option);
        }

        $options_str = implode(PHP_EOL, $option_list);

        return <<<XML
        $value_str
        <datalist id="options-$name">
        $options_str
        </datalist>
        <a>Add more...</a>
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
