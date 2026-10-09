<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBatchRequest;
use App\Http\Requests\UpdateBatchRequest;
use App\Models\Batch;
use App\Services\BatchManagementService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BatchController extends Controller
{
    /**
     * Display a list of student batches.
     */
    public function index(Request $request): View
    {
        $batches = Batch::query()
            ->withCount([
                'schoolClasses',
                'studentMemberships',
            ])
            ->orderByDesc('intake_year')
            ->orderBy('code')
            ->paginate(10)
            ->withQueryString();

        return view('batches.index', [
            'batches' => $batches,
        ]);
    }

    /**
     * Store a newly created student batch.
     */
    public function store(
        StoreBatchRequest $request,
        BatchManagementService $service
    ): RedirectResponse {
        $service->create(
            $request->user(),
            $request->validated()
        );

        return redirect()
            ->route('batches.index')
            ->with(
                'success',
                'Student batch created successfully.'
            );
    }

    /**
     * Update an existing student batch.
     */
    public function update(
        UpdateBatchRequest $request,
        Batch $batch,
        BatchManagementService $service
    ): RedirectResponse {
        $service->update(
            $request->user(),
            $batch,
            $request->validated()
        );

        return redirect()
            ->route('batches.index')
            ->with(
                'success',
                'Student batch updated successfully.'
            );
    }

    /**
     * Change the status of an existing batch.
     */
    public function changeStatus(
        Request $request,
        Batch $batch,
        BatchManagementService $service
    ): RedirectResponse {
        $validated = $request->validate([
            'status' => [
                'required',
                'string',
                \Illuminate\Validation\Rule::in([
                    'completed',
                    'inactive',
                ]),
            ],
        ]);

        $service->changeStatus(
            $request->user(),
            $batch,
            $validated['status']
        );

        return redirect()
            ->route('batches.index')
            ->with(
                'success',
                'Batch status updated successfully.'
            );
    }

}