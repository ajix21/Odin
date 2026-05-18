<?php

namespace App\Http\Controllers;

use App\Models\SearchLog;
use App\Services\IpGeolocationService;
use App\Services\MulticheckService;
use App\Services\WhoisService;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    // GET /api/v1/me
    public function me()
    {
        $user  = auth()->user();
        $today = today();
        $used  = SearchLog::where('user_id', $user->id)->whereDate('created_at', $today)->count();

        return response()->json([
            'id'                  => $user->id,
            'name'                => $user->name,
            'username'            => $user->username,
            'role'                => $user->role,
            'daily_search_limit'  => $user->daily_search_limit,
            'searches_used_today' => $used,
            'searches_remaining'  => $user->daily_search_limit !== null
                ? max(0, $user->daily_search_limit - $used)
                : null,
        ]);
    }

    // POST /api/v1/multicheck
    public function multicheck(Request $request, MulticheckService $service)
    {
        $request->validate(['username' => 'required|string|max:50|regex:/^[a-zA-Z0-9._-]+$/']);
        $username = $request->input('username');

        try {
            $results = $service->check($username);
            $found   = collect($results)->where('found', true)->keys()->toArray();
            $this->log('multicheck', $username, 'success', ['found_on' => $found, 'count' => count($found)]);
            return response()->json(['username' => $username, 'results' => $results]);
        } catch (\Exception $e) {
            $this->log('multicheck', $username, 'failed', null, $e->getMessage());
            return response()->json(['error' => 'Pencarian gagal: ' . $e->getMessage()], 500);
        }
    }

    // POST /api/v1/ip-geo
    public function ipGeo(Request $request, IpGeolocationService $service)
    {
        $request->validate(['ip' => 'required|ip']);
        $ip = $request->input('ip');

        try {
            $result = $service->lookup($ip);
            $status = ($result['success'] ?? false) ? 'success' : 'failed';
            $this->log('ip-geo', $ip, $status, $status === 'success' ? $result : null, $result['error'] ?? null);
            return response()->json($result);
        } catch (\Exception $e) {
            $this->log('ip-geo', $ip, 'failed', null, $e->getMessage());
            return response()->json(['error' => 'Pencarian gagal: ' . $e->getMessage()], 500);
        }
    }

    // POST /api/v1/whois
    public function whois(Request $request, WhoisService $service)
    {
        $request->validate(['domain' => 'required|string|max:253|regex:/^[a-zA-Z0-9._-]+$/']);
        $domain = $request->input('domain');

        try {
            $result = $service->lookup($domain);
            $status = ($result['success'] ?? false) ? 'success' : 'failed';
            $this->log('whois', $domain, $status, $status === 'success' ? $result : null, $result['error'] ?? null);
            return response()->json($result);
        } catch (\Exception $e) {
            $this->log('whois', $domain, 'failed', null, $e->getMessage());
            return response()->json(['error' => 'Pencarian gagal: ' . $e->getMessage()], 500);
        }
    }

    private function log(string $tool, string $query, string $status, ?array $result, ?string $error = null): void
    {
        SearchLog::create([
            'user_id'       => auth()->id(),
            'tool'          => $tool,
            'query'         => $query,
            'result_json'   => $result,
            'status'        => $status,
            'error_message' => $error,
            'ip_address'    => request()->ip(),
        ]);
    }
}
