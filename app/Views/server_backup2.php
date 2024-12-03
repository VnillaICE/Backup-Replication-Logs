<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Server Backup/Replication Log</title>
    <style>
        /* Basic reset for consistent styling */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Body styling */
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f5f7fa;
            color: #333;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        /* Container styling */
        .container {
            width: 90%;
            max-width: 800px;
            background-color: #ffffff;
            padding: 20px 30px;
            border-radius: 8px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
        }

        /* Header styling */
        h2 {
            text-align: center;
            color: #1a73e8;
            font-size: 1.8em;
            margin-bottom: 25px;
        }

        /* Top controls for changing log type */
        .top-controls {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
            gap: 10px;
        }

        /* Button styling */
        .top-controls .btn {
            padding: 10px 20px;
            font-size: 14px;
            font-weight: bold;
            color: #ffffff;
            background-color: #1a73e8;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .top-controls .btn:hover {
            background-color: #155bb5;
        }

        /* Display of selected log type */
        .log-type-display {
            font-size: 1em;
            color: #333;
            font-weight: 600;
        }

        /* Table styling */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table th,
        table td {
            padding: 15px 12px;
            text-align: left;
            font-size: 0.9em;
        }

        table th {
            background-color: #f3f4f6;
            color: #333;
            font-weight: 600;
        }

        table tr:nth-child(even) {
            background-color: #fafafa;
        }

        table tr:hover {
            background-color: #eaf1fb;
        }

        /* Status indicator */
        .status-logged {
            color: #34a853;
            font-weight: bold;
        }

        .status-pending {
            color: #fbbc05;
            font-weight: bold;
        }

        /* Time input styling */
        input[type="time"] {
            padding: 8px;
            font-size: 1em;
            width: 100%;
            max-width: 150px;
            border: 1px solid #ccc;
            border-radius: 4px;
            color: #333;
        }

        /* Action button in each row */
        .action-btn {
            background-color: #1a73e8;
            color: white;
            padding: 10px 20px;
            font-size: 0.9em;
            font-weight: bold;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .action-btn:hover {
            background-color: #155bb5;
        }

        .alert {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-size: 16px;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
    </style>

    <script>
        // Get initial log type from PHP
        let currentLogType = '<?= $currentLogType ?>';

        // Function to toggle the log type and update UI elements
        function toggleLogType(logType) {
            currentLogType = logType; // Update global log type variable
            sessionStorage.setItem('currentLogType', logType); // Persist log type in session storage

            document.getElementById('log_type').value = logType; // Update hidden input value
            document.getElementById('log-type-label').textContent = logType.charAt(0).toUpperCase() + logType.slice(1);

            document.querySelectorAll('.log_type_input').forEach(input => {
                input.value = logType;
            });

            document.querySelectorAll('.action-btn').forEach(btn => {
                btn.textContent = logType.charAt(0).toUpperCase() + logType.slice(1);
            });

            // Check and update the status for each server based on the selected log type
            checkLogStatus(logType);
        }

        // Initialize the log type when the page loads, based on session storage or default to 'backup'
        window.onload = function() {
            let savedLogType = sessionStorage.getItem('currentLogType') || currentLogType;
            toggleLogType(savedLogType); // Set initial log type to saved value or default
        };

        // Attach event listeners to backup and replication buttons
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('backup-btn').addEventListener('click', () => toggleLogType('backup'));
            document.getElementById('replication-btn').addEventListener('click', () => toggleLogType('replication'));
        });

        // Function to check log status for each server based on the log type
        function checkLogStatus(logType) {
            fetch(`<?= base_url('/ServerBackupController/checkLogStatus') ?>?log_type=${logType}`)
                .then(response => response.json())
                .then(data => {
                    console.log('Fetched log status data:', data); // Debugging line
                    document.querySelectorAll('.status-cell').forEach((statusCell) => {
                        const serverId = statusCell.getAttribute('data-server-id');
                        // statusCell.textContent = data[serverId] === 'logged' ? 'Logged' : 'Pending';
                        // statusCell.className = data[serverId] === 'logged' ? 'status-cell status-logged' : 'status-cell status-pending';
                        // Check if data has a "logged" status for the current server
                        if (data[serverId] === 'logged') {
                            statusCell.textContent = 'Logged';
                            statusCell.className = 'status-cell status-logged';
                        } else {
                            statusCell.textContent = 'Pending';
                            statusCell.className = 'status-cell status-pending';
                        }
                    });
                })
                .catch(error => console.error('Error fetching log status:', error));
        }

        // Save the scroll position in sessionStorage
        function saveScrollPosition() {
            sessionStorage.setItem('scrollPosition', window.scrollY);
        }

        // Restore the scroll position on page load
        document.addEventListener('DOMContentLoaded', () => {
            const scrollPosition = sessionStorage.getItem('scrollPosition');
            if (scrollPosition) {
                window.scrollTo(0, scrollPosition);
            }
        });
    </script>
