<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'suffix', 'nickname', 'gender', 'birthday', 'name_pronunciation',
        'personal_email', 'mobile_number', 'viber_number', 'telephone', 'local_extension',
        'office_seat', 'office_location_id', 'about_me', 'facebook_url', 'linkedin_url',
        'emergency_contact_name', 'emergency_contact_relationship', 'emergency_contact_phone',
    ];

    protected $casts = [
        'birthday' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function officeLocation(): BelongsTo
    {
        return $this->belongsTo(OfficeLocation::class);
    }
}
