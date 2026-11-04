<?php

namespace FilaForge\Studio\Rendering;

final class FragmentNamespace
{
    public function apply(array $document, string $instance): array
    {
        if (!preg_match('/^[A-Za-z][A-Za-z0-9_-]{0,39}$/D', $instance)) throw new \InvalidArgumentException('Invalid fragment instance.');
        $anchors = [];
        $walk = function (array $node) use (&$walk, &$anchors, $instance): array {
            $node['id'] = $instance.'-'.substr(hash('sha256', $node['id']), 0, 24);
            if (!empty($node['props']['anchor'])) {
                $anchor = $node['props']['anchor'];
                $anchors['#'.$anchor] = '#'.$instance.'-'.substr(hash('sha256', $anchor), 0, 24);
                $node['props']['anchor'] = substr($anchors['#'.$anchor], 1);
            }
            $node['children'] = array_map($walk, $node['children']);
            return $node;
        };
        $document['root'] = $walk($document['root']);
        $links = function (array $node) use (&$links, $anchors): array {
            if (isset($anchors[$node['props']['url'] ?? ''])) $node['props']['url'] = $anchors[$node['props']['url']];
            if (isset($anchors[$node['styles']['clickTarget'] ?? ''])) $node['styles']['clickTarget'] = $anchors[$node['styles']['clickTarget']];
            $node['children'] = array_map($links, $node['children']);
            return $node;
        };
        $document['root'] = $links($document['root']);
        return $document;
    }
}
