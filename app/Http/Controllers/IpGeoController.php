<?php

namespace App\Http\Controllers;

use App\Models\SearchLog;
use App\Services\IpGeolocationService;
use Illuminate\Http\Request;

class IpGeoController extends Controller
{
    public function __construct(private IpGeolocationService $service) {}

    public function index()
    {
        return view('tools.ip-geo');
    }

    public function lookup(Request $request)
    {
        $request->validate(['ip' => 'required|ip']);

        $ip     = $request->input('ip');
        $result = $this->service->lookup($ip);

        SearchLog::create([
            'user_id'     => auth()->id(),
            'tool'        => 'ip-geo',
            'query'       => $ip,
            'result_json' => ($result['success'] ?? false) ? ['country' => $result['country'] ?? null, 'isp' => $result['isp'] ?? null] : null,
            'status'      => ($result['success'] ?? false) ? 'success' : 'failed',
            'error_message' => $result['error'] ?? null,
            'ip_address'  => $request->ip(),
        ]);

        return view('tools.ip-geo', compact('result'));
    }
}
