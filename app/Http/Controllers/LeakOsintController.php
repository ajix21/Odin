<?php

namespace App\Http\Controllers;

use App\Models\SearchLog;
use App\Services\LeakOsintService;
use Illuminate\Http\Request;

class LeakOsintController extends Controller
{
    public function __construct(private LeakOsintService $service) {}

    public function index()
    {
        return view('tools.leakosint');
    }

    public function search(Request $request)
    {
        $request->validate([
            'query' => 'required|string|max:5000',
            'limit' => 'integer|in:10,50,100,250,500,1000,5000,10000',
            'lang'  => 'string|in:en,ru,de,fr,es,it,pt,zh,ar',
        ]);

        try {
            $data   = $this->service->search(
                $request->input('query'),
                (int) $request->input('limit', 100),
                $request->input('lang', 'en')
            );
            $status = 'success';
            $error  = null;
        } catch (\Exception $e) {
            $data   = [];
            $status = 'failed';
            $error  = $e->getMessage();
        }

        SearchLog::create([
            'user_id'       => auth()->id(),
            'tool'          => 'leakosint',
            'query'         => $request->input('query'),
            'result_json'   => $data ?: null,
            'status'        => $status,
            'error_message' => $error,
            'ip_address'    => $request->ip(),
        ]);

        return view('tools.leakosint', compact('data', 'status', 'error'));
    }

    public function history()
    {
        $user  = auth()->user();
        $query = SearchLog::where('tool', 'leakosint')->with('user');
        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }
        $logs = $query->latest()->paginate(30);
        return view('history.leakosint', compact('logs'));
    }
}
