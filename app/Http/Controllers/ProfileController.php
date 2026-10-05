<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function complete(Request $request): View
    {
        return view('profile.complete', ['role' => 'student']);
    }
}
