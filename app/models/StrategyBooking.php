<?php
declare(strict_types=1);

namespace App\Models;

use Core\Model;

class StrategyBooking extends Model
{
    public int $id;
    public string $full_name;
    public string $work_email;
    public string $phone_number;
    public string $company_name;
    public string $status = 'pending';
    public ?string $admin_notes = null;
    public string $created_at;

    public function __construct()
    {
        $this->table = 'strategy_bookings';
        parent::__construct();
    }
}
