<?php
require_once 'db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Fetch ALL active trips ──────────────────────────────
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$type_filter = isset($_GET['type']) ? trim($_GET['type']) : '';
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'newest';

$where = "t.status = 'active'";
$params = [];
$types_str = '';

// Search
if ($search !== '') {
    $where .= " AND (t.title LIKE ? OR t.destination LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $types_str .= 'ss';
}

// Type filter
$validTypes = ['Leisure', 'Adventure', 'Educational', 'Religious', 'Budget', 'Luxury'];
if ($type_filter !== '' && in_array($type_filter, $validTypes)) {
    $where .= " AND t.trip_type = ?";
    $params[] = $type_filter;
    $types_str .= 's';
}

// Sort
$orderBy = "t.created_at DESC";
if ($sort === 'price_low')
    $orderBy = "t.cost ASC";
if ($sort === 'price_high')
    $orderBy = "t.cost DESC";
if ($sort === 'popular')
    $orderBy = "enrolled_count DESC";
if ($sort === 'rating')
    $orderBy = "avg_rating DESC";

$sql = "SELECT t.*, u.name as organizer,
        (SELECT COUNT(*) FROM enrollments e WHERE e.trip_id = t.id AND e.status='approved') as enrolled_count,
        (SELECT IFNULL(AVG(r.rating),0) FROM reviews r WHERE r.trip_id = t.id) as avg_rating,
        (SELECT COUNT(*) FROM reviews r WHERE r.trip_id = t.id) as review_count
        FROM trips t
        JOIN users u ON t.created_by = u.id
        WHERE $where
        ORDER BY $orderBy";

$trips = [];
if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types_str, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
}
else {
    $result = $conn->query($sql);
}

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $trips[] = $row;
    }
}

// Stats for header
$total_trips = count($trips);
$total_destinations = count(array_unique(array_column($trips, 'destination')));
?>
<?php include 'header.php'; ?>

<!-- ━━━ Page Hero Banner ━━━ -->
<div class="pt-hero">
    <div class="pt-hero-bg"></div>
    <div class="container" style="position: relative; z-index: 2;">
        <div class="pt-hero-content">
            <span class="pt-hero-badge"><i class="ri-fire-fill"></i> Trending Destinations</span>
            <h1 class="pt-hero-title">Popular Trips</h1>
            <p class="pt-hero-sub">Discover curated experiences from our top TripMakers. Browse, explore, and reserve your spot!</p>
            <div class="pt-hero-stats">
                <div class="pt-stat">
                    <strong><?php echo $total_trips; ?></strong>
                    <span>Active Trips</span>
                </div>
                <div class="pt-stat-divider"></div>
                <div class="pt-stat">
                    <strong><?php echo $total_destinations; ?></strong>
                    <span>Destinations</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ━━━ Filters Bar ━━━ -->
<div class="container" style="margin-top: -35px; position: relative; z-index: 10;">
    <form method="GET" class="pt-filters-bar" id="filtersForm">
        <!-- Search -->
        <div class="pt-search-wrap">
            <i class="ri-search-line"></i>
            <input type="text" name="search" class="pt-search-input" placeholder="Search trips or destinations..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <!-- Type -->
        <select name="type" class="pt-filter-select" onchange="document.getElementById('filtersForm').submit();">
            <option value="">All Types</option>
            <?php foreach ($validTypes as $vt): ?>
                <option value="<?php echo $vt; ?>" <?php echo($type_filter === $vt) ? 'selected' : ''; ?>><?php echo $vt; ?></option>
            <?php
endforeach; ?>
        </select>
        <!-- Sort -->
        <select name="sort" class="pt-filter-select" onchange="document.getElementById('filtersForm').submit();">
            <option value="newest"     <?php echo($sort === 'newest') ? 'selected' : ''; ?>>Newest First</option>
            <option value="price_low"  <?php echo($sort === 'price_low') ? 'selected' : ''; ?>>Price: Low → High</option>
            <option value="price_high" <?php echo($sort === 'price_high') ? 'selected' : ''; ?>>Price: High → Low</option>
            <option value="popular"    <?php echo($sort === 'popular') ? 'selected' : ''; ?>>Most Popular</option>
            <option value="rating"     <?php echo($sort === 'rating') ? 'selected' : ''; ?>>Top Rated</option>
        </select>
        <button type="submit" class="pt-search-btn"><i class="ri-search-line"></i> Search</button>
    </form>
