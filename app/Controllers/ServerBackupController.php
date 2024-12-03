<?php

namespace App\Controllers;

use App\Models\ServerModel;
use App\Models\BackupLogModel;
use App\Models\ReplicationLogModel;
use App\Models\DBBackupLogModel;
use App\Models\DBNamesModel;
use App\Models\StorageNamesModel;
use App\Models\StorageSpaceModel;
use App\Models\UserModel;
use App\Models\AdditionalInfoModel;
use DateTime;
use Dompdf\Dompdf;
use Dompdf\Options;

class ServerBackupController extends BaseController
{
    public function index()
    {
        $serverModel = new ServerModel();
        $dbModel = new DBNamesModel();
        $storageNamesModel = new StorageNamesModel();
        $userModel = new UserModel();

        $users = $userModel->findAll();
        $servers = $serverModel->findAll();
        $databases = $dbModel->findAll();
        $storageNames = $storageNamesModel->findAll();
        // Pass 'backup' as the initial log type, or retrieve from session
        $currentLogType = session()->get('currentLogType') ?? 'backup';

        return view('server_backup', [
            'servers' => $servers,
            'currentLogType' => $currentLogType,
            'databases' => $databases,
            'storageNames' => $storageNames,
            'users' => $users
        ]);
    }

    public function saveLog()
    {
        // Retrieve form data
        $logType = $this->request->getPost('log_type'); // backup or replication
        session()->set('currentLogType', $logType); // Store the log type in session
        $serverId = $this->request->getPost('server_id');
        $lastSuccessfulTime = $this->request->getPost('last_successful_time');

        // Get the current time as log time (format H:i:s)
        $logTime = date('H:i:s');

        // Get the current date for created_date (without time)
        $createdDate = date('Y-m-d');

        // Prepare the data to be saved or updated
        $data = [
            'server_id' => $serverId,
            'last_successful_time' => $lastSuccessfulTime,
            'log_time' => $logTime,
            'created_date' => $createdDate,
        ];

        try {
            if ($logType === 'backup') {
                $backupLogModel = new BackupLogModel();

                // Check if a log already exists for this server for today
                $existingLog = $backupLogModel->where('server_id', $serverId)
                    ->where('created_date', $createdDate)
                    ->first();

                if ($existingLog) {
                    // If the log exists, update it
                    $backupLogModel->update($existingLog['id'], $data);
                    session()->setFlashdata('success', 'Backup log updated successfully!');
                } else {
                    // If no log exists for today, insert a new log
                    $backupLogModel->save($data);
                    // Return a message indicating that the log was created
                    session()->setFlashdata('success', 'Backup log saved successfully!');
                }
            } else if ($logType === 'replication') {
                $replicationLogModel = new ReplicationLogModel();

                // Check if a log already exists for this server for today
                $existingLog = $replicationLogModel->where('server_id', $serverId)
                    ->where('created_date', $createdDate)
                    ->first();

                if ($existingLog) {
                    // If the log exists, update it
                    $replicationLogModel->update($existingLog['id'], $data);
                    session()->setFlashdata('success', 'Replication log updated successfully!');
                } else {
                    // If no log exists for today, insert a new log
                    $replicationLogModel->save($data);
                    session()->setFlashdata('success', 'Replication log saved successfully!');
                }
            }
        } catch (\Exception $e) {
            return redirect()->to('/server-backup')->with('error', 'An error occured while saving the log. Please try again.');
        }
        return redirect()->to('/server-backup');
    }

    public function checkLogStatus()
    {
        $logType = $this->request->getGet('log_type'); // Fetch the log type from the query parameter
        $logModel = ($logType === 'backup') ? new BackupLogModel() : new ReplicationLogModel();

        try {
            // Fetch today's log data for each server
            $logs = $logModel->getTodaysLogs(); // Adjust method if it requires parameters
            $statusData = [];

            foreach ($logs as $log) {
                $statusData[$log['server_id']] = 'logged';
            }

            // Respond with the log status in JSON format
            return $this->response->setJSON($statusData);
        } catch (\Exception $e) {
            // Catch any exceptions and return an error response
            return $this->response->setJSON(['error' => 'Failed to fetch log status', 'message' => $e->getMessage()], 500);
        }
    }

