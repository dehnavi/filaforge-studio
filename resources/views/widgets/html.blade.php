<style>@include('filaforge::rendering.html-block-styles')</style>
<div data-node="{{ $node['id'] }}" class="ff-html">{!! \FilaForge\Studio\Rendering\SafeHtml::cleanBlock($props['html'], $node['id']) !!}</div>
