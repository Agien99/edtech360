<?php

return [
    'roles' => [
        'administrator' => [
            'Main' => [
                ['label' => 'Dashboard', 'icon' => 'bi-grid-1x2-fill', 'route' => 'dashboard'],
            ],
            'Academic' => [
                ['label' => 'Students', 'icon' => 'bi-people', 'route' => 'students.index'],
                ['label' => 'Teachers', 'icon' => 'bi-person-workspace'],
                ['label' => 'Classes', 'icon' => 'bi-easel2', 'route' => 'classes.index'],
                ['label' => 'Subjects', 'icon' => 'bi-journal-bookmark', 'route' => 'subjects.index'],
            ],
            'Learning' => [
                ['label' => 'Attendance', 'icon' => 'bi-check2-square'],
                ['label' => 'Homework', 'icon' => 'bi-journal-text'],
                ['label' => 'Quiz', 'icon' => 'bi-patch-question'],
            ],
            'Management' => [
                ['label' => 'Timetable', 'icon' => 'bi-calendar3'],
                ['label' => 'Reports', 'icon' => 'bi-bar-chart-line'],
            ],
            'System' => [
                ['label' => 'Users', 'icon' => 'bi-person-gear'],
                ['label' => 'Settings', 'icon' => 'bi-gear'],
            ],
        ],
        'teacher' => [
            'Main' => [['label' => 'Dashboard', 'icon' => 'bi-grid-1x2-fill', 'route' => 'dashboard']],
            'Teaching' => [
                ['label' => 'My Classes', 'icon' => 'bi-people', 'route' => 'classes.index'],
                ['label' => 'My Subjects', 'icon' => 'bi-journal-bookmark', 'route' => 'subjects.index'],
                ['label' => 'Students', 'icon' => 'bi-mortarboard', 'route' => 'students.index'],
                ['label' => 'Attendance', 'icon' => 'bi-check2-square'],
                ['label' => 'Homework', 'icon' => 'bi-journal-text'],
                ['label' => 'Quiz', 'icon' => 'bi-patch-question'],
                ['label' => 'Timetable', 'icon' => 'bi-calendar3'],
            ],
        ],
        'class-teacher' => [
            'Main' => [['label' => 'Dashboard', 'icon' => 'bi-grid-1x2-fill', 'route' => 'dashboard']],
            'My Class' => [
                ['label' => 'Class Overview', 'icon' => 'bi-easel2', 'route' => 'classes.index'],
                ['label' => 'Students', 'icon' => 'bi-people', 'route' => 'students.index'],
                ['label' => 'Attendance', 'icon' => 'bi-check2-square'],
                ['label' => 'Student Roles', 'icon' => 'bi-person-badge'],
                ['label' => 'Performance', 'icon' => 'bi-graph-up-arrow'],
            ],
            'Teaching' => [
                ['label' => 'My Subjects', 'icon' => 'bi-journal-bookmark', 'route' => 'subjects.index'],
                ['label' => 'Homework', 'icon' => 'bi-journal-text'],
                ['label' => 'Quiz', 'icon' => 'bi-patch-question'],
                ['label' => 'Timetable', 'icon' => 'bi-calendar3'],
            ],
        ],
        'student' => [
            'Main' => [['label' => 'Dashboard', 'icon' => 'bi-grid-1x2-fill', 'route' => 'dashboard']],
            'Learning' => [
                ['label' => 'My Subjects', 'icon' => 'bi-journal-bookmark', 'route' => 'subjects.index'],
                ['label' => 'Homework', 'icon' => 'bi-journal-text'],
                ['label' => 'Quiz', 'icon' => 'bi-patch-question'],
                ['label' => 'Timetable', 'icon' => 'bi-calendar3'],
                ['label' => 'Attendance', 'icon' => 'bi-check2-circle'],
                ['label' => 'Results', 'icon' => 'bi-award'],
            ],
        ],
    ],
];
