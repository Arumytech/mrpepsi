<?php
namespace App\Models;

use App\Core\Model;

class ExpenseCategory extends Model {
    protected string $table = 'expense_categories';

    public function getActive(): array {
        return $this->where('is_active', 1, 'name ASC');
    }
}