    public function saveDBBackup()
    {
        $dbBackupLogModel = new DBBackupLogModel();  // Model for `db_backup_log` table

        $db_name_id = $this->request->getPost('db_name_id');
        $backup_time_dc = $this->request->getPost('backup_time_dc');
        $backup_time_dr = $this->request->getPost('backup_time_dr');
        $backup_time_ndc = $this->request->getPost('backup_time_ndc');
        $currentDate = date('Y-m-d'); // Get the current date

        // Validation: Ensure at least one field is filled
        if (empty($backup_time_dc) && empty($backup_time_dr) && empty($backup_time_ndc)) {
            session()->setFlashdata('error', 'At least one backup time field must be filled.');
            return redirect()->to(base_url('/server-backup'));
        }

        // Check if a log already exists for this database and day
        $existingLog = $dbBackupLogModel->where('db_name_id', $db_name_id)
            ->where('DATE(log_time_dc)', $currentDate) // Ensure it's the same day
            ->first();

        // Prepare data for partial updates
        $data = [];
        if ($backup_time_dc) {
            $data['log_time_dc'] = date('Y-m-d H:i:s'); // Update log time for DC
            $data['backup_time_dc'] = $backup_time_dc;  // Update backup time for DC
        }
        if ($backup_time_dr) {
            $data['log_time_dr'] = date('Y-m-d H:i:s'); // Update log time for DR
            $data['backup_time_dr'] = $backup_time_dr;  // Update backup time for DR
        }
        if ($backup_time_ndc) {
            $data['log_time_ndc'] = date('Y-m-d H:i:s'); // Update log time for NDC
            $data['backup_time_ndc'] = $backup_time_ndc; // Update backup time for NDC
        }

        if ($existingLog) {
            // Update only the specified fields for the existing log
            $dbBackupLogModel->update($existingLog['id'], $data);
            session()->setFlashdata('success', 'Database backup log updated successfully.');
        } else {
            // If no existing log, ensure all fields have default values to create a new row
            $newData = [
                'db_name_id' => $db_name_id,
                'log_time_dc' => isset($data['log_time_dc']) ? $data['log_time_dc'] : null,
                'backup_time_dc' => isset($data['backup_time_dc']) ? $data['backup_time_dc'] : null,
                'log_time_dr' => isset($data['log_time_dr']) ? $data['log_time_dr'] : null,
                'backup_time_dr' => isset($data['backup_time_dr']) ? $data['backup_time_dr'] : null,
                'log_time_ndc' => isset($data['log_time_ndc']) ? $data['log_time_ndc'] : null,
                'backup_time_ndc' => isset($data['backup_time_ndc']) ? $data['backup_time_ndc'] : null,
            ];
            $dbBackupLogModel->insert($newData);

            session()->setFlashdata('success', 'Database backup log saved successfully.');
        }
        return redirect()->to(base_url('/server-backup'));
    }

    public function saveStorageSpace()
    {
        $storageSpaceModel = new StorageSpaceModel(); // Model for `storage_space` table

        // Get the submitted values
        $storage_name_id = $this->request->getPost('storage_name_id');
        $free_space = $this->request->getPost('free_space');
        $unit = $this->request->getPost('unit');
        $submission_date = date('Y-m-d'); // Automatically set the submission date

        // Convert the free space to a standard unit (MB) based on the selected unit
        if ($unit == 'GB') {
            $free_space = $free_space * 1024; // Convert GB to MB
        } elseif ($unit == 'TB') {
            $free_space = $free_space * 1024 * 1024; // Convert TB to MB
        }

        // Check if there's already an entry for the storage name on the current day
        $existingEntry = $storageSpaceModel->where('storage_name_id', $storage_name_id)
            ->where('DATE(submission_date)', $submission_date)
            ->first();

        if ($existingEntry) {
            // If an existing entry is found, update it with the new free space
            $data = [
                'free_space' => $free_space,
                'submission_date' => $submission_date
            ];
            $storageSpaceModel->update($existingEntry['id'], $data);
            session()->setFlashdata('success', 'Storage space data updated successfully.');
        } else {
            // If no existing entry is found, create a new entry
            $data = [
                'storage_name_id' => $storage_name_id,
                'free_space' => $free_space,
                'submission_date' => $submission_date
            ];
            $storageSpaceModel->save($data);
            session()->setFlashdata('success', 'Storage space data saved successfully.');
        }
        return redirect()->to(base_url('/server-backup'));
    }

