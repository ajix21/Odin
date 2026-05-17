<?php

namespace App\Http\Controllers;

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

        $result = $this->service->analyze($request->input('email'));
        return view('tools.email-osint', compact('result'));
    }
}
