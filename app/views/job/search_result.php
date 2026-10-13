<h2>Search Results</h2>

<!-- Hiển thị số lượng và bộ lọc hiện tại -->
<p>
    Found <?= count($jobs) ?> job(s)
    <?php if (!empty($filter_description)) : ?>
        matching: <strong><?= htmlspecialchars($filter_description) ?></strong>
    <?php endif; ?>
</p>

<!-- Nếu có job thì hiển thị danh sách -->
<?php if (count($jobs) > 0): ?>
    <?php foreach ($jobs as $job): ?>
        <div style="border: 1px solid #ccc; padding: 10px; margin: 10px 0;">
            <h3><?= htmlspecialchars($job['title']) ?> at <?= htmlspecialchars($job['company_name']) ?></h3>
            <p><strong>Location:</strong> <?= htmlspecialchars($job['location']) ?></p>
            <p><strong>Salary:</strong> $<?= number_format($job['salary'], 0) ?></p>
            <p><strong>Type:</strong> <?= htmlspecialchars($job['job_type']) ?> | 
               <strong>Experience:</strong> <?= htmlspecialchars($job['experience_level']) ?></p>
            <p><strong>Category:</strong> <?= htmlspecialchars($job['category']) ?> |
               <strong>Posted on:</strong> <?= htmlspecialchars($job['posted_date']) ?></p>
            <p><?= nl2br(htmlspecialchars($job['description'])) ?></p>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p>No jobs found matching your criteria.</p>
<?php endif; ?>

