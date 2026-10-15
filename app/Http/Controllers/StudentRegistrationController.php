<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\StudentProfile;
use App\Services\StudentRegistrationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StudentRegistrationController extends Controller
{
    private const HEADERS = ['ic_number', 'full_name', 'email'];

    public function index(): View
    {
        $sessions = AcademicSession::query()->with('batch')->whereIn('status', ['planned', 'active'])
            ->orderByDesc('start_date')->get()->filter(fn ($s) => $s->batch?->status === 'active');
        $students = StudentProfile::query()->with(['user:id,email', 'batchMemberships.batch.academicSession'])
            ->latest('id')->paginate(20);
        return view('student-registrations.index', compact('sessions', 'students'));
    }

    public function store(Request $request, StudentRegistrationService $service): RedirectResponse
    {
        $data = $request->validate([
            'academic_session_id' => ['required', 'integer', 'exists:academic_sessions,id'],
            'students' => ['required', 'array', 'min:1', 'max:200'],
            'students.*.ic_number' => ['required', 'string', 'regex:/^\d{6}-?\d{2}-?\d{4}$/'],
            'students.*.full_name' => ['required', 'string', 'max:150'],
            'students.*.email' => ['required', 'email', 'max:255'],
        ]);
        $rows = $this->normalizeAndValidate($data['students']);
        $credentials = $service->registerRows($request->user(), (int)$data['academic_session_id'], $rows);
        return to_route('student-registrations.index')
            ->with('success', count($credentials).' student(s) registered without class or student number.')
            ->with('issued_credentials', $credentials);
    }

    public function import(Request $request, StudentRegistrationService $service): RedirectResponse
    {
        $data = $request->validate([
            'academic_session_id' => ['required', 'integer', 'exists:academic_sessions,id'],
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:1024'],
            'confirm_import' => ['accepted'],
        ]);
        $handle = fopen($request->file('csv_file')->getRealPath(), 'rb');
        if (!$handle) throw ValidationException::withMessages(['csv_file' => 'Unable to read CSV file.']);
        try {
            $header = fgetcsv($handle);
            if (!$header) throw ValidationException::withMessages(['csv_file' => 'Empty CSV.']);
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
            if (array_map('trim', $header) !== self::HEADERS) {
                throw ValidationException::withMessages(['csv_file' => 'Expected columns: '.implode(',', self::HEADERS)]);
            }
            $rows = [];
            while (($cells = fgetcsv($handle)) !== false) {
                if (count($cells) === 1 && trim($cells[0]) === '') continue;
                if (count($rows) >= 200) throw ValidationException::withMessages(['csv_file' => 'Maximum 200 rows.']);
                if (count($cells) !== 3) throw ValidationException::withMessages(['csv_file' => 'Invalid column count on row '.(count($rows)+2)]);
                $rows[] = array_combine(self::HEADERS, array_map('trim', $cells));
            }
            $validator = Validator::make(['students' => $rows], [
                'students' => ['required','array','min:1','max:200'],
                'students.*.ic_number' => ['required','string','regex:/^\d{6}-?\d{2}-?\d{4}$/'],
                'students.*.full_name' => ['required','string','max:150'],
                'students.*.email' => ['required','email','max:255'],
            ]);
            $validator->validate();
            $rows = $this->normalizeAndValidate($rows);
            $credentials = $service->registerRows($request->user(), (int)$data['academic_session_id'], $rows, 'csv');
        } finally { fclose($handle); }
        return to_route('student-registrations.index')
            ->with('success', count($credentials).' student(s) imported.')
            ->with('issued_credentials', $credentials);
    }

    private function normalizeAndValidate(array $rows): array
    {
        $ics = []; $emails = [];
        foreach ($rows as $index => &$row) {
            $row['ic_number'] = preg_replace('/\D/', '', $row['ic_number']);
            $row['email'] = mb_strtolower(trim($row['email']));
            $row['full_name'] = trim($row['full_name']);
            if (isset($ics[$row['ic_number']]) || isset($emails[$row['email']])) {
                throw ValidationException::withMessages(['students' => 'Duplicate IC or email at row '.($index+1).'.']);
            }
            $ics[$row['ic_number']] = true;
            $emails[$row['email']] = true;
        }
        unset($row);
        return $rows;
    }
}
