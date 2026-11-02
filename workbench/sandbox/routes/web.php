<?php
use Illuminate\Support\Facades\Route;
use FilaForge\Studio\Models\PageLocale;
use FilaForge\Studio\Rendering\BladeRenderer;
Route::get('/', function () {
    $page=PageLocale::firstOrFail();
    return response('<!doctype html><html lang="fa" dir="rtl"><meta charset="utf-8"><title>محیط تست FilaForge</title><style>body{font:18px system-ui;max-width:850px;margin:4rem auto;padding:1rem;line-height:2}a{display:block;margin:1rem 0;color:#4338ca}</style><h1>محیط تست مستقل FilaForge Studio</h1><p>این برنامه، دیتابیس و کپی پکیج همگی در /tmp قرار دارند و از پروژهٔ اصلی جدا هستند.</p><a href="/admin">ورود به پنل مدیریت</a><a href="/admin/pages/'.$page->id.'/edit">ویرایش صفحهٔ نمونه</a><a href="/preview/test-page">مشاهدهٔ خروجی منتشرشده</a></html>');
});
Route::get('/preview/{slug}', function (string $slug) {
    $page=PageLocale::where('locale','fa')->where('slug',$slug)->whereHas('page')->firstOrFail();
    abort_unless($revision=$page->publishedRevision,404);
    return app(\FilaForge\Studio\Rendering\HtmlRenderer::class)->publishedResponse($page);
});