</div>

<!-- ━━━ Trips Grid ━━━ -->
<div class="container section" style="padding-top: 40px;">
    
    <?php if ($search !== '' || $type_filter !== ''): ?>
        <div style="margin-bottom: 24px; display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
            <span style="color: var(--text-light); font-size: 0.92rem;">
                Showing <strong style="color: var(--secondary-color);"><?php echo $total_trips; ?></strong> result(s)
                <?php if ($search !== ''): ?>
                    for "<strong style="color: var(--primary-color);"><?php echo htmlspecialchars($search); ?></strong>"
                <?php
    endif; ?>
                <?php if ($type_filter !== ''): ?>
                    in <span class="badge badge-purple"><?php echo htmlspecialchars($type_filter); ?></span>
                <?php
    endif; ?>
            </span>
            <a href="popular_trips.php" style="font-size: 0.85rem; color: var(--primary-color); font-weight: 600;">
                <i class="ri-close-circle-line"></i> Clear Filters
            </a>
        </div>
    <?php
endif; ?>

    <?php if (count($trips) > 0): ?>
    <div class="popular-trips-grid">
        <?php foreach ($trips as $t):
        $stars_display = str_repeat("★", intval($t['comfort_level'])) . str_repeat("☆", 5 - intval($t['comfort_level']));
        $d1 = new DateTime($t['start_date']);
        $d2 = new DateTime($t['end_date']);
        $duration = $d1->diff($d2)->days + 1;
        $avg = round(floatval($t['avg_rating']), 1);
        $days_until = ceil((strtotime($t['start_date']) - time()) / 86400);
?>
        <div class="trip-card pop-trip-card fade-in">
            <!-- Image -->
            <div class="trip-img-wrap pop-trip-img-wrap">
                <?php if (!empty($t['image_url'])): ?>
                    <img src="<?php echo htmlspecialchars($t['image_url']); ?>" alt="<?php echo htmlspecialchars($t['title']); ?>" class="trip-image" loading="lazy">
                <?php
        else: ?>
                    <div class="pop-trip-img-placeholder">
                        <i class="ri-landscape-line"></i>
                    </div>
                <?php
        endif; ?>
                <div class="trip-img-badges">
                    <span class="trip-type-tag"><?php echo htmlspecialchars($t['trip_type']); ?></span>
                    <?php if ($days_until > 0): ?>
                        <span class="trip-type-tag" style="background:rgba(72,187,120,0.3);border-color:rgba(72,187,120,0.4);">
                            <i class="ri-time-line" style="font-size:0.65rem;"></i> In <?php echo $days_until; ?> days
                        </span>
                    <?php
        endif; ?>
                </div>
                <div class="trip-price-badge">$<?php echo number_format($t['cost']); ?></div>
            </div>

            <!-- Content -->
            <div class="trip-content">
                <h3 class="trip-title"><?php echo htmlspecialchars($t['title']); ?></h3>
                
                <div class="trip-meta">
                    <span><i class="ri-map-pin-2-fill"></i> <?php echo htmlspecialchars($t['destination']); ?></span>
                    <span><i class="ri-calendar-event-line"></i> <?php echo $duration; ?> Days</span>
                </div>

                <!-- Date Range -->
                <div style="font-size: 0.8rem; color: var(--text-light); margin-bottom: 12px;">
                    <i class="ri-calendar-line" style="color: var(--primary-color); font-size:0.82rem;"></i>
                    <?php echo date('M d', strtotime($t['start_date'])); ?> – <?php echo date('M d, Y', strtotime($t['end_date'])); ?>
                </div>

                <!-- Info Row -->
                <div class="pop-trip-info-row">
                    <!-- Rating -->
                    <div class="pop-trip-rating">
                        <span class="pop-trip-stars"><?php echo $stars_display; ?></span>
                        <?php if ($t['review_count'] > 0): ?>
                            <span class="pop-trip-rating-num"><?php echo $avg; ?> (<?php echo $t['review_count']; ?>)</span>
                        <?php
        else: ?>
                            <span class="pop-trip-rating-num">New</span>
                        <?php
        endif; ?>
                    </div>
                    <!-- Enrolled -->
                    <div class="pop-trip-enrolled">
                        <i class="ri-group-line"></i> 
                        <?php echo $t['enrolled_count']; ?>/<?php echo $t['max_participants'] > 0 ? $t['max_participants'] : '∞'; ?>
                    </div>
                </div>

                <!-- Footer -->
                <div class="pop-trip-footer">
                    <div class="pop-trip-organizer">
                        <div class="pop-trip-avatar"><?php echo strtoupper(substr($t['organizer'], 0, 1)); ?></div>
                        <span><?php echo htmlspecialchars($t['organizer']); ?></span>
                    </div>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="trip.php?id=<?php echo $t['id']; ?>" class="trip-cta-btn" style="width:auto; padding:10px 20px;">
                            Book now <i class="ri-arrow-right-line"></i>
                        </a>
                    <?php
        else: ?>
                        <a href="login.php" class="trip-cta-btn" style="width:auto; padding:10px 20px;">
                            Book now <i class="ri-arrow-right-line"></i>
                        </a>
                    <?php
        endif; ?>
                </div>
            </div>
        </div>
        <?php
    endforeach; ?>
    </div>

    <?php