    public function saveAdditionalInfo()
    {
        $additionalInfoModel = new AdditionalInfoModel(); // Model for `additional_info` table

        // Retrieve form data
        $user_id = $this->request->getPost('user_id');
        $remarks = $this->request->getPost('remarks');
        $current_date = date('Y-m-d'); // Current date

        // Validate input
        if (empty($user_id)) {
            session()->setFlashdata('error', 'User required.');
            return redirect()->back()->withInput();
        }

        // Check if an entry already exists for the user on the same date
        $existingEntry = $additionalInfoModel
            ->where('user_id', $user_id)
            ->where('creation_date', $current_date)
            ->first();

        if ($existingEntry) {
            // Update the existing entry
            $additionalInfoModel->update($existingEntry['id'], [
                'remarks' => $remarks,
            ]);
            session()->setFlashdata('success', 'Additional information updated successfully.');
        } else {
            // Insert a new entry
            $additionalInfoModel->insert([
                'user_id' => $user_id,
                'remarks' => $remarks,
                'creation_date' => $current_date,
            ]);
            session()->setFlashdata('success', 'Additional information saved successfully.');
        }

        return redirect()->to(base_url('/server-backup'));
    }

    public function viewLogs($selectedDate = null, $returnData = false)
    {
        if (!$selectedDate) {
            // Retrieve the 'log_date' parameter from the GET request if 'selectedDate' is not already set
            $logDate = $this->request->getGet('log_date');
            // Convert the retrieved 'log_date' into the 'Y-m-d' format and assign it to 'selectedDate'
            $selectedDate = date('Y-m-d', strtotime($logDate));
        }

        $logDate = $this->request->getGet('log_date');
        $selectedDate = date('Y-m-d', strtotime($logDate));

        $backupLogModel = new BackupLogModel();
        $replicationLogModel = new ReplicationLogModel();
        $dbBackupLogModel = new DBBackupLogModel();
        $storageSpaceModel = new StorageSpaceModel();
        $additionalInfoModel = new AdditionalInfoModel();
        $serverModel = new ServerModel();

        // Fetch logs for the selected date
        $backupLogs = $backupLogModel
            ->select('server_names.server_name AS name, backup_logdb.log_time, backup_logdb.last_successful_time, "backup" AS log_type')
            ->join('server_names', 'backup_logdb.server_id = server_names.id')
            ->where('created_date', $selectedDate)
            ->findAll();

        $replicationLogs = $replicationLogModel
            ->select('server_names.server_name AS name, replication_logdb.log_time, replication_logdb.last_successful_time, "replication" AS log_type')
            ->join('server_names', 'replication_logdb.server_id = server_names.id')
            ->where('created_date', $selectedDate)
            ->findAll();

        $dbBackupLogs = $dbBackupLogModel
            ->select('db_names.db_name AS name, db_backup_log.log_time_dc, db_backup_log.backup_time_dc, db_backup_log.log_time_dr, db_backup_log.backup_time_dr, db_backup_log.log_time_ndc, db_backup_log.backup_time_ndc')
            ->join('db_names', 'db_backup_log.db_name_id = db_names.id')
            ->groupStart()
            ->where('DATE(log_time_dc)', $selectedDate)
            ->orWhere('DATE(log_time_dr)', $selectedDate)
            ->orWhere('DATE(log_time_ndc)', $selectedDate)
            ->groupEnd()
            ->findAll();

        $storageSpaceLogs = $storageSpaceModel
            ->select('storage_names.storage_type AS name, storage_space.free_space')
            ->join('storage_names', 'storage_space.storage_name_id = storage_names.id')
            ->where('DATE(submission_date)', $selectedDate)
            ->findAll();

        $additionalInfoLogs = $additionalInfoModel
            ->select('users.name AS name, additional_info.remarks')
            ->join('users', 'additional_info.user_id = users.id')
            ->where('DATE(creation_date)', $selectedDate)
            ->findAll();

        // Normalize logs to a common structure
        $normalizedBackupLogs = array_map(function ($log) use ($serverModel) {
            // Retrieve the reference time for this server
            $server = $serverModel->getServerByName($log['name']);  // Fetch server info from the server_names table
            $reference_time_str = $server['reference_time_min'] ?? '0';  // Default if no reference_time exists

            // Convert log time and last successful time to DateTime objects
            $log_time = new DateTime($log['log_time']);
            $last_successful_time = new DateTime($log['last_successful_time']);

            // Calculate the time difference in minutes
            $time_diff = $log_time->diff($last_successful_time);
            $diff_minutes = $time_diff->h * 60 + $time_diff->i;  // Total difference in minutes

            // Determine if the log is delayed based on the reference time
            $is_delayed = $diff_minutes > $reference_time_str;
            return [
                'name' => $log['name'],
                'log_time' => $log['log_time'],
                'last_successful_time' => $log['last_successful_time'],
                'log_type' => $log['log_type'],
                'is_delayed' => $is_delayed,
            ];
        }, $backupLogs);

        $normalizedReplicationLogs = array_map(function ($log) use ($serverModel) {
            $server = $serverModel->getServerByName($log['name']);
            // Default if no reference_time exists
            $reference_time_str = $server['reference_time_min'] ?? '0';

            // Convert log time and last successful time to DateTime objects
            $log_time = new DateTime($log['log_time']);
            $last_successful_time = new DateTime($log['last_successful_time']);
            $time_diff = $log_time->diff($last_successful_time);
            $diff_minutes = $time_diff->h * 60 + $time_diff->i;

             // Calculate the time difference in minutes
            $is_delayed = $diff_minutes > $reference_time_str;
            return [
                'name' => $log['name'],
                'log_time' => $log['log_time'],
                'last_successful_time' => $log['last_successful_time'],
                'log_type' => $log['log_type'],
                'is_delayed' => $is_delayed,
            ];
        }, $replicationLogs);

        $normalizedDbBackupLogs = array_map(function ($log) {
            return [
                'name' => $log['name'],
                'log_time_dc' => $log['log_time_dc'] ? date('H:i:s', strtotime($log['log_time_dc'])) : null,
                'backup_time_dc' => $log['backup_time_dc'],
                'log_time_dr' => $log['log_time_dr'] ? date('H:i:s', strtotime($log['log_time_dr'])) : null,
                'backup_time_dr' => $log['backup_time_dr'],
                'log_time_ndc' => $log['log_time_ndc'] ? date('H:i:s', strtotime($log['log_time_ndc'])) : null,
                'backup_time_ndc' => $log['backup_time_ndc'],
            ];
        }, $dbBackupLogs);

        $normalizedStorageSpaceLogs = array_map(function ($log) {
            return [
                'name' => $log['name'],
                'free_space' => $log['free_space'],
            ];
        }, $storageSpaceLogs);

        $normalizedAdditionalInfoLogs = array_map(function ($log) {
            return [
                'submitted_by' => $log['name'],
                'remarks' => $log['remarks'],
            ];
        }, $additionalInfoLogs);

        // Combine all normalized logs into one array
        $logs = [
            'backupLogs' => $normalizedBackupLogs,
            'replicationLogs' => $normalizedReplicationLogs,
            'dbBackupLogs' => $normalizedDbBackupLogs,
            'storageSpaceLogs' => $normalizedStorageSpaceLogs,
            'additionalInfoLogs' => $normalizedAdditionalInfoLogs,
        ];
        // Return data for reuse or render the view
        if ($returnData) {
            return $logs;
        }
        return view('view_logs', [
            'logs' => $logs,
            'selectedDate' => $selectedDate,
        ]);
    }

    public function downloadPDF()
    {
        $logDate = $this->request->getGet('log_date');
        $selectedDate = date('Y-m-d', strtotime($logDate));

        // Reuse the logic from viewLogs
        $logs = $this->viewLogs($selectedDate, true); // Pass a flag to return data instead of rendering the view.

        // Generate the PDF content using the fetched logs
        $html = view('pdf_template', ['logs' => $logs, 'selectedDate' => $selectedDate]);

        // Initialize Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
    
        // Set paper size and render
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Stream the generated PDF
        $dompdf->stream("logs_$selectedDate.pdf", ["Attachment" => true]);
    }
}
