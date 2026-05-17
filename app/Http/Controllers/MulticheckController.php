<?php

namespace App\Http\Controllers;

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

        return view('tools.multicheck', compact('results', 'username'));
    }
}
