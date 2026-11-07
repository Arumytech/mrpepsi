<?php
namespace App\Models;

use App\Core\Model;

class StaffOffice extends Model {
    protected string $table = 'staff_offices';

    public function getActive(): array {
        return $this->where('is_active', 1, 'name ASC');
    }
}
