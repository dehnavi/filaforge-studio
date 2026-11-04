<?php

namespace FilaForge\Studio\Documents;

use FilaForge\Studio\Models\PageLocale;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

/** Restrictions are persisted in the document and enforced against the stored draft. */
final class EditingRules
{
    public function authorize(PageLocale $page, array $before, array $after): void
    {
        if (Gate::allows('filaforge.manageStructure', $page)) return;
        $compare = function (array $old, ?array $new) use (&$compare): void {
            $rules = $old['meta'] ?? [];
            if (($new['meta'] ?? []) !== $rules) throw new InvalidArgumentException('Only structure managers may change editing rules.');
            if ($rules['locked'] ?? false) {
                if ($old !== $new) throw new InvalidArgumentException('This element is locked.');
                return;
            }
            if (array_key_exists('editable', $rules)) {
                $expected = $old;
                foreach ($rules['editable'] as $key) {
                    $expected['props'][$key] = $new['props'][$key] ?? null;
                    unset($expected['props']['formatted'][$key]);
                    if (isset($new['props']['formatted'][$key])) $expected['props']['formatted'][$key] = $new['props']['formatted'][$key];
                }
                if (($expected['props']['formatted'] ?? null) === [] && !isset($new['props']['formatted'])) unset($expected['props']['formatted']);
                if ($expected !== $new) throw new InvalidArgumentException('Only the exposed content fields may change.');
                return;
            }
            $children = array_column($new['children'] ?? [], null, 'id');
            foreach ($old['children'] as $child) {
                if (!isset($children[$child['id']])) {
                    if ($this->hasProtection($child)) throw new InvalidArgumentException('Protected elements cannot be removed.');
                } else $compare($child, $children[$child['id']]);
            }
            $oldIds = array_column($old['children'], 'id');
            foreach ($new['children'] ?? [] as $child) {
                if (!in_array($child['id'], $oldIds, true) && $this->hasRules($child)) throw new InvalidArgumentException('Only structure managers may introduce editing rules.');
            }
            // A protected element must keep its parent and position.
            foreach ($old['children'] as $index => $child) {
                if ($this->hasProtection($child) && ($new['children'][$index]['id'] ?? null) !== $child['id']) throw new InvalidArgumentException('Protected elements cannot be moved.');
            }
        };
        $compare($before['root'], $after['root']);
    }

    private function hasProtection(array $node): bool
    {
        if (($node['meta']['locked'] ?? false) || array_key_exists('editable', $node['meta'] ?? [])) return true;
        foreach ($node['children'] as $child) if ($this->hasProtection($child)) return true;
        return false;
    }

    private function hasRules(array $node): bool
    {
        if (($node['meta'] ?? []) !== []) return true;
        foreach ($node['children'] as $child) if ($this->hasRules($child)) return true;
        return false;
    }
}
