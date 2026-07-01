<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Admin Dashboard') }} - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: system-ui, -apple-system, sans-serif; background: #f5f5f5; }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        .header { background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header h1 { color: #333; font-size: 24px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .stat-card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .stat-card h3 { color: #666; font-size: 14px; margin-bottom: 10px; }
        .stat-card .value { font-size: 32px; font-weight: bold; color: #2563eb; }
        .section { background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .section h2 { color: #333; font-size: 18px; margin-bottom: 15px; border-bottom: 2px solid #e5e5e5; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e5e5; }
        th { background: #f9fafb; font-weight: 600; color: #374151; }
        tr:hover { background: #f9fafb; }
        .badge { padding: 4px 12px; border-radius: 9999px; font-size: 12px; font-weight: 500; }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-info { background: #dbeafe; color: #1e40af; }
        .btn { padding: 8px 16px; border-radius: 6px; border: none; cursor: pointer; font-size: 14px; text-decoration: none; display: inline-block; }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-primary:hover { background: #1d4ed8; }
        [dir="rtl"] th, [dir="rtl"] td { text-align: right; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ __('Admin Dashboard') }}</h1>
            <p style="color: #666; margin-top: 5px;">{{ __('Welcome to Qader Academy Administration Panel') }}</p>
        </div>

        <!-- Statistics Overview -->
        <div class="stats-grid" id="statsGrid">
            <div class="stat-card">
                <h3>{{ __('Total Students') }}</h3>
                <div class="value" id="totalStudents">-</div>
            </div>
            <div class="stat-card">
                <h3>{{ __('Total Trainers') }}</h3>
                <div class="value" id="totalTrainers">-</div>
            </div>
            <div class="stat-card">
                <h3>{{ __('Total Courses') }}</h3>
                <div class="value" id="totalCourses">-</div>
            </div>
            <div class="stat-card">
                <h3>{{ __('Pending Reviews') }}</h3>
                <div class="value" id="pendingReviews">-</div>
            </div>
        </div>

        <!-- Pending Course Approvals -->
        <div class="section">
            <h2>{{ __('Pending Course Approvals') }}</h2>
            <table id="pendingCoursesTable">
                <thead>
                    <tr>
                        <th>{{ __('Course') }}</th>
                        <th>{{ __('Trainer') }}</th>
                        <th>{{ __('Submitted') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td colspan="4" style="text-align: center; color: #666;">{{ __('Loading...') }}</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Recent Students -->
        <div class="section">
            <h2>{{ __('Recent Students') }}</h2>
            <table id="recentStudentsTable">
                <thead>
                    <tr>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Email') }}</th>
                        <th>{{ __('Enrollments') }}</th>
                        <th>{{ __('Joined') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td colspan="4" style="text-align: center; color: #666;">{{ __('Loading...') }}</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Trainer Applications -->
        <div class="section">
            <h2>{{ __('Pending Trainer Applications') }}</h2>
            <table id="trainerApplicationsTable">
                <thead>
                    <tr>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Email') }}</th>
                        <th>{{ __('Applied') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td colspan="4" style="text-align: center; color: #666;">{{ __('Loading...') }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        const API_BASE = '/api';
        let authToken = localStorage.getItem('admin_token') || '';

        // Auth check
        function checkAuth() {
            if (!authToken) {
                window.location.href = '/admin/login';
            }
        }

        // Fetch dashboard statistics
        async function fetchStats() {
            try {
                const response = await fetch(`${API_BASE}/admin/analytics/overview`, {
                    headers: { 'Authorization': `Bearer ${authToken}` }
                });
                if (response.ok) {
                    const data = await response.json();
                    document.getElementById('totalStudents').textContent = data.total_students || 0;
                    document.getElementById('totalTrainers').textContent = data.total_trainers || 0;
                    document.getElementById('totalCourses').textContent = data.total_courses || 0;
                    document.getElementById('pendingReviews').textContent = data.pending_courses || 0;
                }
            } catch (error) {
                console.error('Error fetching stats:', error);
            }
        }

        // Fetch pending courses
        async function fetchPendingCourses() {
            try {
                const response = await fetch(`${API_BASE}/admin/courses/pending`, {
                    headers: { 'Authorization': `Bearer ${authToken}` }
                });
                if (response.ok) {
                    const data = await response.json();
                    const tbody = document.querySelector('#pendingCoursesTable tbody');
                    if (data.courses && data.courses.length > 0) {
                        tbody.innerHTML = data.courses.map(course => `
                            <tr>
                                <td>${course.title_en || course.title_ar}</td>
                                <td>${course.trainer_name}</td>
                                <td>${new Date(course.created_at).toLocaleDateString()}</td>
                                <td>
                                    <button class="btn btn-primary" onclick="approveCourse(${course.id})">{{ __('Approve') }}</button>
                                    <button class="btn" style="background: #ef4444; color: white;" onclick="rejectCourse(${course.id})">{{ __('Reject') }}</button>
                                </td>
                            </tr>
                        `).join('');
                    } else {
                        tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; color: #666;">{{ __('No pending courses') }}</td></tr>';
                    }
                }
            } catch (error) {
                console.error('Error fetching pending courses:', error);
            }
        }

        // Fetch recent students
        async function fetchRecentStudents() {
            try {
                const response = await fetch(`${API_BASE}/admin/students?limit=10`, {
                    headers: { 'Authorization': `Bearer ${authToken}` }
                });
                if (response.ok) {
                    const data = await response.json();
                    const tbody = document.querySelector('#recentStudentsTable tbody');
                    if (data.students && data.students.length > 0) {
                        tbody.innerHTML = data.students.map(student => `
                            <tr>
                                <td>${student.name}</td>
                                <td>${student.email}</td>
                                <td>${student.enrollments_count || 0}</td>
                                <td>${new Date(student.created_at).toLocaleDateString()}</td>
                            </tr>
                        `).join('');
                    }
                }
            } catch (error) {
                console.error('Error fetching students:', error);
            }
        }

        // Fetch trainer applications
        async function fetchTrainerApplications() {
            try {
                const response = await fetch(`${API_BASE}/admin/trainers?status=pending&limit=10`, {
                    headers: { 'Authorization': `Bearer ${authToken}` }
                });
                if (response.ok) {
                    const data = await response.json();
                    const tbody = document.querySelector('#trainerApplicationsTable tbody');
                    if (data.trainers && data.trainers.length > 0) {
                        tbody.innerHTML = data.trainers.map(trainer => `
                            <tr>
                                <td>${trainer.name}</td>
                                <td>${trainer.email}</td>
                                <td>${new Date(trainer.created_at).toLocaleDateString()}</td>
                                <td>
                                    <button class="btn btn-primary" onclick="approveTrainer(${trainer.id})">{{ __('Approve') }}</button>
                                    <button class="btn" style="background: #ef4444; color: white;" onclick="rejectTrainer(${trainer.id})">{{ __('Reject') }}</button>
                                </td>
                            </tr>
                        `).join('');
                    } else {
                        tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; color: #666;">{{ __('No pending applications') }}</td></tr>';
                    }
                }
            } catch (error) {
                console.error('Error fetching trainer applications:', error);
            }
        }

        // Action functions
        async function approveCourse(courseId) {
            try {
                const response = await fetch(`${API_BASE}/admin/courses/${courseId}/approve`, {
                    method: 'POST',
                    headers: { 
                        'Authorization': `Bearer ${authToken}`,
                        'Content-Type': 'application/json'
                    }
                });
                if (response.ok) {
                    alert('{{ __('Course approved successfully!') }}');
                    fetchPendingCourses();
                    fetchStats();
                }
            } catch (error) {
                alert('{{ __('Error approving course') }}');
            }
        }

        async function approveTrainer(trainerId) {
            try {
                const response = await fetch(`${API_BASE}/admin/trainers/${trainerId}/approve`, {
                    method: 'POST',
                    headers: { 
                        'Authorization': `Bearer ${authToken}`,
                        'Content-Type': 'application/json'
                    }
                });
                if (response.ok) {
                    alert('{{ __('Trainer approved successfully!') }}');
                    fetchTrainerApplications();
                    fetchStats();
                }
            } catch (error) {
                alert('{{ __('Error approving trainer') }}');
            }
        }

        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', () => {
            checkAuth();
            fetchStats();
            fetchPendingCourses();
            fetchRecentStudents();
            fetchTrainerApplications();
        });
    </script>
</body>
</html>
