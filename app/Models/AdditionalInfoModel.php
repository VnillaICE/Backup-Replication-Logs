<?php
namespace App\Models;

use CodeIgniter\Model;

class AdditionalInfoModel extends Model
{
    protected $table = 'additional_info';
    protected $primaryKey = 'id';
    protected $allowedFields = ['user_id', 'creation_date', 'remarks'];
}
