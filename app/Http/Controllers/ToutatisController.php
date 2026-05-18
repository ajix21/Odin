<?php

namespace App\Http\Controllers;

use App\Models\SearchLog;
use App\Services\ToutatisService;
use Illuminate\Http\Request;

class ToutatisController extends Controller
{
    public function __construct(private ToutatisService $service) {}

    public function index()
    {
        return view('tools.toutatis');
    }

    public function lookup(Request $request)
    {
        $request->validate(['username' => 'required|string|max:30|regex:/^[a-zA-Z0-9._]+$/']);

        $username = $request->input('username');
        $result   = $this->service->lookup($username);

        SearchLog::create([
            'user_id'     => auth()->id(),
            'tool'        => 'toutatis',
            'query'       => $username,
            'result_json' => ($result['success'] ?? false) ? ['source' => $result['source'] ?? null, 'id' => $result['id'] ?? null] : null,
            'status'      => ($result['success'] ?? false) ? 'success' : 'failed',
            'error_message' => $result['error'] ?? null,
            'ip_address'  => $request->ip(),
        ]);

        return response()->json($result);
    }
}
