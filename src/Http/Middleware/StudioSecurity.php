<?php
namespace FilaForge\Studio\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
final class StudioSecurity
{
    public function handle(Request $request, Closure $next)
    {
        if (!in_array($request->method(), ['GET','HEAD','OPTIONS'], true)) {
            $origin=$request->headers->get('Origin');
            abort_if($request->headers->get('Sec-Fetch-Site')==='cross-site',403);
            if($origin!==null)abort_unless(hash_equals(strtolower($request->getSchemeAndHttpHost()),strtolower(rtrim($origin,'/'))),403);
        }
        $response=$next($request);
        $response->headers->set('X-Content-Type-Options','nosniff');
        $response->headers->set('X-Frame-Options','SAMEORIGIN');
        $response->headers->set('Referrer-Policy','same-origin');
        $response->headers->set('Cache-Control','private, no-store');
        return $response;
    }
}
