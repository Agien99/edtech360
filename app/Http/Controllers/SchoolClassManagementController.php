<?php
namespace App\Http\Controllers;

use App\Http\Requests\StoreSchoolClassRequest;
use App\Http\Requests\UpdateSchoolClassRequest;
use App\Models\SchoolClass;
use App\Services\SchoolClassManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SchoolClassManagementController extends Controller
{
    public function store(StoreSchoolClassRequest $request, SchoolClassManagementService $service): RedirectResponse
    {
        $service->create($request->user(), $request->validated());
        return redirect()->route('classes.index')->with('success', 'School class registered successfully.');
    }

    public function update(UpdateSchoolClassRequest $request, SchoolClass $schoolClass, SchoolClassManagementService $service): RedirectResponse
    {
        $service->update($request->user(), $schoolClass, $request->validated());
        return redirect()->route('classes.index')->with('success', 'School class updated successfully.');
    }

    public function deactivate(Request $request, SchoolClass $schoolClass, SchoolClassManagementService $service): RedirectResponse
    {
        $service->deactivate($request->user(), $schoolClass);
        return redirect()->route('classes.index')->with('success', 'School class deactivated successfully.');
    }
}
