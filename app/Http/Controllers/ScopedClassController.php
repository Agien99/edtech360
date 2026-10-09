<?php
namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\Batch;
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
            ->with(['academicSession:id,name', 'batch:id,code,name'])
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $canManage = $request->user()->can('classes.create') || $request->user()->can('classes.update');
        $sessions = $canManage
            ? AcademicSession::query()->whereIn('status', ['planned', 'active'])->orderByDesc('start_date')->get(['id', 'name', 'status'])
            : collect();
        $batches = $canManage
            ? Batch::query()->where('status', 'active')->orderByDesc('intake_year')->get(['id', 'code', 'name'])
            : collect();

        return view('classes.index', compact('classes', 'sessions', 'batches'));
    }
}
