<?php

namespace App\Models;

use CodeIgniter\Model;

class StorageSpaceModel extends Model
{
    protected $table = 'storage_space';
    protected $primaryKey = 'id';
    protected $allowedFields = ['storage_name_id', 'free_space', 'submission_date'];
}

