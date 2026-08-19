<?php
namespace App\Http;

use Illuminate\Foundation\Http\Kernel as BaseKernel;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProfilingKernel extends BaseKernel
{
    public function handle($request)
    {
        $t0 = microtime(true);
        $r = parent::handle($request);
        $t1 = microtime(true);
        echo "[prof] total handle: ".round(($t1-$t0)*1000,1)."ms route=".($request->route()?->getName() ?? $request->path())."\n";
        return $r;
    }

    protected function sendRequestThroughRouter($request)
    {
        $t0 = microtime(true);
        $r = parent::sendRequestThroughRouter($request);
        $t1 = microtime(true);
        echo "[prof] sendRequestThroughRouter: ".round(($t1-$t0)*1000,1)."ms\n";
        return $r;
    }

    protected function dispatchToRouter(Request $request)
    {
        $t0 = microtime(true);
        $r = parent::dispatchToRouter($request);
        $t1 = microtime(true);
        echo "[prof] dispatchToRouter: ".round(($t1-$t0)*1000,1)."ms\n";
        return $r;
    }

    protected function prepareResponse($request, $response)
    {
        $t0 = microtime(true);
        $r = parent::prepareResponse($request, $response);
        $t1 = microtime(true);
        echo "[prof] prepareResponse: ".round(($t1-$t0)*1000,1)."ms\n";
        return $r;
    }
}
