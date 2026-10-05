@extends('layouts.app')
@section('title', 'Students')
@section('content')
<div class="page-heading action-heading">
    <div><p class="eyebrow">Home / Students</p><h1>Students</h1><p>Manage student records and academic information.</p></div>
    <a href="{{ route('students.create', ['role' => $role]) }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Add Student</a>
</div>

<section class="panel data-panel">
    <div class="filter-bar">
        <div class="filter-search"><i class="bi bi-search"></i><input type="search" placeholder="Search student name, IC or class..."></div>
        <select class="form-select"><option>All Classes</option><option>Form 6A</option><option>Form 6B</option><option>Form 6C</option></select>
        <select class="form-select"><option>All Status</option><option>Active</option><option>Inactive</option></select>
        <button class="btn btn-light border"><i class="bi bi-funnel me-1"></i> More Filters</button>
    </div>

    <div class="table-responsive student-table-wrap d-none d-md-block">
        <table class="table align-middle student-table mb-0">
            <thead><tr><th>#</th><th>Name</th><th>Class</th><th>IC / Student No.</th><th>Gender</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @foreach($students as $index => $student)
                <tr><td>{{ $index + 1 }}</td><td><strong>{{ $student['name'] }}</strong></td><td>{{ $student['class'] }}</td><td>{{ $student['id'] }}</td><td>{{ $student['gender'] }}</td><td><span class="status-badge {{ $student['status'] === 'Active' ? 'success' : 'danger' }}"><i></i>{{ $student['status'] }}</span></td><td class="text-end"><button class="row-action"><i class="bi bi-three-dots-vertical"></i></button></td></tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="mobile-record-list d-md-none">
        @foreach($students as $student)
            <article class="record-card"><div class="record-card-top"><div><strong>{{ $student['name'] }}</strong><span>{{ $student['id'] }}</span></div><span class="status-badge {{ $student['status'] === 'Active' ? 'success' : 'danger' }}"><i></i>{{ $student['status'] }}</span></div><div class="record-meta"><span><i class="bi bi-easel2"></i>{{ $student['class'] }}</span><span><i class="bi bi-person"></i>{{ $student['gender'] }}</span></div><div class="record-actions"><button class="btn btn-sm btn-light border">View</button><button class="btn btn-sm btn-outline-primary">Edit</button></div></article>
        @endforeach
    </div>

    <div class="table-footer"><span>Showing 1 to {{ count($students) }} of 328 students</span><nav><button disabled>‹</button><button class="active">1</button><button>2</button><button>3</button><button>4</button><button>›</button></nav></div>
</section>
@endsection