else: ?>
    <!-- Empty State -->
    <div class="pt-empty-state">
        <div class="pt-empty-icon">
            <i class="ri-road-map-line"></i>
        </div>
        <h3>No Trips Found</h3>
        <?php if ($search !== '' || $type_filter !== ''): ?>
            <p>Try adjusting your search or filters to find what you're looking for.</p>
            <a href="popular_trips.php" class="btn btn-primary" style="margin-top: 16px;">
                <i class="ri-restart-line"></i> Clear Filters
            </a>
        <?php
    else: ?>
            <p>Our TripMakers are crafting amazing experiences. Check back soon for exciting destinations!</p>
        <?php
    endif; ?>
    </div>
    <?php
endif; ?>
</div>

<!-- ━━━ Styles ━━━ -->
<style>
/* ── Hero Banner ── */
.pt-hero {
    position: relative;
    padding: 100px 0 80px;
    overflow: hidden;
    margin-top: -65px;
}
.pt-hero-bg {
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 40%, #0f3460 100%);
    z-index: 0;
}
.pt-hero-bg::before {
    content: '';
    position: absolute;
    inset: 0;
    background: 
        radial-gradient(circle at 20% 80%, rgba(108,99,255,0.25) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(245,0,87,0.15) 0%, transparent 50%);
}
.pt-hero-bg::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    right: 0;
    height: 80px;
    background: linear-gradient(to top, var(--bg-color), transparent);
}
.pt-hero-content {
    text-align: center;
    color: white;
    max-width: 640px;
    margin: 0 auto;
    padding-top: 40px;
}
.pt-hero-badge {
    display: inline-block;
    background: rgba(255,255,255,0.12);
    backdrop-filter: blur(10px);
    color: #F6E05E;
    font-size: 0.78rem;
    font-weight: 700;
    padding: 6px 18px;
    border-radius: 20px;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    margin-bottom: 20px;
    border: 1px solid rgba(255,255,255,0.1);
}
.pt-hero-title {
    font-size: 3.2rem;
    font-weight: 800;
    margin-bottom: 16px;
    line-height: 1.1;
    letter-spacing: -1px;
}
.pt-hero-sub {
    font-size: 1.05rem;
    opacity: 0.75;
    line-height: 1.6;
    margin-bottom: 30px;
}
.pt-hero-stats {
    display: inline-flex;
    align-items: center;
    gap: 28px;
    background: rgba(255,255,255,0.08);
    backdrop-filter: blur(10px);
    padding: 16px 32px;
    border-radius: 16px;
    border: 1px solid rgba(255,255,255,0.1);
}
.pt-stat {
    text-align: center;
}
.pt-stat strong {
    display: block;
    font-size: 1.6rem;
    font-weight: 800;
    color: white;
}
.pt-stat span {
    font-size: 0.78rem;
    color: rgba(255,255,255,0.6);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.pt-stat-divider {
    width: 1px;
    height: 36px;
    background: rgba(255,255,255,0.15);
}

/* ── Filters Bar ── */
.pt-filters-bar {
    display: flex;
    gap: 12px;
    align-items: center;
    background: var(--white);
    padding: 14px 20px;
    border-radius: 18px;
    box-shadow: 0 8px 30px rgba(0,0,0,0.1);
    border: 1px solid #edf2f7;
    flex-wrap: wrap;
}
.pt-search-wrap {
    flex: 1;
    min-width: 200px;
    position: relative;
    display: flex;
    align-items: center;
}
.pt-search-wrap i {
    position: absolute;
    left: 14px;
    color: #a0aec0;
    font-size: 1.1rem;
}
.pt-search-input {
    width: 100%;
    padding: 12px 14px 12px 42px;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    font-family: inherit;
    font-size: 0.92rem;
    background: #f8fafc;
    transition: all 0.3s ease;
}
.pt-search-input:focus {
    outline: none;
    border-color: var(--primary-color);
    background: white;
    box-shadow: 0 0 0 3px rgba(108,99,255,0.1);
}
.pt-filter-select {
    padding: 12px 16px;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    font-family: inherit;
    font-size: 0.88rem;
    background: #f8fafc;
    color: var(--text-color);
    cursor: pointer;
    min-width: 150px;
    transition: all 0.3s ease;
}
.pt-filter-select:focus {
    outline: none;
    border-color: var(--primary-color);
}
.pt-search-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 12px 24px;
    background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
    color: white;
    border: none;
    border-radius: 12px;
    font-family: inherit;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 14px rgba(108,99,255,0.3);
}
.pt-search-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(108,99,255,0.5);
}

