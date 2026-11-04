@php
$collapseAt=$props['collapseAt']??'tablet';
$breakpoint=['tablet'=>1024,'mobile'=>640,'never'=>0][$collapseAt];
@endphp
<nav data-node="{{ $node['id'] }}" data-ff-menu data-collapse-at="{{ $collapseAt }}" aria-label="{{ $props['brand'] }}" class="ff-menu">
    <strong>{!! \FilaForge\Studio\Rendering\RichText::field($props,'brand') !!}</strong>
    <details class="ff-menu-shell" open>
        <summary class="ff-menu-toggle">{{ $props['toggle'] }}</summary>
        <ul class="ff-menu-list">{!! $children !!}</ul>
    </details>
</nav>
<style>
.ff-menu{display:flex;align-items:center;justify-content:space-between;gap:24px;padding:20px;border-block-end:1px solid #e4e2f0;position:relative;z-index:5;box-sizing:border-box;min-inline-size:0;max-inline-size:100%}
.ff-menu *{box-sizing:border-box}.ff-menu strong{min-inline-size:0;overflow-wrap:anywhere}.ff-menu ul{list-style:none;margin:0;padding:0}.ff-menu-shell{min-inline-size:0;max-inline-size:100%}
.ff-menu .ff-menu-list{display:flex;align-items:center;gap:var(--ff-menu-gap,12px);flex-wrap:wrap}
.ff-menu .ff-menu-item{position:relative;min-inline-size:0;max-inline-size:100%}
.ff-menu .ff-menu-line{display:flex;align-items:center;justify-content:space-between;gap:4px;min-inline-size:0}
.ff-menu .ff-menu-line a{padding:12px 10px;color:inherit;text-decoration:none;display:block;min-inline-size:0;min-block-size:44px;white-space:normal;overflow-wrap:anywhere;touch-action:manipulation}
.ff-menu .ff-menu-line a:hover{text-decoration:underline;text-underline-offset:4px}
.ff-menu summary{cursor:pointer;min-block-size:44px;padding:12px 10px;list-style:none;touch-action:manipulation}
.ff-menu summary::-webkit-details-marker{display:none}.ff-menu summary:focus-visible,.ff-menu a:focus-visible{outline:2px solid #4f46e5;outline-offset:3px}
.ff-menu .ff-dropdown-trigger{inline-size:44px;display:flex;align-items:center;justify-content:center}
.ff-menu .ff-menu-toggle{display:none}
.ff-menu .ff-dropdown-panel{position:absolute;inset-block-start:100%;inset-inline-start:0;inline-size:var(--ff-submenu-width,240px);max-inline-size:calc(100vw - 24px);background:#fff;color:#18212f;border:1px solid #e4e2f0;border-radius:10px;padding:6px;z-index:20}
.ff-menu .ff-dropdown-panel .ff-dropdown-panel{inset-block-start:0;inset-inline-start:100%}
.ff-menu .ff-dropdown-panel>.ff-menu-item+.ff-menu-item{margin-block-start:var(--ff-menu-gap,4px)}
.ff-menu .ff-menu-chevron{display:block;inline-size:12px;block-size:12px;fill:none;stroke:currentColor;stroke-width:2}
.ff-menu details[open]>.ff-dropdown-trigger .ff-menu-chevron{transform:rotate(180deg)}
/* Long desktop panels keep descendants inline inside the same scroll surface. */
.ff-menu .ff-scroll-panel{overscroll-behavior:contain}
.ff-menu .ff-scroll-panel .ff-menu-line{display:block;position:relative}
.ff-menu .ff-scroll-panel .ff-menu-line>a{inline-size:100%;padding-inline-end:56px}
.ff-menu .ff-scroll-panel .ff-menu-line>details{display:block;inline-size:100%}
.ff-menu .ff-scroll-panel .ff-dropdown-trigger{position:absolute;inset-block-start:0;inset-inline-end:0}
.ff-menu .ff-scroll-panel .ff-dropdown-panel{position:static;inline-size:auto;max-inline-size:100%;margin-inline-start:var(--ff-submenu-indent,12px);transform:none!important}
@media(max-width:{{ $breakpoint }}px){
[data-node="{{ $node['id'] }}"].ff-menu{align-items:flex-start;flex-wrap:wrap;gap:12px}
[data-node="{{ $node['id'] }}"].ff-menu .ff-menu-shell{inline-size:100%}
[data-node="{{ $node['id'] }}"].ff-menu .ff-menu-toggle{display:list-item;border:1px solid #e4e2f0;border-radius:8px}
[data-node="{{ $node['id'] }}"].ff-menu .ff-menu-list{display:block;padding-block-start:8px;max-block-size:calc(100vh - 96px);max-block-size:calc(100dvh - 96px);overflow-y:auto;overflow-x:hidden;overscroll-behavior:contain}
[data-node="{{ $node['id'] }}"].ff-menu .ff-menu-list>.ff-menu-item+ .ff-menu-item{margin-block-start:var(--ff-menu-gap,8px)}
[data-node="{{ $node['id'] }}"].ff-menu .ff-menu-line{display:block;position:relative}
[data-node="{{ $node['id'] }}"].ff-menu .ff-menu-line>a{inline-size:100%;padding-inline-end:56px}
[data-node="{{ $node['id'] }}"].ff-menu .ff-menu-line>details{display:block;inline-size:100%}
[data-node="{{ $node['id'] }}"].ff-menu .ff-dropdown-trigger{position:absolute;inset-block-start:0;inset-inline-end:0}
[data-node="{{ $node['id'] }}"].ff-menu .ff-dropdown-panel{position:static;inline-size:auto;max-inline-size:100%;margin-inline-start:var(--ff-submenu-indent,12px);transform:none!important}
}
</style>
<script src="{{ \FilaForge\Studio\Support\Assets::url('components.js') }}" defer></script>
