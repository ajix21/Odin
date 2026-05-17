<?php

namespace App\Http\Controllers;

use App\Models\SearchLog;
use App\Services\WhoisService;
use Illuminate\Http\Request;

class WhoisController extends Controller
{
    public function __construct(private WhoisService $service) {}

    public function index()
    {
        return view('tools.whois');
    }

    public function lookup(Request $request)
    {
        $request->validate(['domain' => 'required|string|max:253|regex:/^[a-zA-Z0-9][a-zA-Z0-9\-\.]+[a-zA-Z]$/']);

        $domain = $request->input('domain');
        $result = $this->service->lookup($domain);

        SearchLog::create([
            'user_id'     => auth()->id(),
            'tool'        => 'whois',
            'query'       => $domain,
            'result_json' => ($result['success'] ?? false) ? ['registrar' => $result['registrar'] ?? null, 'expires' => $result['expires'] ?? null] : null,
            'status'      => ($result['success'] ?? false) ? 'success' : 'failed',
            'error_message' => $result['whois_error'] ?? null,
            'ip_address'  => $request->ip(),
        ]);

        return view('tools.whois', compact('result'));
    }
}
