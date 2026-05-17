<?php

namespace App\Http\Controllers;

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

        $result = $this->service->lookup($request->input('domain'));
        return view('tools.whois', compact('result'));
    }
}
