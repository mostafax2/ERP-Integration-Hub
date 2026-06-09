<?php

namespace Mostafax\ErpIntegrationHub\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Mostafax\ErpIntegrationHub\Models\DynamicsSetting;

class SettingsController extends Controller
{
    public function index(): JsonResponse
    {
        $settings = DynamicsSetting::all()->groupBy('group');
        return response()->json(['data' => $settings]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate(['settings' => 'required|array']);
        foreach ($data['settings'] as $key => $value) {
            DynamicsSetting::set($key, $value);
        }
        return response()->json(['message' => 'Settings saved.']);
    }

    public function get(string $key): JsonResponse
    {
        return response()->json(['data' => DynamicsSetting::get($key)]);
    }
}
