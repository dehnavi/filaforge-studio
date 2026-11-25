@php($embed = \FilaForge\Studio\Rendering\EmbedCode::render($props['code'], $props['title']))
@if($embed !== null)
<style>.ff-embed{position:relative;max-width:100%;min-width:0}.ff-embed iframe{max-width:100%;border:0}.ff-embed-frame{aspect-ratio:16/9}{{--
The frame owns its layout: a stylesheet that another embed's script adds to the page must not move the iframe out of it.
--}}.ff-embed-frame *{position:static!important}.ff-embed-frame iframe{position:absolute!important;inset:0!important;width:100%!important;height:100%!important}</style>
<div data-node="{{ $node['id'] }}" class="ff-embed{{ $embed['frame'] ? ' ff-embed-frame' : '' }}">{!! $embed['html'] !!}</div>
@endif
