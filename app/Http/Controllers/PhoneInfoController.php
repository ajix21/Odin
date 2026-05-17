<?php

namespace App\Http\Controllers;

use App\Models\SearchLog;
use App\Services\PhoneInfoService;
use Illuminate\Http\Request;

class PhoneInfoController extends Controller
{
    public function __construct(private PhoneInfoService $service) {}

    public function index()
    {
        return view('tools.phone-info');
    }

    public function analyze(Request $request)
    {
        $request->validate(['phone' => 'required|string|max:20']);

        $phone  = $request->input('phone');
        $result = $this->service->analyze($phone);

        SearchLog::create([
            'user_id'     => auth()->id(),
            'tool'        => 'phone-info',
            'query'       => $phone,
            'result_json' => ['valid' => $result['valid'] ?? false, 'country' => $result['country'] ?? null, 'type' => $result['type'] ?? null],
            'status'      => ($result['valid'] ?? false) ? 'success' : 'failed',
            'error_message' => $result['error'] ?? null,
            'ip_address'  => $request->ip(),
        ]);

        return view('tools.phone-info', compact('result'));
    }
}
