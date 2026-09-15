<?php

use App\Enums\Role;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MajorController;
use App\Http\Controllers\Admin\StudentController as AdminStudentController;
use App\Http\Controllers\Admin\TeacherController as AdminTeacherController;
use App\Http\Controllers\AttendancePrivateFileController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredStudentController;
use App\Http\Controllers\PrivateFileController as SharedPrivateFileController;
use App\Http\Controllers\Student\AttendanceController as StudentAttendanceController;
use App\Http\Controllers\Student\DailyReportController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\LeaveRequestController;
use App\Http\Controllers\Student\PrivateFileController;
use App\Http\Controllers\Student\ProfileController as StudentDashboardProfileController;
use App\Http\Controllers\StudentApprovalController;
use App\Http\Controllers\StudentProfileController;
use App\Http\Controllers\StudentRecapExportController;
use App\Http\Controllers\Teacher\AttendanceController as TeacherAttendanceController;
use App\Http\Controllers\Teacher\DailyReportController as TeacherDailyReportController;
use App\Http\Controllers\Teacher\DashboardController as TeacherDashboardController;
use App\Http\Controllers\Teacher\LeaveRequestController as TeacherLeaveRequestController;
use App\Http\Controllers\Teacher\LeaveRequestReviewController;
use App\Http\Controllers\Teacher\RecapController;
use App\Http\Controllers\Teacher\TeacherNoteController;
use App\Http\Controllers\TeacherProfileController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request): RedirectResponse {
    if ($request->user()?->isAdmin()) {
        return to_route('admin.dashboard');
    }

    if ($request->user()?->isActive() && $request->user()->role === Role::Student) {
        return to_route('student.dashboard');
    }

    if ($request->user()?->isActive() && $request->user()->role === Role::Teacher) {
        return to_route('teacher.dashboard');
    }

    return to_route($request->user() === null ? 'login' : 'account');
})->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [RegisteredStudentController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredStudentController::class, 'store'])->middleware('throttle:registration');
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::get('/account', AccountController::class)->name('account');
    Route::get('/student-profiles/{studentProfile}', [StudentProfileController::class, 'show'])->name('student-profiles.show');
    Route::patch('/student-profiles/{studentProfile}', [StudentProfileController::class, 'update'])
        ->middleware('throttle:sensitive')->name('student-profiles.update');
    Route::get('/teacher-profiles/{teacherProfile}', [TeacherProfileController::class, 'show'])->name('teacher-profiles.show');
    Route::get('/berkas/laporan/{dailyReport}', [SharedPrivateFileController::class, 'dailyReportPhoto'])->name('private.daily-reports.photo');
    Route::get('/berkas/izin/{leaveRequest}', [SharedPrivateFileController::class, 'leaveRequestFile'])->name('private.leave-requests.file');
    Route::get('/berkas/absensi/{attendance}/{phase}', AttendancePrivateFileController::class)
        ->whereIn('phase', ['check-in', 'check-out'])->name('private.attendances.selfie');
    Route::get('/rekap-murid/{studentProfile}/excel', StudentRecapExportController::class)
        ->middleware('throttle:sensitive')->name('student-recaps.export');
});

Route::middleware(['auth', 'active', 'teacher'])->prefix('guru')->as('teacher.')->group(function (): void {
    Route::get('/', TeacherDashboardController::class)->name('dashboard');
    Route::get('/kehadiran', TeacherAttendanceController::class)->name('attendance');
    Route::get('/laporan-harian', [TeacherDailyReportController::class, 'index'])->name('daily-reports.index');
    Route::get('/laporan-harian/{dailyReport}', [TeacherDailyReportController::class, 'show'])->name('daily-reports.show');
    Route::get('/izin-sakit', [TeacherLeaveRequestController::class, 'index'])->name('leave-requests.index');
    Route::patch('/izin-sakit/{leaveRequest}', LeaveRequestReviewController::class)->middleware('throttle:sensitive')->name('leave-requests.review');
    Route::get('/rekap', RecapController::class)->name('recap');
    Route::resource('catatan-murid', TeacherNoteController::class)
        ->except(['show', 'create'])->names('notes')->parameters(['catatan-murid' => 'teacherNote'])
        ->middlewareFor(['store', 'update', 'destroy'], 'throttle:sensitive');
});

