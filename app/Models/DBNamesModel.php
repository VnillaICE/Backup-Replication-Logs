<?php
namespace App\Models;

use CodeIgniter\Model;

class DBNamesModel extends Model
{
    protected $table = 'db_names';
    protected $primaryKey = 'id';
    protected $allowedFields = ['db_name'];
}
