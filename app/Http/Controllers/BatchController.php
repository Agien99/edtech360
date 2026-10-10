<?php
namespace App\Http\Controllers;
use App\Models\Batch;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class BatchController extends Controller
{
    public function index(Request $request): View
    {
        $batches = Batch::query()
            ->with('academicSession:id,name,start_date,end_date,status')
            ->withCount(['schoolClasses', 'studentMemberships'])
            ->orderByDesc('intake_year')->orderBy('code')->paginate(10)->withQueryString();
        return view('batches.index', compact('batches'));
    }
}
