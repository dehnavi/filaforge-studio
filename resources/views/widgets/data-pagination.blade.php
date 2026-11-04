@php
$paginationUrl = function (int $number) use ($props,$dataContext): string {
    if (!request()->isMethod('get')) return '#ff-page-'.$props['node'].'-'.$number;
    $pages=[];
    foreach(array_slice($dataContext->pagination,0,20,true) as $id=>$page) if(is_string($id)&&preg_match('/^[A-Za-z0-9_-]{1,80}$/D',$id)&&filter_var($page,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>1000]])) $pages[$id]=(int)$page;
    $pages[$props['node']]=$number;
    return request()->url().'?'.http_build_query(['ff_page'=>$pages]);
};
@endphp
<nav data-node="{{ $node['id'] }}" aria-label="{{ __('filaforge::studio.data_pagination') }}" style="display:flex;gap:16px;align-items:center;grid-column:1 / -1">
@if($props['page']>1)<a data-ff-loop="{{ $props['node'] }}" data-ff-page="{{ $props['page']-1 }}" rel="prev" href="{{ $paginationUrl($props['page']-1) }}">{{ __('filaforge::studio.data_previous') }}</a>@endif
<span>{{ $props['page'] }} / {{ $props['last'] }}</span>
@if($props['page']<$props['last'])<a data-ff-loop="{{ $props['node'] }}" data-ff-page="{{ $props['page']+1 }}" rel="next" href="{{ $paginationUrl($props['page']+1) }}">{{ __('filaforge::studio.data_next') }}</a>@endif
</nav>
