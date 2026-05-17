<?php

namespace App\Http\Controllers;

use App\Models\SearchLog;
use App\Services\GetContactService;
use Illuminate\Http\Request;

class PhoneLookupController extends Controller
{
    public function __construct(private GetContactService $gc) {}

    public function index()
    {
        return view('tools.phone-lookup');
    }

    public function search(Request $request)
    {
        $request->validate(['phone' => 'required|string|max:20']);

        $result = $this->gc->lookup($request->input('phone'));
        $status = $result['success'] ? 'success' : 'failed';

        SearchLog::create([
            'user_id'       => auth()->id(),
            'tool'          => 'getcontact',
            'query'         => $request->input('phone'),
            'result_json'   => $result,
            'status'        => $status,
            'error_message' => $result['error'] ?? null,
            'ip_address'    => $request->ip(),
        ]);

        return view('tools.phone-lookup', compact('result'));
    }

    public function history()
    {
        $user  = auth()->user();
        $query = SearchLog::where('tool', 'getcontact')->with('user');
        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }
        $logs = $query->latest()->paginate(30);
        return view('history.phone', compact('logs'));
    }
}
