<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

$languageCode = in_array($_SESSION['lang'] ?? 'vi', ['vi', 'en'], true) ? $_SESSION['lang'] : 'vi';
include __DIR__ . '/lang/' . $languageCode . '.php';
$featuredCompanies = getProminentCompanies($conn);
?>
<!doctype html>
<html lang="<?= htmlspecialchars($languageCode, ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f3f6f4">
    <title><?= htmlspecialchars($lang['featured_companies'], ENT_QUOTES, 'UTF-8'); ?> | ViecNhe</title>
    <style>
        :root {
            --directory-ink: #18332f;
            --directory-muted: #687a75;
            --directory-line: #dce6e2;
            --directory-surface: #fff;
            --directory-canvas: #f3f6f4;
            --directory-accent: #bb3337;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            color: var(--directory-ink);
            background: var(--directory-canvas);
            font-family: "Segoe UI", Arial, sans-serif;
        }

        .featured-directory {
            width: min(1180px, 100%);
            margin: 0 auto;
            padding: 40px 28px 72px;
        }

        .directory-heading {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 24px;
            margin-bottom: 28px;
            padding-bottom: 24px;
            border-bottom: 1px solid var(--directory-line);
        }

        .directory-heading__copy { max-width: 700px; }

        .directory-kicker {
            margin: 0 0 8px;
            color: var(--directory-accent);
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .directory-heading h1 {
            margin: 0;
            font-size: 32px;
            line-height: 1.2;
        }

        .directory-description {
            margin: 10px 0 0;
            color: var(--directory-muted);
            font-size: 15px;
            line-height: 1.6;
        }

        .directory-count {
            flex: 0 0 auto;
            padding: 9px 12px;
            border: 1px solid var(--directory-line);
            color: var(--directory-muted);
            background: var(--directory-surface);
            font-size: 13px;
            font-weight: 600;
        }

        .company-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
        }

        .company-card {
            min-width: 0;
            overflow: hidden;
            color: inherit;
            background: var(--directory-surface);
            border: 1px solid var(--directory-line);
            border-radius: 6px;
            text-decoration: none;
            transition: border-color .18s ease, transform .18s ease, box-shadow .18s ease;
        }

        .company-card:hover,
        .company-card:focus-visible {
            border-color: #b9cbc4;
            box-shadow: 0 12px 28px rgba(24, 51, 47, .09);
            transform: translateY(-2px);
        }

        .company-card__logo {
            display: grid;
            min-height: 174px;
            padding: 24px;
            place-items: center;
            background: #f8faf9;
            border-bottom: 1px solid #edf1ef;
        }

        .company-card__logo img {
            display: block;
            width: 100%;
            height: 126px;
            object-fit: contain;
        }

        .company-card__details {
            display: flex;
            align-items: start;
            gap: 10px;
            min-height: 76px;
            padding: 16px;
        }

        .company-card__rank {
            flex: 0 0 auto;
            color: var(--directory-accent);
            font-size: 12px;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
        }

        .company-card__name {
            margin: 0;
            overflow-wrap: anywhere;
            font-size: 15px;
            font-weight: 650;
            line-height: 1.45;
        }

        .directory-empty {
            padding: 48px 20px;
            color: var(--directory-muted);
            background: var(--directory-surface);
            border: 1px solid var(--directory-line);
            text-align: center;
        }

        @media (max-width: 900px) {
            .company-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }

        @media (max-width: 640px) {
            .featured-directory { padding: 28px 16px 48px; }
            .directory-heading { align-items: start; flex-direction: column; gap: 14px; }
            .directory-heading h1 { font-size: 26px; }
            .company-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
            .company-card__logo { min-height: 132px; padding: 16px; }
            .company-card__logo img { height: 96px; }
            .company-card__details { min-height: 72px; gap: 7px; padding: 12px; }
            .company-card__name { font-size: 13px; }
        }

        @media (max-width: 360px) {
            .company-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/homepage/header.php'; ?>
    <main class="featured-directory">
        <header class="directory-heading">
            <div class="directory-heading__copy">
                <p class="directory-kicker">ViecNhe</p>
                <h1><?= htmlspecialchars($lang['featured_companies'], ENT_QUOTES, 'UTF-8'); ?></h1>
                <p class="directory-description"><?= htmlspecialchars($lang['featured_companies_description'], ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <div class="directory-count"><?= count($featuredCompanies); ?> <?= htmlspecialchars($lang['featured_companies_count'], ENT_QUOTES, 'UTF-8'); ?></div>
        </header>

        <?php if ($featuredCompanies): ?>
            <div class="company-grid">
                <?php foreach ($featuredCompanies as $index => $company): ?>
                    <?php
                    $companyName = (string) $company['company_name'];
                    $companyLogo = normalizeCompanyLogoPath($company['company_logo'] ?? '');
                    ?>
                    <a class="company-card" href="/companyprofile.php?company_id=<?= (int) $company['id']; ?>" title="<?= htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="company-card__logo">
                            <img src="<?= htmlspecialchars($companyLogo, ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8'); ?>" loading="lazy">
                        </div>
                        <div class="company-card__details">
                            <span class="company-card__rank"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT); ?></span>
                            <h2 class="company-card__name"><?= htmlspecialchars(truncateCompanyName($companyName), ENT_QUOTES, 'UTF-8'); ?></h2>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="directory-empty"><?= htmlspecialchars($lang['featured_companies_empty'], ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
    </main>
</body>
<footer>
    <?php include __DIR__ . '/homepage/footer.php'; ?>
</footer>
</html>