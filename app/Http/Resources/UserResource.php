<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Transform a user without exposing authentication credentials or private preferences.
 */
class UserResource extends JsonResource
{
    /**
     * Return the public administration fields for the user.
     *
     * @param Request $request
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->resource->id,
            'name'              => $this->resource->name,
            'email'             => $this->resource->email,
            'status'            => (bool) $this->resource->status,
            'email_verified_at' => $this->resource->email_verified_at,
            'created_at'        => $this->resource->created_at,
        ];
    }
}
