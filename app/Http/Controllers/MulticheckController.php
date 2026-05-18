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
        $results  = $this->service->check($username);

        $found = collect($results)->where('found', true)->keys()->toArray();

        SearchLog::create([
            'user_id'     => auth()->id(),
            'tool'        => 'multicheck',
            'query'       => $username,
            'result_json' => ['found_on' => $found, 'count' => count($found)],
            'status'      => 'success',
            'ip_address'  => $request->ip(),
        ]);

        return response()->json(['username' => $username, 'results' => $results]);
    }
}
