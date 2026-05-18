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

    public function query(Request $request)
    {
        $request->validate([
            'request' => 'required|string|max:5000',
            'limit'   => 'integer|in:10,50,100,250,500,1000,5000,10000',
            'lang'    => 'string|in:en,ru,de,fr,es,it,pt,zh,ar',
        ]);

        try {
            $data = $this->service->search(
                $request->input('request'),
                (int) $request->input('limit', 100),
                $request->input('lang', 'en')
            );
            $status = 'success';

            SearchLog::create([
                'user_id'     => auth()->id(),
                'tool'        => 'leakosint',
                'query'       => $request->input('request'),
                'result_json' => ['NumOfResults' => $data['NumOfResults'] ?? 0, 'NumOfDatabase' => $data['NumOfDatabase'] ?? 0],
                'status'      => 'success',
                'ip_address'  => $request->ip(),
            ]);

            return response()->json($data);

        } catch (\Exception $e) {
            SearchLog::create([
                'user_id'       => auth()->id(),
                'tool'          => 'leakosint',
                'query'         => $request->input('request'),
                'result_json'   => null,
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
                'ip_address'    => $request->ip(),
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

}
