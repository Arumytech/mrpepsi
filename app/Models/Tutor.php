<?php
namespace App\Models;

use App\Core\Model;

class Tutor extends Model {
    protected string $table = 'tutors';

    public function getActive(): array {
        return $this->where('is_active', 1, 'name ASC');
    }
}
