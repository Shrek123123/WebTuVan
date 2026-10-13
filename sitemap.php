<?php

require_once 'config.php';

header('Content-Type: application/xml; charset=UTF-8');

// Hàm escape dữ liệu XML
function xmlEscape($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_XML1 | ENT_QUOTES,
        'UTF-8'
    );
}

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

    <!-- Homepage -->
    <url>
        <loc>https://viecnhe.io.vn/</loc>
    </url>

<?php

// Lấy các tin tuyển dụng còn hợp lệ
$sql = "
    SELECT
        j.id,
        j.created_at,
        j.post_duration
    FROM hoa_giai_vien j
    LEFT JOIN company c ON c.id = j.company_id
    WHERE
        (c.status IS NULL OR c.status NOT IN ('pending_review', 'rejected'))
";

$result = $conn->query($sql);

if ($result) {

    while ($job = $result->fetch_assoc()) {

        $jobId = (int) $job['id'];

        // URL hiện tại của trang chi tiết việc làm
        $url = 'https://viecnhe.io.vn/jobdetail.php?id=' . $jobId;

        // created_at dùng làm lastmod
        $lastmod = '';

        if (!empty($job['created_at'])) {
            try {
                $date = new DateTime($job['created_at']);
                $lastmod = $date->format('Y-m-d');
            } catch (Exception $e) {
                $lastmod = '';
            }
        }

        echo "    <url>\n";
        echo "        <loc>" . xmlEscape($url) . "</loc>\n";

        if ($lastmod !== '') {
            echo "        <lastmod>" . $lastmod . "</lastmod>\n";
        }

        echo "    </url>\n";
    }

    $result->free();
}

?>

</urlset>