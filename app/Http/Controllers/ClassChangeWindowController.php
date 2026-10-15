<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ClassChangeWindowController extends Controller
{
    public function index() {
        $sessions = AcademicSession::query()->orderByDesc('start_date')->get();
        $windows = DB::table('class_change_windows')->get()->keyBy('academic_session_id');
        return view('class-change-windows.index', compact('sessions', 'windows'));
    }
    public function update(Request $request, AcademicSession $academicSession): RedirectResponse {
        $data = $request->validate([
            'opens_at' => ['required', 'date_format:Y-m-d\\TH:i'],
            'closes_at' => ['required', 'date_format:Y-m-d\\TH:i', 'after:opens_at'],
            'is_enabled' => ['required', 'boolean'],
        ]);
        $open = \Carbon\Carbon::createFromFormat('Y-m-d\\TH:i', $data['opens_at']);
        $close = \Carbon\Carbon::createFromFormat('Y-m-d\\TH:i', $data['closes_at']);
        if ($open->toDateString() < $academicSession->start_date->toDateString() ||
            $close->toDateString() > $academicSession->end_date->toDateString()) {
            throw ValidationException::withMessages(['opens_at' => 'The window must fall within the Academic Session dates.']);
        }
        DB::transaction(function () use ($request, $academicSession, $open, $close, $data) {
            $old = DB::table('class_change_windows')->where('academic_session_id', $academicSession->id)->lockForUpdate()->first();
            $new = [ 'opens_at' => $open->format('Y-m-d H:i:s'), 'closes_at' => $close->format('Y-m-d H:i:s'),
                'is_enabled' => (bool) $data['is_enabled'], 'updated_by' => $request->user()->id, 'updated_at' => now() ];
            if ($old) {
                DB::table('class_change_windows')->where('id', $old->id)->update($new);
            } else {
                DB::table('class_change_windows')->insert(array_merge($new, ['academic_session_id' => $academicSession->id, 'created_at' => now()]));
            }
            DB::table('audit_logs')->insert([
                'actor_user_id' => $request->user()->id, 'actor_identifier' => $request->user()->email,
                'actor_role_snapshot' => $request->user()->getRoleNames()->implode(', '),
                'event_category' => 'academic_management', 'action' => $old ? 'update' : 'create',
                'module' => 'class_change_windows', 'auditable_type' => AcademicSession::class,
                'auditable_id' => $academicSession->id,
                'old_values' => $old ? json_encode($old, JSON_THROW_ON_ERROR) : null,
                'new_values' => json_encode($new, JSON_THROW_ON_ERROR),
                'metadata' => null, 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent(),
                'request_id' => $request->headers->get('X-Request-ID') ?? (string) Str::uuid(),
                'outcome' => 'success', 'created_at' => now(),
            ]);
        }, 3);
        return back()->with('success', 'Class change window saved.');
    }
}
