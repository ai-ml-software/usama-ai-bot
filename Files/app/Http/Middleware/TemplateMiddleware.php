<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TemplateMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        if (env('DEMO_MODE')) {
            $templates = getTemplates();

            if ($request->template && in_array($request->template, $templates)) {
                session()->put('template', $request->template);
            }
            
        }


        return $next($request);
    }
}
