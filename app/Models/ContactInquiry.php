<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

final class ContactInquiry extends Model
{
    protected static string $table = 'contact_inquiries';

    public static function newCount(): int
    {
        return static::count("status = 'new'");
    }
}
