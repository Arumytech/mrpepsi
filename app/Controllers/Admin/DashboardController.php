<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Program;
use App\Models\Tutor;
use App\Models\Schedule;
use App\Models\Attendance;
use App\Models\PosTransaction;
use App\Models\Period;
use App\Models\Registration;
use App\Models\Student;
use App\Models\Room;

class DashboardController extends Controller {
    public function index(): void {
        $this->requireAuth(['admin']);

        $programModel = new Program();
        $tutorModel = new Tutor();
        $scheduleModel = new Schedule();
        $periodModel = new Period();
        $posModel = new PosTransaction();
        $regModel = new Registration();
        $studentModel = new Student();
        $roomModel = new Room();

        $activePeriod = $periodModel->getLatestActive();
        $totalPrograms = count($programModel->all());
        $totalTutors = count($tutorModel->all());
        $totalSchedules = count($scheduleModel->getFullSchedules($activePeriod['id'] ?? null));

        // Students & room occupancy
        $studentStats = $studentModel->getStats();
        $activeStudents = (int)($studentStats['active'] ?? 0);
        $occupancy = $roomModel->getRoomOccupancy($activePeriod['id'] ?? null);

        // Registrations summary
        $regStats = $regModel->getStats();
        $recentPendingRegs = $regModel->getFiltered('pending');

        // Today's attendance summary
        $today = date('Y-m-d');
        $attModel = new Attendance();
        $todayAttendances = $attModel->getDailyAttendance($today, $activePeriod['id'] ?? null);
        $presentCount = 0;
        $substitutedCount = 0;
        $absentCount = 0;
        foreach ($todayAttendances as $att) {
            if ($att['status'] === 'present') $presentCount++;
            elseif ($att['status'] === 'substituted') $substitutedCount++;
            elseif ($att['status'] === 'absent') $absentCount++;
        }

        // Students who have not paid (unpaid / partial)
        $unpaidStudents = $studentModel->getUnpaidStudents(10);
        $unpaidSummary = $studentModel->getUnpaidSummary();

        // Today's Canteen Sales
        $todayPos = $posModel->getProfitLossReport($today, $today);

        // Recent Schedules
        $recentSchedules = array_slice($scheduleModel->getFullSchedules($activePeriod['id'] ?? null), 0, 6);

        $this->view('admin.dashboard', [
            'totalPrograms' => $totalPrograms,
            'totalTutors' => $totalTutors,
            'totalSchedules' => $totalSchedules,
            'activeStudents' => $activeStudents,
            'occupancy' => $occupancy,
            'unpaidStudents' => $unpaidStudents,
            'unpaidSummary' => $unpaidSummary,
            'regStats' => $regStats,
            'recentPendingRegs' => $recentPendingRegs,
            'activePeriod' => $activePeriod,
            'today' => $today,
            'todayAttendances' => $todayAttendances,
            'presentCount' => $presentCount,
            'substitutedCount' => $substitutedCount,
            'absentCount' => $absentCount,
            'todayPos' => $todayPos['summary'],
            'recentSchedules' => $recentSchedules
        ], 'admin');
    }
}
