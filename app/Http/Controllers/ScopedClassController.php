<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Services\SchoolClassAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ScopedClassController extends Controller
{
    public function index(Request $request, SchoolClassAccess $access): View
    {
        Gate::authorize('viewAny', SchoolClass::class);

        $classes = $access->visibleTo($request->user())
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('classes.index', compact('classes'));
    }
}
