<?php

namespace FilaForge\Studio\Facades;

use FilaForge\Studio\Rendering\HtmlRenderer;
use Illuminate\Support\Facades\Facade;

/** @mixin HtmlRenderer */
final class StudioHtml extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return HtmlRenderer::class;
    }
}
