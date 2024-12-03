<?php
namespace App\Models;

use CodeIgniter\Model;

class ReplicationLogModel extends Model
{
    protected $table = 'Replication_Logdb';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'server_id',
        'last_successful_time',
        'log_time',
        'created_date',
    ];

    protected $createdField  = 'created_date';

    public function getTodaysLogs()
    {
        return $this->where('created_date', date('Y-m-d'))->findAll(); // Adjust column name if necessary
    }
}
