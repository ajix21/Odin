<?php

namespace App\Http\Controllers;

use App\Models\SearchLog;
use App\Services\EmailOsintService;
use Illuminate\Http\Request;

class EmailOsintController extends Controller
{
    public function __construct(private EmailOsintService $service) {}

    public function index()
    {
        return view('tools.email-osint');
    }

    public function analyze(Request $request)
    {
        $request->validate(['email' => 'required|email|max:200']);

        $email  = $request->input('email');
        $result = $this->service->analyze($email);

        SearchLog::create([
            'user_id'     => auth()->id(),
            'tool'        => 'email-osint',
            'query'       => $email,
            'result_json' => ['valid' => $result['valid'] ?? false, 'disposable' => $result['disposable'] ?? false, 'gravatar' => (bool)($result['gravatar'] ?? false)],
            'status'      => ($result['valid'] ?? false) ? 'success' : 'failed',
            'ip_address'  => $request->ip(),
        ]);

        return response()->json($result);
    }
}
