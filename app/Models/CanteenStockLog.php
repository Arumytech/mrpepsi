<?php
namespace App\Models;

use App\Core\Model;

class CanteenStockLog extends Model {
    protected string $table = 'canteen_stock_logs';

    public function getLogs(int $limit = 50): array {
        $sql = "SELECT l.*, p.name AS product_name, p.barcode 
                FROM `{$this->table}` l 
                JOIN canteen_products p ON l.product_id = p.id 
                ORDER BY l.id DESC LIMIT {$limit}";
        return $this->raw($sql);
    }
}
