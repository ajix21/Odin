<?php

namespace App\Http\Controllers;

use App\Services\ToutatisService;
use Illuminate\Http\Request;

class ToutatisController extends Controller
{
    public function __construct(private ToutatisService $service) {}

    public function index()
    {
        return view('tools.toutatis');
    }

    public function lookup(Request $request)
    {
        $request->validate(['username' => 'required|string|max:30|regex:/^[a-zA-Z0-9._]+$/']);

        $result = $this->service->lookup($request->input('username'));
        return view('tools.toutatis', compact('result'));
    }
}
