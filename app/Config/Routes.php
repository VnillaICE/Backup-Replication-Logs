<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'ServerBackupController::index');
$routes->get('/server-backup', 'ServerBackupController::index');
$routes->post('/server-backup/saveLog', 'ServerBackupController::saveLog');
$routes->post('ServerBackupController/checkLogStatus', 'ServerBackupController::checkLogStatus');
$routes->get('ServerBackupController/checkLogStatus', 'ServerBackupController::checkLogStatus');
$routes->post('/server-backup/saveDbBackupLog', 'ServerBackupController::saveDBBackup');
$routes->post('/server-backup/saveStorageSpace', 'ServerBackupController::saveStorageSpace');
$routes->post('/server-backup/saveAdditionalInfo', 'ServerBackupController::saveAdditionalInfo');

$routes->get('/server-backup/viewLogs', 'ServerBackupController::viewLogs');
$routes->get('/view-logs', 'ServerBackupController::viewLogs');

$routes->get('/download-pdf', 'ServerBackupController::downloadPDF');



