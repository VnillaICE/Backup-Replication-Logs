<?php

namespace App\Models;
use CodeIgniter\Model;

class ServerModel extends Model {
    protected $table = 'Server_names';
    protected $allowedFields = ['server_name'];

    public function getServerByName($server_name) {
        return $this->db->table('server_names')
                        ->where('server_name', $server_name)
                        ->get()
                        ->getRowArray(); 
    }
}
