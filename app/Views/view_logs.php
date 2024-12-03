<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs</title>
    <link rel="stylesheet" href="/styles.css">
</head>

<body>
    <div class="sidebar">
        <h2>LBBL</h2>
        <a href="<?= base_url('/server-backup') ?>">Home</a>
        <a href="<?= base_url('/server-backup/viewLogs') ?>">View Logs</a>
    </div>

    <div class="content">
        <h2>View Logs</h2>
        <form action="<?= base_url('/view-logs') ?>" method="GET">
            <label for="date">Select Date:</label>
            <input type="date" name="log_date" required>
            <button type="submit">View</button>
            <button type="submit" formaction="<?= base_url('/download-pdf') ?>" class="btn-pdf">PDF</button>
        </form>
        <table>
            <caption>Backup/Replication Logs</caption>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Log Time</th>
                    <th>Last Successful Time</th>
                    <th>Log Type</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $combinedLogs = array_merge($logs['backupLogs'], $logs['replicationLogs']);
                foreach ($combinedLogs as $log): ?>
                    <tr>
                        <td><?= $log['name'] ?></td>
                        <td>
                            <?php if (isset($log['is_delayed']) && $log['is_delayed']): ?>
                                <span style="color: red;">&#x25CF; Delayed</span> <!-- Red indicator -->
                            <?php else: ?>
                                <span style="color: green;">&#x25CF; On Time</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $log['log_time'] ?></td>
                        <td><?= $log['last_successful_time'] ?></td>
                        <td><?= $log['log_type'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <table>
            <caption>DB Backup Logs</caption>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Log Time (DC)</th>
                    <th>Backup Time (DC)</th>
                    <th>Log Time (DR)</th>
                    <th>Backup Time (DR)</th>
                    <th>Log Time (NDC)</th>
                    <th>Backup Time (NDC)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs['dbBackupLogs'] as $log): ?>
                    <tr>
                        <td><?= $log['name'] ?></td>
                        <td><?= $log['log_time_dc'] ?? 'N/A' ?></td>
                        <td><?= $log['backup_time_dc'] ?? 'N/A' ?></td>
                        <td><?= $log['log_time_dr'] ?? 'N/A' ?></td>
                        <td><?= $log['backup_time_dr'] ?? 'N/A' ?></td>
                        <td><?= $log['log_time_ndc'] ?? 'N/A' ?></td>
                        <td><?= $log['backup_time_ndc'] ?? 'N/A' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <table>
            <caption>Storage Space Logs</caption>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Free Space</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs['storageSpaceLogs'] as $log): ?>
                    <tr>
                        <td><?= $log['name'] ?></td>
                        <td><?= $log['free_space'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div>
            <h3>Additional Information</h3>
            <?php foreach ($logs['additionalInfoLogs'] as $log): ?>
                <p>Submitted by: <?= $log['submitted_by'] ?></p>
                <p>Remarks: <?= $log['remarks'] ?></p>
            <?php endforeach; ?>
        </div>
    </div>
</body>

</html>