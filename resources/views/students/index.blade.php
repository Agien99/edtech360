@extends('layouts.app')
@section('title', 'Students')
@section('content')
<x-ui.page-header title="Students" description="Student records available under your assigned responsibilities." />
<section class="ed-card">
    <div class="table-responsive">
        <table class="table ed-table align-middle mb-0">
            <thead><tr><th>Student Number</th><th>Name</th><th>Status</th></tr></thead>
            <tbody>
                @forelse ($students as $student)
                    <tr>
                        <td>{{ $student->student_number }}</td>
                        <td>{{ $student->full_name }}</td>
                        <td>{{ ucfirst($student->status) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center py-4 text-muted">No accessible student records.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-3">{{ $students->links() }}</div>
</section>
@endsection