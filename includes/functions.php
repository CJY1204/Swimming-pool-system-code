<?php
require_once __DIR__ . '/session_init.php';

function formatDateLabel(string $dateStr): string
{
    $d = strtotime($dateStr);
    $today = strtotime('today');
    $tomorrow = strtotime('tomorrow');
    if ($d === $today) return 'Today';
    if ($d === $tomorrow) return 'Tomorrow';
    return date('D, j M Y', $d);
}

function formatTimeLabel(string $timeStr): string
{
    return date('g:i A', strtotime($timeStr));
}

function generateBookingReference(): string
{
    return 'SPB-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
}

/** Redirects to the shared login page if not logged in, preserving where to return to. */
function requireMemberLogin(): void
{
    if (empty($_SESSION['member_id'])) {
        $next = urlencode($_SERVER['REQUEST_URI']);
        header('Location: ' . BASE_URL . "/login.php?next={$next}");
        exit;
    }
}

function isMemberLoggedIn(): bool
{
    return !empty($_SESSION['member_id']);
}

function isAdminLoggedIn(): bool
{
    return !empty($_SESSION['admin_id']);
}

/** Redirects to the shared login page if not logged in as staff. */
function requireAdminLogin(): void
{
    if (empty($_SESSION['admin_id'])) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

/**
 * A booking is 'confirmed' only while it's still upcoming. Once the
 * session's end time has passed, display it as 'finished' instead —
 * this is computed on the fly, not stored, so it's always accurate
 * without needing a scheduled job to update old rows.
 */
function getBookingDisplayStatus(string $bookingStatus, string $sessionDate, string $endTime): string
{
    if ($bookingStatus === 'cancelled') {
        return 'cancelled';
    }
    $sessionEnd = strtotime($sessionDate . ' ' . $endTime);
    if ($sessionEnd !== false && $sessionEnd < time()) {
        return 'finished';
    }
    return 'confirmed';
}

/** Simple gauge percentage + left-count helper for display. */
function occupancy(int $capacity, int $booked): array
{
    $pct = $capacity > 0 ? (int) round(($booked / $capacity) * 100) : 0;
    $left = max(0, $capacity - $booked);
    return ['pct' => $pct, 'left' => $left];
}

/** Decorative ripple pattern shown over each pool card's photo/gradient. */
function rippleSVG(): string
{
    return '
    <svg class="ripple-svg" viewBox="0 0 200 160" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
      <circle cx="170" cy="20" r="14" fill="none" stroke="white" stroke-width="1.4"/>
      <circle cx="170" cy="20" r="26" fill="none" stroke="white" stroke-width="1"/>
      <circle cx="170" cy="20" r="38" fill="none" stroke="white" stroke-width="0.6"/>
      <circle cx="20" cy="130" r="10" fill="none" stroke="white" stroke-width="1.2"/>
      <circle cx="20" cy="130" r="22" fill="none" stroke="white" stroke-width="0.8"/>
    </svg>';
}
function renderGauge(int $pct): string
{
    $pct = max(0, min(100, $pct));
    return '
    <div class="gauge" role="img" aria-label="' . $pct . '% of this session booked">
      <div class="gauge-water" style="height:' . $pct . '%">
        <svg class="wave" viewBox="0 0 200 20" preserveAspectRatio="none"><path d="M0,10 Q25,0 50,10 T100,10 T150,10 T200,10 V20 H0 Z" fill="rgba(255,255,255,0.55)"/></svg>
        <svg class="wave wave-2" viewBox="0 0 200 20" preserveAspectRatio="none"><path d="M0,10 Q25,18 50,10 T100,10 T150,10 T200,10 V20 H0 Z" fill="rgba(255,255,255,0.35)"/></svg>
      </div>
      <div class="gauge-pct">' . $pct . '%</div>
    </div>';
}