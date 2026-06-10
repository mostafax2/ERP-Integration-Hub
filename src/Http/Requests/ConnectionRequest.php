<?php

declare(strict_types=1);

namespace Mostafax\ErpIntegrationHub\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConnectionRequest extends FormRequest
{
    private const MICROSOFT_DRIVERS = ['business_central', 'dynamics_finance', 'supply_chain'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isMicrosoft = in_array($this->input('driver'), self::MICROSOFT_DRIVERS, strict: true);

        return [
            'name'             => 'required|string|max:255',
            'driver'           => 'required|string',
            'tenant_id'        => $isMicrosoft ? 'required|string|max:255' : 'nullable|string|max:255',
            'client_id'        => 'required|string|max:255',
            'client_secret'    => 'required|string|max:2048',
            'environment_name' => 'nullable|string|max:255',
            'base_url'         => 'nullable|url',
            'company_id'       => 'nullable|string|max:255',
            'extra_config'     => 'nullable|array',
            'is_default'       => 'nullable|boolean',
            'test_after_create' => 'nullable|boolean',
        ];
    }
}
