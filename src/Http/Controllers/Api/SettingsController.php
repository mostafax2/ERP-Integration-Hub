<?php

declare(strict_types=1);

namespace Mostafax\ErpIntegrationHub\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Mostafax\ErpIntegrationHub\Models\DynamicsSetting;

class SettingsController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('manage_settings');

        $settings = DynamicsSetting::where('is_public', true)->get()->groupBy('group');
        return response()->json(['data' => $settings]);
    }

    public function update(Request $request): JsonResponse
    {
        $this->authorize('manage_settings');

        $data = $request->validate(['settings' => 'required|array']);
        foreach ($data['settings'] as $key => $value) {
            DynamicsSetting::set($key, $value);
        }
        return response()->json(['message' => 'Settings saved.']);
    }

    public function get(string $key): JsonResponse
    {
        $this->authorize('manage_settings');

        return response()->json(['data' => DynamicsSetting::get($key)]);
    }
}
