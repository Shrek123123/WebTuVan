<?php
require_once __DIR__ . '/location-data.php';

if (!function_exists('mediator_escape')) {
    function mediator_escape($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    function mediator_decode_specialties($value)
    {
        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true, 32);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return [];
        }

        $specialties = [];
        foreach ($decoded as $item) {
            if (!is_string($item) && !is_int($item) && !is_float($item)) {
                continue;
            }
            $item = trim((string) $item);
            if ($item !== '' && !in_array($item, $specialties, true)) {
                $specialties[] = $item;
            }
        }
        return $specialties;
    }

    function mediator_lower($value)
    {
        $value = (string) $value;
        return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
    }

    function mediator_bind_values($stmt, $types, &$values)
    {
        $arguments = [$types];
        foreach ($values as &$value) {
            $arguments[] = &$value;
        }
        call_user_func_array([$stmt, 'bind_param'], $arguments);
    }

    function mediator_like_pattern($value)
    {
        return '%' . strtr((string) $value, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
    }

    function mediator_ensure_connection($conn)
    {
        try {
            if ($conn instanceof mysqli && $conn->ping()) {
                return $conn;
            }
        } catch (Throwable $exception) {
        }
        return new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    }

    function mediator_fetch_profiles($conn, $filters = [])
    {
        $query = trim((string) ($filters['q'] ?? ''));
        $specialty = trim((string) ($filters['specialty'] ?? ''));
        $availability = (string) ($filters['availability'] ?? '');
        $locationPairValid = mediator_location_pair_is_valid(
            $filters['province_location'] ?? '',
            $filters['district_location'] ?? ''
        );
        [$provinceLocation, $districtLocation] = mediator_normalize_location_pair(
            $filters['province_location'] ?? '',
            $filters['district_location'] ?? ''
        );
        if (!in_array($availability, ['online', 'busy', 'offline'], true)) {
            $availability = '';
        }

        $sort = (string) ($filters['sort'] ?? 'rating');
        $orderBy = [
            'rating' => 'rating IS NULL ASC, rating DESC, total_cases DESC, full_name ASC',
            'rate_low' => 'hourly_rate IS NULL ASC, hourly_rate ASC, rating DESC, full_name ASC',
            'rate_high' => 'hourly_rate IS NULL ASC, hourly_rate DESC, rating DESC, full_name ASC',
        ][$sort] ?? 'rating IS NULL ASC, rating DESC, total_cases DESC, full_name ASC';

        $conditions = [];
        $params = [];
        if (!$locationPairValid) {
            $conditions[] = '1 = 0';
        }
        if ($query !== '') {
            $term = mediator_like_pattern($query);
            $conditions[] = "(full_name LIKE ? ESCAPE '!' OR university LIKE ? ESCAPE '!' OR major LIKE ? ESCAPE '!' OR bio LIKE ? ESCAPE '!' OR CAST(specialties AS CHAR) LIKE ? ESCAPE '!')";
            array_push($params, $term, $term, $term, $term, $term);
        }
        if ($specialty !== '') {
            $conditions[] = "CAST(specialties AS CHAR) LIKE ? ESCAPE '!'";
            $params[] = mediator_like_pattern($specialty);
        }
        if ($availability !== '') {
            $conditions[] = 'status = ?';
            $params[] = $availability;
        }
        if ($provinceLocation !== '') {
            $conditions[] = 'province_location = ?';
            $params[] = $provinceLocation;
        }
        if ($districtLocation !== '') {
            $conditions[] = 'district_location = ?';
            $params[] = $districtLocation;
        }

        $sql = 'SELECT id, full_name, avatar_url, university, major, bio, specialties, hourly_rate, rating, total_cases, status, province_location, district_location
                FROM mediators';
        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        $sql .= ' ORDER BY ' . $orderBy;

        try {
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                error_log('Unable to prepare mediator directory query: ' . $conn->error);
                return ['profiles' => [], 'total' => 0, 'error' => true];
            }
            if ($params) {
                $types = str_repeat('s', count($params));
                mediator_bind_values($stmt, $types, $params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $profiles = [];
            while ($row = $result->fetch_assoc()) {
                $row['decoded_specialties'] = mediator_decode_specialties($row['specialties'] ?? null);

                if ($query !== '') {
                    $searchable = [
                        $row['full_name'] ?? '',
                        $row['university'] ?? '',
                        $row['major'] ?? '',
                        $row['bio'] ?? '',
                        implode(' ', $row['decoded_specialties']),
                    ];
                    $matched = false;
                    foreach ($searchable as $field) {
                        if (strpos(mediator_lower($field), mediator_lower($query)) !== false) {
                            $matched = true;
                            break;
                        }
                    }
                    if (!$matched) {
                        continue;
                    }
                }

                if ($specialty !== '') {
                    $hasSpecialty = false;
                    foreach ($row['decoded_specialties'] as $item) {
                        if (mediator_lower($item) === mediator_lower($specialty)) {
                            $hasSpecialty = true;
                            break;
                        }
                    }
                    if (!$hasSpecialty) {
                        continue;
                    }
                }
                $profiles[] = $row;
            }
            $stmt->close();
            return ['profiles' => $profiles, 'total' => count($profiles), 'error' => false];
        } catch (Throwable $exception) {
            error_log('Unable to fetch mediator directory: ' . $exception->getMessage());
            return ['profiles' => [], 'total' => 0, 'error' => true];
        }
    }

    function mediator_specialty_options($conn)
    {
        $options = [];
        try {
            $result = $conn->query('SELECT specialties FROM mediators');
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    foreach (mediator_decode_specialties($row['specialties'] ?? null) as $specialty) {
                        $options[mediator_lower($specialty)] = $specialty;
                    }
                }
            }
        } catch (Throwable $exception) {
            error_log('Unable to load mediator specialty filters: ' . $exception->getMessage());
        }
        $options = array_values($options);
        usort($options, 'strcasecmp');
        return $options;
    }

    function mediator_status_label($status)
    {
        return [
            'online' => 'Đang sẵn sàng',
            'busy' => 'Đang bận',
            'offline' => 'Ngoại tuyến',
        ][(string) $status] ?? 'Chưa cập nhật';
    }

    function mediator_status_class($status)
    {
        return [
            'online' => 'is-online',
            'busy' => 'is-busy',
            'offline' => 'is-offline',
        ][(string) $status] ?? 'is-unknown';
    }

    function mediator_hourly_rate($rate)
    {
        if ($rate === null || $rate === '' || !is_numeric($rate)) {
            return 'Chưa cập nhật';
        }
        return number_format((float) $rate, 0, ',', '.') . ' đ/giờ';
    }

    function mediator_rating($rating)
    {
        if ($rating === null || $rating === '' || !is_numeric($rating)) {
            return 'Chưa có đánh giá';
        }
        return number_format((float) $rating, 1, ',', '.') . ' / 5';
    }

    function mediator_initials($name)
    {
        $parts = preg_split('/\s+/u', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY);
        if (!$parts) {
            return 'HV';
        }
        $initials = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= function_exists('mb_substr') ? mb_substr($part, 0, 1, 'UTF-8') : substr($part, 0, 1);
        }
        return mediator_escape($initials !== '' ? $initials : 'HV');
    }

    function mediator_avatar_url($value)
    {
        $url = trim((string) $value);
        if ($url === '' || preg_match('/[\x00-\x1F\x7F]/', $url) || strpos($url, '//') === 0 || strpos($url, '\\') !== false) {
            return '';
        }
        $parts = parse_url($url);
        if ($parts === false) {
            return '';
        }
        if (isset($parts['scheme'])) {
            if (!in_array(strtolower($parts['scheme']), ['http', 'https'], true) || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
                return '';
            }
        } elseif (isset($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
            return '';
        }
        return $url;
    }

    function mediator_render_avatar($profile, $class = 'mediator-avatar')
    {
        $name = (string) ($profile['full_name'] ?? 'Hòa giải viên');
        $url = mediator_avatar_url($profile['avatar_url'] ?? '');
        if ($url === '') {
            return '<span class="' . mediator_escape($class) . ' mediator-avatar-fallback" aria-hidden="true">' . mediator_initials($name) . '</span>';
        }
        return '<img class="' . mediator_escape($class) . '" src="' . mediator_escape($url) . '" alt="Ảnh đại diện của ' . mediator_escape($name) . '" data-initials="' . mediator_initials($name) . '" loading="lazy" onerror="this.replaceWith(Object.assign(document.createElement(\'span\'),{className:this.className+\' mediator-avatar-fallback\',textContent:this.dataset.initials,ariaHidden:\'true\'}))">';
    }

    function mediator_render_card($profile)
    {
        $id = (int) ($profile['id'] ?? 0);
        $name = trim((string) ($profile['full_name'] ?? '')) ?: 'Hòa giải viên';
        $school = trim((string) ($profile['university'] ?? ''));
        $major = trim((string) ($profile['major'] ?? ''));
        $bio = trim((string) ($profile['bio'] ?? ''));
        $specialties = $profile['decoded_specialties'] ?? mediator_decode_specialties($profile['specialties'] ?? null);
        $bioExcerpt = function_exists('mb_substr') ? mb_substr($bio, 0, 150, 'UTF-8') : substr($bio, 0, 150);
        if (function_exists('mb_strlen') ? mb_strlen($bio, 'UTF-8') > 150 : strlen($bio) > 150) {
            $bioExcerpt .= '…';
        }
        ?>
        <article class="mediator-card">
            <div class="mediator-card-top">
                <?= mediator_render_avatar($profile) ?>
                <span class="mediator-status <?= mediator_status_class($profile['status'] ?? '') ?>">
                    <span class="mediator-status-dot" aria-hidden="true"></span><?= mediator_escape(mediator_status_label($profile['status'] ?? '')) ?>
                </span>
            </div>
            <h3><?= mediator_escape($name) ?></h3>
            <?php if ($school !== '' || $major !== ''): ?>
                <p class="mediator-card-education">
                    <?= mediator_escape($school) ?><?= $school !== '' && $major !== '' ? ' · ' : '' ?><?= mediator_escape($major) ?>
                </p>
            <?php endif; ?>
            <p class="mediator-card-bio"><?= $bioExcerpt !== '' ? mediator_escape($bioExcerpt) : 'Hồ sơ chưa có phần giới thiệu.' ?></p>
            <?php if ($specialties): ?>
                <div class="mediator-card-specialties" aria-label="Lĩnh vực chuyên môn">
                    <?php foreach (array_slice($specialties, 0, 3) as $item): ?><span class="mediator-specialty"><?= mediator_escape($item) ?></span><?php endforeach; ?>
                    <?php if (count($specialties) > 3): ?><span class="mediator-more-specialties">+<?= count($specialties) - 3 ?></span><?php endif; ?>
                </div>
            <?php endif; ?>
            <div class="mediator-card-facts">
                <div><span>Phí theo giờ</span><strong><?= mediator_escape(mediator_hourly_rate($profile['hourly_rate'] ?? null)) ?></strong></div>
                <div><span>Đánh giá</span><strong><?= mediator_escape(mediator_rating($profile['rating'] ?? null)) ?></strong></div>
                <div><span>Số vụ việc</span><strong><?= number_format(max(0, (int) ($profile['total_cases'] ?? 0)), 0, ',', '.') ?></strong></div>
            </div>
            <a class="mediator-card-link" href="jobdetail.php?id=<?= $id ?>">Xem hồ sơ <span aria-hidden="true">→</span></a>
        </article>
        <?php
    }

    function mediator_availability_select($id, $selected)
    {
        $selected = in_array($selected, ['online', 'busy', 'offline'], true) ? $selected : '';
        $options = ['' => 'Mọi tình trạng', 'online' => 'Đang sẵn sàng', 'busy' => 'Đang bận', 'offline' => 'Ngoại tuyến'];
        $html = '<select id="' . mediator_escape($id) . '" name="availability">';
        foreach ($options as $value => $label) {
            $html .= '<option value="' . mediator_escape($value) . '"' . ($selected === $value ? ' selected' : '') . '>' . mediator_escape($label) . '</option>';
        }
        return $html . '</select>';
    }

    function mediator_sort_select($id, $selected)
    {
        $options = ['rating' => 'Đánh giá cao', 'rate_low' => 'Phí thấp trước', 'rate_high' => 'Phí cao trước'];
        if (!isset($options[$selected])) {
            $selected = 'rating';
        }
        $html = '<select id="' . mediator_escape($id) . '" name="sort">';
        foreach ($options as $value => $label) {
            $html .= '<option value="' . mediator_escape($value) . '"' . ($selected === $value ? ' selected' : '') . '>' . mediator_escape($label) . '</option>';
        }
        return $html . '</select>';
    }
}
?>
<style>
    :root { --mediator-paper: #f5f3ed; --mediator-ink: #1d302f; --mediator-teal: #2f7068; --mediator-terracotta: #a75e49; --mediator-muted: #5e6d69; --mediator-line: #dce1da; }
    .mediator-card { display: flex; min-width: 0; flex-direction: column; padding: 21px; border: 1px solid var(--mediator-line); background: #fffefa; box-shadow: 0 5px 18px rgba(34, 46, 45, .035); transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease; }
    .mediator-card:hover { transform: translateY(-3px); border-color: #b4cbc3; box-shadow: 0 12px 28px rgba(34, 46, 45, .09); }
    .mediator-card-top { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
    .mediator-avatar { width: 66px; height: 66px; flex: 0 0 66px; border: 1px solid var(--mediator-line); object-fit: cover; background: #e8efeb; }
    .mediator-avatar-fallback { display: inline-grid; place-items: center; color: var(--mediator-teal); font: 500 1.35rem Georgia, 'Times New Roman', serif; }
    .mediator-status { display: inline-flex; align-items: center; gap: 7px; width: fit-content; color: var(--mediator-muted); font-size: .78rem; font-weight: 700; }
    .mediator-status-dot { width: 8px; height: 8px; flex: 0 0 8px; border-radius: 50%; background: #89928e; }
    .mediator-status.is-online { color: #25695d; } .mediator-status.is-online .mediator-status-dot { background: #318c72; }
    .mediator-status.is-busy { color: #8b5a34; } .mediator-status.is-busy .mediator-status-dot { background: #bd8853; }
    .mediator-status.is-offline { color: #69736f; } .mediator-status.is-offline .mediator-status-dot { background: #929b97; }
    .mediator-card h3 { margin: 18px 0 6px; color: var(--mediator-ink); font: 500 1.45rem/1.2 Georgia, 'Times New Roman', serif; }
    .mediator-card-education { min-height: 22px; margin: 0; color: var(--mediator-muted); font-size: .88rem; line-height: 1.5; }
    .mediator-card-bio { min-height: 66px; margin: 14px 0 15px; color: #43514e; font-size: .91rem; line-height: 1.6; }
    .mediator-card-specialties, .mediator-detail-specialties { display: flex; flex-wrap: wrap; gap: 7px; }
    .mediator-specialty { display: inline-block; padding: 5px 9px; border: 1px solid #d9e5df; background: #eff4f0; color: #345d56; font-size: .76rem; line-height: 1.25; }
    .mediator-more-specialties { padding: 5px 3px; color: var(--mediator-muted); font-size: .78rem; }
    .mediator-card-facts { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 18px; padding-top: 15px; border-top: 1px solid var(--mediator-line); }
    .mediator-card-facts div { min-width: 0; }
    .mediator-card-facts div:first-child { grid-column: 1 / -1; }
    .mediator-card-facts span { display: block; margin-bottom: 4px; color: var(--mediator-muted); font-size: .73rem; }
    .mediator-card-facts strong { display: block; overflow-wrap: anywhere; color: var(--mediator-ink); font-size: .9rem; }
    .mediator-card-facts div:first-child strong { color: var(--mediator-terracotta); }
    .mediator-card-link { display: flex; justify-content: space-between; align-items: center; margin-top: 16px; padding-top: 13px; border-top: 1px solid var(--mediator-line); color: var(--mediator-teal); font-size: .9rem; font-weight: 750; text-decoration: none; }
    .mediator-card-link:hover { color: var(--mediator-terracotta); }
    .mediator-card-link:focus-visible, .mediator-all-link:focus-visible, .mediator-back-link:focus-visible, .mediator-pagination a:focus-visible { outline: 3px solid rgba(47, 112, 104, .35); outline-offset: 3px; }
    .mediator-state { padding: 30px; border: 1px solid var(--mediator-line); background: #fffefa; color: var(--mediator-ink); }
    .mediator-state strong { font: 500 1.4rem Georgia, 'Times New Roman', serif; }
    .mediator-state p, .mediator-empty-copy { margin: 9px 0 0; color: var(--mediator-muted); line-height: 1.6; }
    .mediator-state a { display: inline-block; margin-top: 10px; color: var(--mediator-teal); font-weight: 700; }
    @media (prefers-reduced-motion: reduce) { .mediator-card { transition: none; } .mediator-card:hover { transform: none; } }
</style>
