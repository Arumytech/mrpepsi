<?php
namespace App\Models;

use App\Core\Model;

class Period extends Model {
    protected string $table = 'periods';

    public const MONTHS_ID = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];

    public function getActive(): array {
        return $this->where('is_active', 1, 'start_date DESC');
    }

    /**
     * Periode yang ditonjolkan: periode aktif pilihan admin,
     * atau otomatis periode terdekat jika belum ada / sudah lewat.
     */
    public function getLatestActive(): ?array {
        $stmt = $this->db->query("SELECT * FROM `{$this->table}` WHERE is_active = 1 ORDER BY start_date ASC LIMIT 1");
        $row = $stmt->fetch();
        if ($row) {
            return $row;
        }
        return $this->getNextStart();
    }

    /**
     * Selalu sediakan periode tanggal 10 & 25 untuk bulan berjalan
     * sampai beberapa bulan ke depan (rolling, tanpa input manual).
     */
    public function ensureGenerated(int $monthsBack = 1, int $monthsAhead = 3): void {
        for ($i = -$monthsBack; $i <= $monthsAhead; $i++) {
            $base = new \DateTime(date('Y-m-01'));
            if ($i !== 0) {
                $base->modify(($i > 0 ? '+' : '') . $i . ' month');
            }
            $lastDay = (int)$base->format('t');

            foreach ([10, 25] as $day) {
                $dayOfMonth = min($day, $lastDay);
                $reg = (clone $base)->setDate((int)$base->format('Y'), (int)$base->format('n'), $dayOfMonth);
                $registrationDate = $reg->format('Y-m-d');
                $expectedName = sprintf(
                    'Periode %d %s %d',
                    $dayOfMonth,
                    self::MONTHS_ID[(int)$reg->format('n')],
                    (int)$reg->format('Y')
                );

                [$startDate, $endDate] = self::learningDates($reg);

                // Cocokkan per tipe periode + bulan (tanggal belajar bisa bergeser dari tgl 10/25)
                $exists = $this->db->prepare(
                    "SELECT id, name, start_date FROM `{$this->table}`
                     WHERE period_day = ? AND YEAR(start_date) = ? AND MONTH(start_date) = ? LIMIT 1"
                );
                $exists->execute([$day, (int)$reg->format('Y'), (int)$reg->format('n')]);
                $row = $exists->fetch();

                if ($row) {
                    $set = [];

                    // Rapikan nama hasil seed lama (bulan Inggris -> Indonesia)
                    if ($row['name'] !== $expectedName
                        && preg_match('/^Periode\s+\d+\s+\w+\s+\d{4}$/', $row['name'])) {
                        $set['name'] = $expectedName;
                    }

                    // Geser tanggal belajar pada periode lama yang masih di tgl 10/25 (akhir pekan)
                    if ($row['start_date'] === $registrationDate && $row['start_date'] !== $startDate) {
                        $set['start_date'] = $startDate;
                        $set['end_date'] = $endDate;
                    }

                    if ($set) {
                        $cols = implode(', ', array_map(fn($c) => "`{$c}` = ?", array_keys($set)));
                        $upd = $this->db->prepare("UPDATE `{$this->table}` SET {$cols} WHERE id = ?");
                        $upd->execute([...array_values($set), $row['id']]);
                    }
                    continue;
                }

                $this->create([
                    'name' => $expectedName,
                    'period_day' => $day,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'is_active' => 0
                ]);
            }
        }

        $this->autoActivate();
    }

    /**
     * Tanggal mulai & selesai belajar untuk periode.
     *
     * Pendaftaran tetap tanggal 10 / 25, namun mulai belajar digeser ke
     * Senin bila tanggal tersebut jatuh pada Sabtu/Minggu. Satu siklus kelas
     * = 2 minggu (10 sesi Senin-Jumat) dan tanggal selesai tidak pernah
     * jatuh pada akhir pekan.
     *
     * @return array{0:string,1:string} [start_date, end_date]
     */
    public static function learningDates(\DateTime $registration): array {
        $start = clone $registration;

        // 1=Senin ... 6=Sabtu, 7=Minggu
        $dow = (int)$start->format('N');
        if ($dow === 6) {
            $start->modify('+2 days');
        } elseif ($dow === 7) {
            $start->modify('+1 day');
        }

        $end = (clone $start)->modify('+13 days');
        while ((int)$end->format('N') >= 6) {
            $end->modify('-1 day');
        }

        return [$start->format('Y-m-d'), $end->format('Y-m-d')];
    }

    /**
     * Jika periode aktif belum ada atau sudah lewat masa kelasnya,
     * otomatis alihkan ke periode tanggal 10/25 berikutnya.
     */
    public function autoActivate(): void {
        $today = date('Y-m-d');

        // Pastikan hanya satu periode yang aktif
        $countActive = (int)$this->db->query("SELECT COUNT(*) FROM `{$this->table}` WHERE is_active = 1")->fetchColumn();
        if ($countActive > 1) {
            $keep = $this->db->query("SELECT id FROM `{$this->table}` WHERE is_active = 1 ORDER BY start_date ASC LIMIT 1")->fetchColumn();
            $this->db->exec("UPDATE `{$this->table}` SET is_active = 0");
            $stmt = $this->db->prepare("UPDATE `{$this->table}` SET is_active = 1 WHERE id = ?");
            $stmt->execute([$keep]);
        }

        $stmt = $this->db->prepare("SELECT id, start_date, end_date FROM `{$this->table}` WHERE is_active = 1 ORDER BY start_date ASC LIMIT 1");
        $stmt->execute();
        $active = $stmt->fetch();

        if ($active && $active['end_date'] >= $today) {
            return;
        }

        $next = $this->getNextStart();
        if (!$next || (int)$next['id'] === (int)($active['id'] ?? 0)) {
            return;
        }

        $this->db->exec("UPDATE `{$this->table}` SET is_active = 0");
        $stmt = $this->db->prepare("UPDATE `{$this->table}` SET is_active = 1 WHERE id = ?");
        $stmt->execute([$next['id']]);
    }

    /**
     * Daftar periode mulai dari hari ini ke depan (untuk pendaftaran & banner).
     */
    public function getUpcoming(int $limit = 6): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}` WHERE start_date >= ? ORDER BY start_date ASC LIMIT " . (int)$limit
        );
        $stmt->execute([date('Y-m-d')]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Periode paling dekat yang tanggal mulainya belum lewat,
     * fallback ke periode yang sedang berjalan.
     */
    public function getNextStart(): ?array {
        $upcoming = $this->getUpcoming(1);
        if ($upcoming) {
            return $upcoming[0];
        }

        $stmt = $this->db->prepare("SELECT * FROM `{$this->table}` WHERE end_date >= ? ORDER BY start_date DESC LIMIT 1");
        $stmt->execute([date('Y-m-d')]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Sisa hari sampai tanggal mulai periode (0 = hari ini).
     */
    public static function daysUntil(string $startDate): int {
        $today = new \DateTime(date('Y-m-d'));
        $start = new \DateTime($startDate);
        return (int)$today->diff($start)->days * ($start < $today ? -1 : 1);
    }
}