Route::middleware(['auth', 'active', 'student'])->prefix('siswa')->as('student.')->group(function (): void {
    Route::get('/', StudentDashboardController::class)->name('dashboard');
    Route::get('/profil', [StudentDashboardProfileController::class, 'show'])->name('profile.show');
    Route::patch('/profil', [StudentDashboardProfileController::class, 'update'])
        ->middleware('throttle:sensitive')->name('profile.update');
    Route::get('/profil/foto', [PrivateFileController::class, 'profilePhoto'])->name('profile.photo');
    Route::get('/absensi', [StudentAttendanceController::class, 'index'])->name('attendance');
    Route::post('/absensi/masuk', [StudentAttendanceController::class, 'checkIn'])
        ->middleware('throttle:sensitive')->name('attendance.check-in');
    Route::post('/absensi/pulang', [StudentAttendanceController::class, 'checkOut'])
        ->middleware('throttle:sensitive')->name('attendance.check-out');
    Route::get('/riwayat', [StudentDashboardController::class, 'history'])->name('history');
    Route::get('/laporan-harian/{dailyReport}/foto', [PrivateFileController::class, 'dailyReportPhoto'])->name('daily-reports.photo');
    Route::resource('laporan-harian', DailyReportController::class)
        ->except('show')->names('daily-reports')->parameters(['laporan-harian' => 'dailyReport'])
        ->middlewareFor(['store', 'update', 'destroy'], 'throttle:sensitive');
    Route::get('/izin-sakit/{leaveRequest}/lampiran', [PrivateFileController::class, 'leaveRequestFile'])->name('leave-requests.file');
    Route::resource('izin-sakit', LeaveRequestController::class)
        ->only(['index', 'create', 'store'])->names('leave-requests')
        ->middlewareFor('store', 'throttle:sensitive');
});

Route::middleware(['auth', 'active', 'admin'])->prefix('admin')->as('admin.')->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/pendaftaran-murid', [StudentApprovalController::class, 'index'])->name('registrations.index');
    Route::post('/pendaftaran-murid/{student}/approve', [StudentApprovalController::class, 'store'])
        ->middleware('throttle:sensitive')->name('registrations.approve');
    Route::post('/pendaftaran-murid/{student}/reject', [StudentApprovalController::class, 'reject'])
        ->middleware('throttle:sensitive')->name('registrations.reject');
    Route::get('/murid', [AdminStudentController::class, 'index'])->name('students.index');
    Route::get('/murid/{student}', [AdminStudentController::class, 'show'])->name('students.show');
    Route::get('/murid/{student}/edit', [AdminStudentController::class, 'edit'])->name('students.edit');
    Route::patch('/murid/{student}', [AdminStudentController::class, 'update'])->middleware('throttle:sensitive')->name('students.update');
    Route::delete('/murid/{student}', [AdminStudentController::class, 'destroy'])->middleware('throttle:sensitive')->name('students.destroy');
    Route::resource('guru', AdminTeacherController::class)->names('teachers')->parameters(['guru' => 'teacher'])
        ->middlewareFor(['store', 'update', 'destroy'], 'throttle:sensitive');
    Route::resource('jurusan', MajorController::class)->except('show')->names('majors')->parameters(['jurusan' => 'major'])
        ->middlewareFor(['store', 'update', 'destroy'], 'throttle:sensitive');
});

Route::middleware(['auth', 'active', 'admin'])->group(function (): void {
    Route::get('/admin/student-approvals', [StudentApprovalController::class, 'index'])->name('student-approvals.index');
    Route::post('/admin/students/{student}/approval', [StudentApprovalController::class, 'store'])
        ->middleware('throttle:sensitive')->name('student-approvals.store');
});
