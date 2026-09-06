<?php
/**
 * ensureSessionsExist()
 * ------------------------------------------------------------
 * Every pool has a set of recurring daily time slots stored in
 * pool_schedules (e.g. Deep Blue: 07:00-12:00 and 14:00-19:00).
 *
 * This function makes sure that for the next $daysAhead days,
 * a matching row exists in `sessions` for each active schedule.
 * If a date doesn't have a session row yet, it creates one with
 * booked = 0 and capacity = the pool's full capacity.
 *
 * Effect: every new day automatically starts fully available.
 * The ONLY thing that ever reduces a session's `booked` count is
 * a real row being inserted into `bookings`. Nothing needs to be
 * "reset" — old full/empty sessions simply age out of the
 * upcoming-sessions view once their date is in the past.
 *
 * Call this once near the top of any page that reads sessions
 * (index.php, admin/dashboard.php, etc.) — it's cheap: it only
 * writes rows the first time a given date is requested each day.
 */
function ensureSessionsExist(PDO $pdo, int $daysAhead = 2): void
{
    $schedules = $pdo->query("
        SELECT ps.id AS schedule_id, ps.pool_id, ps.start_time, ps.end_time,
               COALESCE(ps.capacity_override, p.capacity) AS capacity
        FROM pool_schedules ps
        JOIN pools p ON p.id = ps.pool_id
        WHERE ps.is_active = 1 AND p.is_active = 1
    ")->fetchAll();

    if (empty($schedules)) {
        return;
    }

    $insertStmt = $pdo->prepare("
        INSERT IGNORE INTO sessions (pool_id, schedule_id, session_date, start_time, end_time, capacity, booked, status)
        VALUES (?, ?, ?, ?, ?, ?, 0, 'active')
    ");

    for ($dayOffset = 0; $dayOffset < $daysAhead; $dayOffset++) {
        $date = date('Y-m-d', strtotime("+{$dayOffset} day"));
        foreach ($schedules as $s) {
            $insertStmt->execute([
                $s['pool_id'],
                $s['schedule_id'],
                $date,
                $s['start_time'],
                $s['end_time'],
                $s['capacity'],
            ]);
        }
    }
}