</head>

<body>
    <div class="container">
        <h2>Server Backup/Replication Log</h2>

        <!-- Top controls for changing log type -->
        <div class="top-controls">
            <button type="button" class="btn" id="backup-btn">Backup</button>
            <button type="button" class="btn" id="replication-btn">Replication</button>
            <input type="hidden" name="log_type" id="log_type" value="backup">
            <span class="log-type-display">Selected Log Type: <span id="log-type-label">Backup</span></span>
        </div>

        <!-- Log form with individual submission per server -->
        <table>
            <thead>
                <tr>
                    <th>Server Name</th>
                    <th>Status</th>
                    <th>Last Successful Time</th>
                    <th>Reference Time</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($servers as $server): ?>
                    <tr>
                        <form action="<?= base_url('/server-backup/saveLog') ?>" method="POST" onsubmit="saveScrollPosition()">
                            <input type="hidden" name="log_type" class="log_type_input" value="backup">
                            <input type="hidden" name="server_id" value="<?= $server['id'] ?>">

                            <td><?= esc($server['server_name']) ?></td>
                            <!-- Status cell with data-server-id attribute -->
                            <td class="status-cell" data-server-id="<?= $server['id'] ?>">Pending</td>
                            <td><input type="time" name="last_successful_time" required></td>
                            <td><span><?= esc($server['reference_time']) ?></span></td>
                            <td><button type="submit" class="action-btn">Backup</button></td>
                        </form>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h2>Database Backup Log</h2>

        <table>
            <thead>
                <tr>
                    <th>Database Name</th>
                    <th>DC:82</th>
                    <th>DR:126</th>
                    <th>NDC:6</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($databases as $db): ?>
                    <tr>
                        <form action="<?= base_url('/server-backup/saveDbBackupLog') ?>" method="POST" onsubmit="saveScrollPosition()">
                            <input type="hidden" name="db_name_id" value="<?= $db['id'] ?>">

                            <td><?= esc($db['db_name']) ?></td>
                            <td><input type="time" name="backup_time_dc" id="backup_time_dc"></td>
                            <td><input type="time" name="backup_time_dr" id="backup_time_dr"></td>
                            <td><input type="time" name="backup_time_ndc" id="backup_time_ndc"></td>
                            <td><button type="submit" class="action-btn">Save</button></td>
                        </form>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h2>Storage Free Space</h2>

        <!-- Table for Storage Free Space -->
        <table>
            <thead>
                <tr>
                    <th>Storage Name</th>
                    <th>Free Space</th>
                    <th>Unit</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($storageNames as $storage): ?>
                    <tr>
                        <form action="<?= base_url('/server-backup/saveStorageSpace') ?>" method="POST" onsubmit="saveScrollPosition()">
                            <input type="hidden" name="storage_name_id" value="<?= $storage['id'] ?>">

                            <td><?= esc($storage['storage_type']) ?></td>
                            <td><input type="number" name="free_space"></td>
                            <td>
                                <select name="unit">
                                    <option value="MB">MB</option>
                                    <option value="GB">GB</option>
                                    <option value="TB">TB</option>
                                </select>
                            </td>
                            <td><button type="submit" class="action-btn">Submit</button></td>
                        </form>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h2>Additional Information</h2>

        <form action="<?= base_url('/server-backup/saveAdditionalInfo') ?>" method="POST" onsubmit="saveScrollPosition()">
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Remarks</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <select name="user_id" required>
                                <option value="" disabled selected>Select User</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?= $user['id'] ?>"><?= esc($user['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <textarea name="remarks" rows="3" ></textarea>
                        </td>
                        <td>
                            <button type="submit" class="action-btn">Submit</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </form>
    </div>
</body>

</html>