/* ── Grid ── */
.popular-trips-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 30px;
}

/* ── Card overrides for popular trips ── */
.pop-trip-card {
    box-shadow: 0 4px 24px rgba(0,0,0,0.06);
    border: 1px solid #edf2f7;
}
.pop-trip-card:hover {
    box-shadow: 0 20px 50px rgba(108,99,255,0.12);
    border-color: rgba(108,99,255,0.2);
}

/* ── Image ── */
.pop-trip-img-wrap {
    height: 230px;
}
.pop-trip-img-placeholder {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #e0e7ff, #f0f0ff);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 4rem;
    color: #a0aec0;
}

/* ── Info Row ── */
.pop-trip-info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-top: 1px solid #f0f0f0;
    border-bottom: 1px solid #f0f0f0;
    margin-bottom: 16px;
}
.pop-trip-rating {
    display: flex;
    align-items: center;
    gap: 8px;
}
.pop-trip-stars {
    color: #F6E05E;
    font-size: 0.9rem;
    letter-spacing: 1px;
}
.pop-trip-rating-num {
    font-size: 0.8rem;
    color: var(--text-light);
    font-weight: 500;
}
.pop-trip-enrolled {
    font-size: 0.82rem;
    color: var(--text-light);
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 5px;
}
.pop-trip-enrolled i {
    color: var(--primary-color);
    font-size: 1rem;
}

/* ── Footer ── */
.pop-trip-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: auto;
}
.pop-trip-organizer {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.85rem;
    color: var(--text-light);
    font-weight: 500;
}
.pop-trip-avatar {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    font-weight: 700;
    flex-shrink: 0;
}

/* ── Empty State ── */
.pt-empty-state {
    text-align: center;
    padding: 80px 20px;
    background: white;
    border-radius: 20px;
    box-shadow: var(--shadow-sm);
    border: 1px solid #edf2f7;
}
.pt-empty-icon {
    font-size: 4rem;
    color: #e2e8f0;
    margin-bottom: 20px;
}
.pt-empty-state h3 {
    color: var(--secondary-color);
    margin-bottom: 10px;
    font-size: 1.5rem;
}
.pt-empty-state p {
    color: var(--text-light);
    max-width: 400px;
    margin: 0 auto;
}

/* ── Responsive ── */
@media (max-width: 768px) {
    .pt-hero-title { font-size: 2.2rem; }
    .pt-filters-bar { flex-direction: column; }
    .pt-search-wrap { width: 100%; }
    .pt-filter-select { width: 100%; }
    .popular-trips-grid { grid-template-columns: 1fr; }
}
@media (max-width: 480px) {
    .pop-trip-img-wrap { height: 180px; }
    .pop-trip-body { padding: 18px 16px 16px; }
    .pt-hero { padding: 80px 0 60px; }
    .pt-hero-stats { padding: 12px 20px; gap: 18px; }
}
</style>

<?php include 'footer.php'; ?>
