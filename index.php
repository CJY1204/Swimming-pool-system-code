<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/session_generator.php';

// Make sure today's + tomorrow's sessions exist before we query them.
// This is what makes every new day "reset" to full capacity automatically.
ensureSessionsExist($pdo);

$pageTitle = 'Book a Session';

$pools = $pdo->query("SELECT * FROM pools WHERE is_active = 1 ORDER BY FIELD(size,'big','small'), id")->fetchAll();

$sessionStmt = $pdo->prepare("
    SELECT * FROM sessions
    WHERE pool_id = ? AND status = 'active'
      AND (session_date > CURDATE() OR (session_date = CURDATE() AND end_time > CURTIME()))
    ORDER BY session_date ASC, start_time ASC
");

require_once __DIR__ . '/includes/header.php';
?>

<header class="hero" style="border-radius:28px; margin-top:-16px;">
  <div class="container hero-inner">
    <div>
      <div class="eyebrow hero-eyebrow">CAMPUS AQUATIC CENTER</div>
      <h1>Two pools.<br>One <em>effortless</em> booking.</h1>
      <p class="lede">Reserve your lane in the Olympic pool, or unwind in one of our two smaller pools — pick a time, grab your tickets, and you're confirmed in under a minute.</p>
      <div class="hero-ctas">
        <a href="#pools" class="btn btn-primary">Check availability</a>
        <?php if (!isMemberLoggedIn()): ?>
          <a href="<?php echo BASE_URL; ?>/member/register.php" class="btn btn-ghost">Create an account</a>
        <?php endif; ?>
      </div>
      <div class="hero-stats">
        <div class="hero-stat"><div class="num"><?php echo count($pools); ?></div><div class="label">Pools on campus</div></div>
        <div class="hero-stat"><div class="num"><?php echo array_sum(array_column($pools, 'capacity')); ?></div><div class="label">Total capacity</div></div>
        <div class="hero-stat"><div class="num">7am–7pm</div><div class="label">Daily hours</div></div>
      </div>
    </div>
  </div>
  <div class="wave-divider" aria-hidden="true">
    <svg viewBox="0 0 1440 90" preserveAspectRatio="none">
      <path class="wave-back wave-anim" d="M0,40 C 180,90 360,0 540,40 C 720,80 900,10 1080,40 C 1260,70 1350,30 1440,40 L1440,90 L0,90 Z" />
    </svg>
    <svg viewBox="0 0 1440 90" preserveAspectRatio="none" style="margin-top:-90px">
      <path class="wave-front" d="M0,55 C 200,10 400,90 640,55 C 880,20 1100,85 1440,50 L1440,90 L0,90 Z" />
    </svg>
  </div>
</header>

<section class="section section-tight" id="pools">
  <div class="section-head">
    <div class="eyebrow">OUR POOLS</div>
    <h2>Pick your water.</h2>
    <p>Browse today's and tomorrow's sessions below. You can look around freely — you'll only need to log in when you're ready to book.</p>
  </div>

  <div class="pools-grid">
    <?php foreach ($pools as $pool):
        $sessionStmt->execute([$pool['id']]);
        $sessions = $sessionStmt->fetchAll();
        $next = $sessions[0] ?? null;
        $occ = $next ? occupancy((int)$pool['capacity'], (int)$next['booked']) : ['pct' => 0, 'left' => (int)$pool['capacity']];
    ?>
    <article class="pool-card <?php echo $pool['size'] === 'big' ? 'big' : ''; ?> accent-<?php echo htmlspecialchars($pool['accent']); ?>">
      <div class="pool-media">
        <?php $photoSrc = IMAGE_BASE_URL !== '' ? IMAGE_BASE_URL . $pool['photo'] : BASE_URL . '/images/' . $pool['photo']; ?>
        <img class="pool-photo" src="<?php echo htmlspecialchars($photoSrc); ?>" alt="<?php echo htmlspecialchars($pool['name']); ?>" onerror="this.style.display='none'">
        <div class="pool-media-overlay"></div>
        <?php echo rippleSVG(); ?>
        <div class="pool-media-label">
          <span class="tag"><?php echo htmlspecialchars($pool['tagline']); ?></span>
          <h3><?php echo htmlspecialchars($pool['name']); ?></h3>
        </div>
      </div>
      <div class="pool-body">
        <p class="desc"><?php echo htmlspecialchars($pool['description']); ?></p>
        <div class="pool-meta-row">
          <div class="item"><span class="k">Depth</span><span class="v"><?php echo htmlspecialchars($pool['depth_label']); ?></span></div>
          <div class="item"><span class="k">Capacity</span><span class="v"><?php echo (int)$pool['capacity']; ?> people</span></div>
          <div class="item"><span class="k">Next session</span><span class="v"><?php echo $next ? formatDateLabel($next['session_date']) . ' ' . formatTimeLabel($next['start_time']) : '—'; ?></span></div>
        </div>
        <div class="pool-footer">
          <div class="gauge-wrap">
            <?php echo renderGauge($occ['pct']); ?>
            <div class="gauge-caption">
              <div class="k">Next session</div>
              <div class="v"><?php echo $occ['left']; ?> spot<?php echo $occ['left'] === 1 ? '' : 's'; ?> left</div>
            </div>
          </div>
          <div class="price-tag">Free</div>
        </div>

        <?php if (empty($sessions)): ?>
          <p class="form-hint">No upcoming sessions right now.</p>
        <?php else: ?>
          <div class="session-list">
            <?php foreach ($sessions as $s):
                $so = occupancy((int)$pool['capacity'], (int)$s['booked']);
                $isFull = $so['left'] <= 0;
            ?>
              <?php if ($isFull): ?>
                <div class="session-option" style="opacity:.45; cursor:not-allowed;">
                  <div class="time"><?php echo formatDateLabel($s['session_date']); ?> · <?php echo formatTimeLabel($s['start_time']); ?>–<?php echo formatTimeLabel($s['end_time']); ?></div>
                  <div class="avail low">Full</div>
                </div>
              <?php else: ?>
                <a class="session-option" href="<?php echo BASE_URL; ?>/book.php?session=<?php echo (int)$s['id']; ?>" style="text-decoration:none; color:inherit;">
                  <div class="time"><?php echo formatDateLabel($s['session_date']); ?> · <?php echo formatTimeLabel($s['start_time']); ?>–<?php echo formatTimeLabel($s['end_time']); ?></div>
                  <div class="avail <?php echo $so['left'] <= 5 ? 'low' : ''; ?>"><?php echo $so['left']; ?> left</div>
                </a>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="section" style="background:#fff; border-radius:28px;">
  <div class="section-head">
    <div class="eyebrow">HOW IT WORKS</div>
    <h2>Three steps, no queue.</h2>
  </div>
  <div class="steps">
    <div class="step-card">
      <div class="step-num">01</div>
      <h3>Choose pool &amp; time</h3>
      <p>Browse today's and tomorrow's sessions and see exactly how full each one is before you commit.</p>
    </div>
    <div class="step-card">
      <div class="step-num">02</div>
      <h3>Log in to confirm</h3>
      <p>Booking needs a free SplashPoint account so we can keep your reservation tied to you.</p>
    </div>
    <div class="step-card">
      <div class="step-num">03</div>
      <h3>Get confirmed instantly</h3>
      <p>Receive a booking reference immediately, and see it any time in your profile.</p>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>