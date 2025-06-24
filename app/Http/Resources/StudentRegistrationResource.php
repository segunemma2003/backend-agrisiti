<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentRegistrationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'location' => $this->location,
            'experience_level' => $this->experience_level,
            'experience_level_label' => $this->getExperienceLevelLabel($this->experience_level),
            'interests' => $this->interests ?? [],
            'motivation' => $this->motivation,
            'is_active' => $this->is_active,
            'is_verified' => $this->is_verified,
            'is_contacted' => $this->is_contacted,
            'days_since_registration' => $this->days_since_registration,
            'registration_date' => $this->created_at->toDateTimeString(),
            'registration_date_formatted' => $this->created_at->format('M d, Y'),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
