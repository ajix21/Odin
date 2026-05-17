<?php

namespace App\Http\Controllers;

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

        $result = $this->service->analyze($request->input('phone'));
        return view('tools.phone-info', compact('result'));
    }
}
