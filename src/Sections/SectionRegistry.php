<?php

namespace FilaForge\Studio\Sections;

use Closure;
use FilaForge\Studio\DynamicData\DataContext;
use FilaForge\Studio\Models\PageLocale;
use FilaForge\Studio\Rendering\HtmlRenderer;
use InvalidArgumentException;

/** Aliases and tenant-aware page resolution are supplied by trusted host code. */
final class SectionRegistry
{
    private array $sections = [];

    public function register(string $key, string $label, Closure $resolve, Closure $authorize, array $fields = []): void
    {
        if (!preg_match('/^[a-z][a-z0-9-]{0,63}$/D', $key) || isset($this->sections[$key])) throw new InvalidArgumentException('Invalid or duplicate section.');
        $targets = [];
        foreach ($fields as $name => $field) {
            if (!is_string($name) || !preg_match('/^[a-z][a-z0-9-]{0,63}$/D',$name) || !is_array($field) || !is_string($field['node'] ?? null) || !preg_match('/^[A-Za-z0-9_-]{1,80}$/D',$field['node']) || !is_string($field['property'] ?? null) || !preg_match('/^[A-Za-z][A-Za-z0-9_]{0,63}$/D',$field['property']) || !is_string($field['label'] ?? null) || !in_array($field['type'] ?? 'text',['text','image','url','number','boolean'],true)) throw new InvalidArgumentException('Invalid exposed section field.');
            $target = $field['node'].'.'.$field['property'];
            if (isset($targets[$target])) throw new InvalidArgumentException('Duplicate exposed section property.');
            $targets[$target] = true;
        }
        $this->sections[$key] = compact('key', 'label', 'resolve', 'authorize', 'fields');
    }

    public function catalog(DataContext $context): array
    {
        $items = [];
        foreach ($this->sections as $section) if (($section['authorize'])($context)) $items[] = ['key'=>$section['key'],'label'=>$section['label'],'fields'=>array_map(fn($field)=>['label'=>$field['label'],'type'=>$field['type']??'text'],$section['fields'])];
        return $items;
    }

    public function document(string $key, DataContext $context, array $values = []): array
    {
        $section = $this->sections[$key] ?? throw new InvalidArgumentException('Unknown shared section.');
        if (!(bool) ($section['authorize'])($context)) throw new InvalidArgumentException('Shared section access denied.');
        $page = ($section['resolve'])($context);
        if (!$page instanceof PageLocale || $page->locale !== $context->locale) throw new InvalidArgumentException('Invalid section locale.');
        $document = app(HtmlRenderer::class)->publishedDocument($page);
        if (array_diff(array_keys($values),array_keys($section['fields']))) throw new InvalidArgumentException('Unknown section override.');
        $applied = [];
        $walk = function (array $node) use (&$walk, &$applied, $values, $section): array {
            foreach ($values as $name => $value) {
                $field = $section['fields'][$name];
                if ($node['id'] !== $field['node']) continue;
                $property = $field['property'];
                if (!array_key_exists($property,$node['props']) || $property === 'formatted' || (!is_scalar($value) && $value !== null) || (is_string($value) && mb_strlen($value)>20000)) throw new InvalidArgumentException('Invalid section override.');
                $node['props'][$property] = $value;
                unset($node['props']['formatted'][$property],$node['bindings'][$property]);
                $applied[$name] = true;
            }
            $node['children'] = array_map($walk,$node['children']);
            return $node;
        };
        $document['root'] = $walk($document['root']);
        if (array_diff(array_keys($values),array_keys($applied))) throw new InvalidArgumentException('Exposed section element no longer exists.');
        app(\FilaForge\Studio\Core\DocumentValidator::class)->validate($document,true);
        return $document;
    }
}
