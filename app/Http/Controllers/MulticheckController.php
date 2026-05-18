<?php

namespace App\Http\Controllers;

use App\Models\SearchLog;
use App\Services\MulticheckService;
use Illuminate\Http\Request;

class MulticheckController extends Controller
{
    public function __construct(private MulticheckService $service) {}

    public function index()
    {
        return view('tools.multicheck');
    }

    public function check(Request $request)
    {
        $request->validate(['username' => 'required|string|max:50|regex:/^[a-zA-Z0-9._-]+$/']);

        $username = $request->input('username');

        try {
            $results = $this->service->check($username);
            $found   = collect($results)->where('found', true)->keys()->toArray();
            $status  = 'success';
            $error   = null;
        } catch (\Exception $e) {
            $results = [];
            $found   = [];
            $status  = 'failed';
            $error   = $e->getMessage();
        }

        SearchLog::create([
            'user_id'       => auth()->id(),
            'tool'          => 'multicheck',
            'query'         => $username,
            'result_json'   => $status === 'success' ? ['found_on' => $found, 'count' => count($found)] : null,
            'status'        => $status,
            'error_message' => $error,
            'ip_address'    => $request->ip(),
        ]);

        if ($status === 'failed') {
            return response()->json(['error' => 'Gagal memeriksa username. Coba lagi.'], 500);
        }

        return response()->json(['username' => $username, 'results' => $results]);
    }
}
