<?php

namespace App\Http\Controllers;

use App\Services\IpGeolocationService;
use Illuminate\Http\Request;

class IpGeoController extends Controller
{
    public function __construct(private IpGeolocationService $service) {}

    public function index()
    {
        return view('tools.ip-geo');
    }

    public function lookup(Request $request)
    {
        $request->validate(['ip' => 'required|ip']);

        $result = $this->service->lookup($request->input('ip'));
        return view('tools.ip-geo', compact('result'));
    }
}
