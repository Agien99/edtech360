<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissionsByModule = [
            'dashboard' => ['view'],
            'users' => ['view', 'create', 'update', 'deactivate'],
            'roles' => ['view', 'assign', 'manage'],
            'academic_sessions' => ['view', 'manage'],
            'batches' => ['view', 'manage'],
            'classes' => ['view', 'create', 'update', 'assign_teachers'],
            'students' => ['view', 'create', 'update', 'enroll', 'transfer'],
            'teachers' => ['view', 'create', 'update', 'assign'],
            'subjects' => ['view', 'manage'],
            'subject_groups' => ['view', 'manage'],
            'materials' => ['view', 'create', 'update', 'delete', 'publish'],
            'assignments' => ['view', 'create', 'update', 'delete', 'publish', 'grade', 'submit'],
            'quizzes' => ['view', 'create', 'update', 'delete', 'publish', 'grade', 'attempt'],
            'attendance' => ['view', 'record', 'update'],
            'timetables' => ['view', 'manage'],
            'student_positions' => ['view', 'manage'],
            'cocurricular' => ['view', 'manage', 'assign_advisors', 'manage_members'],
            'files' => ['view', 'upload', 'download', 'replace', 'delete'],
            'reports' => ['view', 'export'],
            'settings' => ['view', 'manage'],
            'audit_logs' => ['view', 'export'],
        ];

        $allPermissions = [];

        foreach ($permissionsByModule as $module => $actions) {
            foreach ($actions as $action) {
                $name = $module . '.' . $action;

                Permission::findOrCreate($name, 'web');

                $allPermissions[] = $name;
            }
        }

        $roles = [
            'super_admin' => $allPermissions,

            'school_admin' => [
                'dashboard.view',
                'users.view',
                'users.create',
                'users.update',
                'users.deactivate',
                'roles.view',
                'roles.assign',
                'academic_sessions.view',
                'academic_sessions.manage',
                'batches.view',
                'batches.manage',
                'classes.view',
                'classes.create',
                'classes.update',
                'classes.assign_teachers',
                'students.view',
                'students.create',
                'students.update',
                'students.enroll',
                'students.transfer',
                'teachers.view',
                'teachers.create',
                'teachers.update',
                'teachers.assign',
                'subjects.view',
                'subjects.manage',
                'subject_groups.view',
                'subject_groups.manage',
                'attendance.view',
                'timetables.view',
                'reports.view',
                'reports.export',
                'settings.view',
            ],

            'curriculum_admin' => [
                'dashboard.view',
                'academic_sessions.view',
                'classes.view',
                'students.view',
                'teachers.view',
                'teachers.assign',
                'subjects.view',
                'subjects.manage',
                'subject_groups.view',
                'subject_groups.manage',
                'materials.view',
                'assignments.view',
                'quizzes.view',
                'timetables.view',
                'timetables.manage',
                'reports.view',
            ],

            'cocurricular_admin' => [
                'dashboard.view',
                'students.view',
                'teachers.view',
                'cocurricular.view',
                'cocurricular.manage',
                'cocurricular.assign_advisors',
                'cocurricular.manage_members',
                'reports.view',
            ],

            'class_teacher' => [
                'dashboard.view',
                'classes.view',
                'students.view',
                'students.update',
                'attendance.view',
                'attendance.record',
                'attendance.update',
                'student_positions.view',
                'student_positions.manage',
                'timetables.view',
                'reports.view',
            ],

            'assistant_class_teacher' => [
                'dashboard.view',
                'classes.view',
                'students.view',
                'attendance.view',
                'attendance.record',
                'student_positions.view',
                'timetables.view',
            ],

            'subject_teacher' => [
                'dashboard.view',
                'classes.view',
                'students.view',
                'subjects.view',
                'materials.view',
                'materials.create',
                'materials.update',
                'materials.delete',
                'materials.publish',
                'assignments.view',
                'assignments.create',
                'assignments.update',
                'assignments.delete',
                'assignments.publish',
                'assignments.grade',
                'quizzes.view',
                'quizzes.create',
                'quizzes.update',
                'quizzes.delete',
                'quizzes.publish',
                'quizzes.grade',
                'files.view',
                'files.upload',
                'files.download',
                'files.replace',
                'files.delete',
                'timetables.view',
            ],

            'subject_group_coordinator' => [
                'dashboard.view',
                'subjects.view',
                'subject_groups.view',
                'materials.view',
                'assignments.view',
                'quizzes.view',
                'reports.view',
            ],

            'student' => [
                'dashboard.view',
                'classes.view',
                'subjects.view',
                'materials.view',
                'assignments.view',
                'assignments.submit',
                'quizzes.view',
                'quizzes.attempt',
                'timetables.view',
                'files.view',
                'files.upload',
                'files.download',
                'attendance.view',
            ],
        ];

        foreach ($roles as $roleName => $permissionNames) {
            $role = Role::findOrCreate($roleName, 'web');

            $role->syncPermissions($permissionNames);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}