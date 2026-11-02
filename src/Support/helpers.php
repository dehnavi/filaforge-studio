<?php

use FilaForge\Studio\Models\PageLocale;
use FilaForge\Studio\Rendering\HtmlOptions;
use FilaForge\Studio\Rendering\HtmlRenderer;
use Illuminate\Support\HtmlString;

if (!function_exists('filaforge_html')) {
    function filaforge_html(array $document, ?PageLocale $page = null, ?\FilaForge\Studio\DynamicData\DataContext $context = null): HtmlString
    {
        return app(HtmlRenderer::class)->fragment($document, $page, $context);
    }
}

if (!function_exists('filaforge_document')) {
    function filaforge_document(array $document, ?HtmlOptions $options = null, ?PageLocale $page = null, ?\FilaForge\Studio\DynamicData\DataContext $context = null): HtmlString
    {
        return app(HtmlRenderer::class)->document($document, $options, $page, $context);
    }
}

if (!function_exists('filaforge_response')) {
    function filaforge_response(array $document, ?HtmlOptions $options = null, ?PageLocale $page = null, ?\FilaForge\Studio\DynamicData\DataContext $context = null): \Illuminate\Http\Response
    {
        return app(HtmlRenderer::class)->response($document, $options, $page, $context);
    }
}
