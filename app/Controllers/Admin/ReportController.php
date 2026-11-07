<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Student;
use App\Models\Program;
use App\Models\Setting;
use App\Models\PosTransaction;
use App\Models\Expense;
use App\Models\CanteenMonthlyBalance;
use App\Models\Registration;

class ReportController extends Controller {

    // ──────────────────────────────────────────────────
    // LAPORAN DATA SISWA — printable report
    // ──────────────────────────────────────────────────
    public function students(): void {
        $this->requireAuth(['admin']);

        $model        = new Student();
        $settingModel = new Setting();

        $status    = $_GET['status'] ?? 'all';
        $search    = trim($_GET['search'] ?? '');
        $period    = trim($_GET['period'] ?? '');
        $startDate = trim($_GET['start_date'] ?? '');
        $endDate   = trim($_GET['end_date'] ?? '');

        if (!empty($startDate) && !empty($endDate) && $startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $rows = $model->queryReport([
            'status'     => $status,
            'search'     => $search,
            'period'     => $period,
            'start_date' => $startDate,
            'end_date'   => $endDate,
        ]);

        $summary = [
            'total'          => count($rows),
            'total_programs' => 0,
            'active'         => 0,
            'graduated'      => 0,
            'dropped'        => 0,
            'total_fee'      => 0.0,
            'total_paid'     => 0.0,
            'total_due'      => 0.0,
        ];
        foreach ($rows as $r) {
            $summary['total_fee']  += (float)$r['total_fee'];
            $summary['total_paid'] += (float)$r['paid_amount'];
            if ($r['status'] === 'active')         $summary['active']++;
            elseif ($r['status'] === 'graduated')  $summary['graduated']++;
            elseif ($r['status'] === 'dropped')    $summary['dropped']++;

            $progs = json_decode($r['programs_json'] ?? '[]', true) ?: [];
            $summary['total_programs'] += max(1, count($progs));
        }
        $summary['total_due'] = max(0, $summary['total_fee'] - $summary['total_paid']);

        // Ambil data pendaftaran siswa untuk melengkapi sesi jam & periode per program
        $regModel = new Registration();
        $allRegs = $regModel->all('id DESC');
        $regsById = [];
        $regsByPhone = [];
        foreach ($allRegs as $reg) {
            $regsById[$reg['id']] = $reg;
            $cleanPhone = preg_replace('/[^0-9]/', '', (string)$reg['phone']);
            if ($cleanPhone !== '') {
                $regsByPhone[$cleanPhone][] = $reg;
            }
        }

        $this->view('admin.reports.students', [
            'rows'        => $rows,
            'summary'     => $summary,
            'regsById'    => $regsById,
            'regsByPhone' => $regsByPhone,
            'periods'     => $model->getDistinctPeriods(),
            'settings'    => $settingModel->getAllKeyValue(),
            'filters'     => [
                'status'     => $status,
                'search'     => $search,
                'period'     => $period,
                'start_date' => $startDate,
                'end_date'   => $endDate,
            ],
            'startDate'   => $startDate,
            'endDate'     => $endDate,
        ], '');
    }

    // ──────────────────────────────────────────────────
    // LAPORAN KEUANGAN KURSUS — printable report
    // ──────────────────────────────────────────────────
    public function finance(): void {
        $this->requireAuth(['admin']);

        $model        = new Student();
        $programModel = new Program();
        $settingModel = new Setting();

        $period        = trim($_GET['period'] ?? '');
        $paymentStatus = $_GET['payment_status'] ?? 'all';
        $paymentMethod = $_GET['payment_method'] ?? 'all';
        $startDate     = trim($_GET['start_date'] ?? '');
        $endDate       = trim($_GET['end_date'] ?? '');

        $rows = $model->queryReport([
            'status'         => 'all',
            'period'         => $period,
            'payment_status' => $paymentStatus,
            'payment_method' => $paymentMethod,
            'start_date'     => $startDate,
            'end_date'       => $endDate,
        ]);

        $groupMap = $programModel->getReportGroupMap();

        // Tiga bagian laporan keuangan kursus
        $groups = [
            'online'    => ['key' => 'online',    'label' => 'Program Kelas Online',  'total_fee' => 0.0, 'total_paid' => 0.0, 'total_due' => 0.0, 'count' => 0, 'total_programs' => 0, 'programs' => [], 'collection_rate' => 0],
            'offline'   => ['key' => 'offline',   'label' => 'Program Kelas Offline', 'total_fee' => 0.0, 'total_paid' => 0.0, 'total_due' => 0.0, 'count' => 0, 'total_programs' => 0, 'programs' => [], 'collection_rate' => 0],
            'non_kelas' => ['key' => 'non_kelas', 'label' => 'Non Kelas',             'total_fee' => 0.0, 'total_paid' => 0.0, 'total_due' => 0.0, 'count' => 0, 'total_programs' => 0, 'programs' => [], 'collection_rate' => 0],
        ];
        $groupStudents = ['online' => [], 'offline' => [], 'non_kelas' => []];

        $summary = [
            'count'          => count($rows),
            'total_programs' => 0,
            'total_fee'      => 0.0,
            'total_paid'     => 0.0,
            'total_due'      => 0.0,
            'total_tunai'    => 0.0,
            'total_transfer' => 0.0,
            'paid'           => 0,
            'partial'        => 0,
            'unpaid'         => 0,
        ];

        foreach ($rows as $r) {
            $fee  = (float)$r['total_fee'];
            $paid = (float)$r['paid_amount'];

            $summary['total_fee']  += $fee;
            $summary['total_paid'] += $paid;
            $summary['total_due']  += max(0, $fee - $paid);

            if ($r['payment_method'] === 'transfer') {
                $summary['total_transfer'] += $paid;
            } else {
                $summary['total_tunai'] += $paid;
            }

            if ($r['payment_status'] === 'paid')        $summary['paid']++;
            elseif ($r['payment_status'] === 'partial') $summary['partial']++;
            else                                        $summary['unpaid']++;

            $progs = json_decode($r['programs_json'] ?? '[]', true) ?: [];
            $summary['total_programs'] += max(1, count($progs));

            // Tagihan siswa ini dikelompokkan per grup laporan
            $studentGroupFee   = [];
            $studentTotalPrice = 0.0;
            foreach ($progs as $p) {
                $pid = (int)($p['id'] ?? 0);
                $grp = $groupMap[$pid] ?? 'offline';
                if (!isset($groups[$grp])) {
                    $grp = 'offline';
                }
                $price = (float)($p['price'] ?? 0);
                $studentGroupFee[$grp] = ($studentGroupFee[$grp] ?? 0) + $price;
                $studentTotalPrice += $price;

                $title = $p['title'] ?? ($p['code'] ?? 'Lainnya');
                if (!isset($groups[$grp]['programs'][$title])) {
                    $groups[$grp]['programs'][$title] = ['title' => $title, 'count' => 0, 'fee' => 0.0];
                }
                $groups[$grp]['programs'][$title]['count']++;
                $groups[$grp]['programs'][$title]['fee'] += $price;
                $groups[$grp]['total_programs']++;
            }

            // Tanpa rincian program: alokasikan ke Kelas Offline agar total tetap konsisten
            if (empty($studentGroupFee) && $fee > 0) {
                $studentGroupFee['offline'] = $fee;
                $studentTotalPrice = $fee;
            }

            foreach ($studentGroupFee as $grp => $grpFee) {
                $share   = $studentTotalPrice > 0 ? ($grpFee / $studentTotalPrice) : 0;
                $grpPaid = $paid * $share;

                $groups[$grp]['total_fee']  += $grpFee;
                $groups[$grp]['total_paid'] += $grpPaid;
                $groups[$grp]['total_due']  += max(0, $grpFee - $grpPaid);

                if (!isset($groupStudents[$grp][$r['id']])) {
                    $groupStudents[$grp][$r['id']] = true;
                    $groups[$grp]['count']++;
                }
            }
        }

        $summary['collection_rate'] = $summary['total_fee'] > 0
            ? round(($summary['total_paid'] / $summary['total_fee']) * 100, 1)
            : 0;

        foreach ($groups as $k => $g) {
            $groups[$k]['collection_rate'] = $g['total_fee'] > 0
                ? round(($g['total_paid'] / $g['total_fee']) * 100, 1)
                : 0;
            $groups[$k]['programs'] = array_values($g['programs']);
        }

        $this->view('admin.reports.finance', [
            'rows'     => $rows,
            'summary'  => $summary,
            'groups'   => $groups,
            'periods'  => $model->getDistinctPeriods(),
            'settings' => $settingModel->getAllKeyValue(),
            'filters'  => [
                'period'         => $period,
                'payment_status' => $paymentStatus,
                'payment_method' => $paymentMethod,
                'start_date'     => $startDate,
                'end_date'       => $endDate,
            ],
        ], '');
    }

    // ──────────────────────────────────────────────────
    // LAPORAN KEUANGAN GABUNGAN — Kursus + Pengeluaran Operasional
    // ──────────────────────────────────────────────────
    public function combined(): void {
        $this->requireAuth(['admin']);

        $studentModel = new Student();
        $settingModel = new Setting();

        $period        = trim($_GET['period'] ?? '');
        $paymentStatus = $_GET['payment_status'] ?? 'all';
        $paymentMethod = $_GET['payment_method'] ?? 'all';
        $startDate     = trim($_GET['start_date'] ?? date('Y-m-01'));
        $endDate       = trim($_GET['end_date'] ?? date('Y-m-d'));

        // ── Pendapatan Kursus ──
        $rows = $studentModel->queryReport([
            'status'         => 'all',
            'period'         => $period,
            'payment_status' => $paymentStatus,
            'payment_method' => $paymentMethod,
            'start_date'     => $startDate,
            'end_date'       => $endDate,
        ]);

        $course = [
            'count'          => count($rows),
            'total_fee'      => 0.0,
            'total_paid'     => 0.0,
            'total_due'      => 0.0,
            'total_tunai'    => 0.0,
            'total_transfer' => 0.0,
            'paid'           => 0,
            'partial'        => 0,
            'unpaid'         => 0,
        ];

        $programs = [];
        foreach ($rows as $r) {
            $fee  = (float)$r['total_fee'];
            $paid = (float)$r['paid_amount'];

            $course['total_fee']  += $fee;
            $course['total_paid'] += $paid;
            $course['total_due']  += max(0, $fee - $paid);

            if ($r['payment_method'] === 'transfer') {
                $course['total_transfer'] += $paid;
            } else {
                $course['total_tunai'] += $paid;
            }

            if ($r['payment_status'] === 'paid')        $course['paid']++;
            elseif ($r['payment_status'] === 'partial') $course['partial']++;
            else                                        $course['unpaid']++;

            $progs = json_decode($r['programs_json'] ?? '[]', true) ?: [];
            foreach ($progs as $p) {
                $key = $p['title'] ?? ($p['code'] ?? 'Lainnya');
                if (!isset($programs[$key])) {
                    $programs[$key] = ['title' => $key, 'count' => 0, 'fee' => 0.0];
                }
                $programs[$key]['count']++;
                $programs[$key]['fee'] += (float)($p['price'] ?? 0);
            }
        }
        $course['collection_rate'] = $course['total_fee'] > 0
            ? round(($course['total_paid'] / $course['total_fee']) * 100, 1)
            : 0;

        // ── Pengeluaran Operasional ──
        $expenseModel = new Expense();
        try {
            $expenseModel->ensureSchema();
        } catch (\Throwable $e) {
            // Database belum siap — abaikan
        }
        $expenseSummary  = $expenseModel->summary($startDate ?: '', $endDate ?: '');
        $expenseCategories = $expenseModel->getCategoryTotals($startDate ?: '', $endDate ?: '');

        // ── Rekonsiliasi Gabungan (Kursus + Pengeluaran, tanpa Kantin) ──
        $incomeCourse  = (float)$course['total_paid'];
        $totalExpense  = (float)$expenseSummary['amount'];
        $combined = [
            'income_course'  => $incomeCourse,
            'total_income'   => $incomeCourse,
            'total_expense'  => $totalExpense,
            'net'            => $incomeCourse - $totalExpense,
            'receivable'     => (float)$course['total_due'],
        ];

        $this->view('admin.reports.combined', [
            'rows'        => $rows,
            'course'      => $course,
            'programs'    => array_values($programs),
            'expenses'    => $expenseSummary,
            'expenseCategories' => $expenseCategories,
            'combined'    => $combined,
            'periods'     => $studentModel->getDistinctPeriods(),
            'settings'    => $settingModel->getAllKeyValue(),
            'filters'     => [
                'period'         => $period,
                'payment_status' => $paymentStatus,
                'payment_method' => $paymentMethod,
                'start_date'     => $startDate,
                'end_date'       => $endDate,
            ],
        ], '');
    }

    // ──────────────────────────────────────────────────
    // LAPORAN PEPSI FOOD & DRINK — printable report (Kantin)
    // ──────────────────────────────────────────────────
    public function canteen(): void {
        $this->requireAuth(['admin']);

        $posModel     = new PosTransaction();
        $settingModel = new Setting();
        $expenseModel = new Expense();

        $startDate = trim($_GET['start_date'] ?? date('Y-m-01'));
        $endDate   = trim($_GET['end_date'] ?? date('Y-m-d'));

        $report      = $posModel->getProfitLossReport($startDate ?: null, $endDate ?: null);
        $summary     = $report['summary'];
        $topProducts = $report['top_products'];

        $canteenExpenses = $expenseModel->getCanteenExpenses($startDate ?: '', $endDate ?: '');
        $grossProfit = (float)($summary['gross_profit'] ?? 0);
        $canteenExpenseTotal = (float)($canteenExpenses['total'] ?? 0);
        $netProfit = $grossProfit - $canteenExpenseTotal;
        $totalRevenue = (float)($summary['total_revenue'] ?? 0);

        $grossMargin = $totalRevenue > 0
            ? round(($grossProfit / $totalRevenue) * 100, 1)
            : 0;
        $netMargin = $totalRevenue > 0
            ? round(($netProfit / $totalRevenue) * 100, 1)
            : 0;

        $this->view('admin.reports.canteen', [
            'summary'             => $summary,
            'topProducts'         => $topProducts,
            'canteenExpenses'     => $canteenExpenses,
            'grossProfit'         => $grossProfit,
            'grossMargin'         => $grossMargin,
            'canteenExpenseTotal' => $canteenExpenseTotal,
            'netProfit'           => $netProfit,
            'netMargin'           => $netMargin,
            'margin'              => $grossMargin,
            'settings'            => $settingModel->getAllKeyValue(),
            'filters'             => [
                'start_date' => $startDate,
                'end_date'   => $endDate,
            ],
        ], '');
    }

    // ──────────────────────────────────────────────────
    // LAPORAN BULANAN PEPSI FOOD & DRINK (Berlanjut Tiap Bulan) — Printable A4
    // ──────────────────────────────────────────────────
    public function canteenMonthly(): void {
        $this->requireAuth(['admin']);

        $balanceModel = new CanteenMonthlyBalance();
        $settingModel = new Setting();

        $month = trim($_GET['month'] ?? date('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }

        $report = $balanceModel->getMonthReport($month);

        $this->view('admin.reports.canteen_monthly', [
            'report'      => $report,
            'current'     => $report['current'],
            'rolling'     => $report['rolling'],
            'yearMonth'   => $report['year_month'],
            'monthLabel'  => $report['month_label'],
            'prevMonth'   => $report['prev_month'],
            'nextMonth'   => $report['next_month'],
            'settings'    => $settingModel->getAllKeyValue(),
        ], '');
    }
}
