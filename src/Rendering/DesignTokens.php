<?php

namespace FilaForge\Studio\Rendering;

use InvalidArgumentException;

final class DesignTokens
{
    public function catalog(): array
    {
        $tokens = config('filaforge.design_tokens', []);
        foreach ($tokens as $key => $token) {
            if (!preg_match('/^[a-z][a-z0-9-]{0,63}$/D', $key) || !is_array($token) || !is_string($token['label'] ?? null) || !is_string($token['property'] ?? null) || !array_key_exists('value', $token) || is_array($token['value'])) throw new InvalidArgumentException('Invalid design token.');
        }
        return $tokens;
    }

    public function values(array $settings): array
    {
        foreach ($settings as $property => $value) if (is_array($value)) {
            if (array_keys($value) !== ['token'] || !is_string($value['token'])) throw new InvalidArgumentException('Invalid design token reference.');
            $token = $this->catalog()[$value['token']] ?? null;
            if (!$token || $token['property'] !== $property) throw new InvalidArgumentException('Unknown or incompatible design token.');
            $settings[$property] = $token['value'];
        }
        return $settings;
    }

    public function resolve(array $document): array
    {
        $walk = function (array $node) use (&$walk): array {
            $node['styles'] = $this->values($node['styles'] ?? []);
            foreach ($node['responsive'] ?? [] as $breakpoint => $values) $node['responsive'][$breakpoint] = $this->values($values);
            $node['children'] = array_map($walk, $node['children']);
            return $node;
        };
        $document['root'] = $walk($document['root']);
        return $document;
    }
}
