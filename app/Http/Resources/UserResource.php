<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role->value,
            'role_label' => $this->role_label,
            'status' => $this->status->value,
            'status_label' => $this->status_label,
            'is_active' => $this->isActive(),
            'last_login_at' => $this->last_login_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_by' => $this->creator ? [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ] : null,
            'updated_by' => $this->updater ? [
                'id' => $this->updater->id,
                'name' => $this->updater->name,
            ] : null,

            // Include profile data for edit form
            'staff_profile' => $this->whenLoaded('staffProfile', function() {
                return [
                    'id' => $this->staffProfile->id,
                    'staff_code' => $this->staffProfile->staff_code,
                    'position' => $this->staffProfile->position,
                    'hired_at' => $this->staffProfile->hired_at,
                    'branch_id' => $this->staffProfile->branch_id,
                ];
            }),
            
            'customer_profile' => $this->whenLoaded('customerProfile', function() {
                return [
                    'id' => $this->customerProfile->id,
                    'customer_code' => $this->customerProfile->customer_code,
                    'loyalty_points' => $this->customerProfile->loyalty_points,
                    'credit_limit' => $this->customerProfile->credit_limit,
                    'branch_id' => $this->customerProfile->branch_id,
                ];
            }),
            
            'supplier_profile' => $this->whenLoaded('supplierProfile', function() {
                return [
                    'id' => $this->supplierProfile->id,
                    'company_name' => $this->supplierProfile->company_name,
                    'payment_terms' => $this->supplierProfile->payment_terms,
                    'tax_id' => $this->supplierProfile->tax_id,
                    'is_active' => $this->supplierProfile->is_active,
                    'branch_id' => $this->supplierProfile->branch_id,
                ];
            }),
        ];
    }
}
