<?php
declare(strict_types=1);

namespace App\Models;

use Core\Model;

class ContactSubmission extends Model
{
    public int $id;
    public string $name;
    public string $email;
    public ?string $subject = null;
    public string $message;
    public string $status = 'unread';
    public string $created_at;

    public function __construct()
    {
        $this->table = 'contact_submissions';
        parent::__construct();
    }
}
