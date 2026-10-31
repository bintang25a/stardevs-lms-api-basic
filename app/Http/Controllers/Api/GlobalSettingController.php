<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GlobalSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GlobalSettingController extends Controller
{
  /**
   * Display a listing of the resource.
   */
  public function index()
  {
    $settings = GlobalSetting::all();

    return response()->json([
      'success' => true,
      'message' => 'List of global settings retrieved successfully',
      'data'    => $settings
    ], 200);
  }

  /**
   * Store a newly created resource in storage.
   */
  public function store(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'key'   => 'required|string|max:255|unique:global_settings,key',
      'value' => 'required|string',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation Errors',
        'errors'  => $validator->errors()
      ], 422);
    }

    $setting = GlobalSetting::create([
      'key'   => $request->key,
      'value' => $request->value,
    ]);

    return response()->json([
      'success' => true,
      'message' => 'Global setting created successfully',
      'data'    => $setting
    ], 201);
  }

  /**
   * Display the specified resource.
   */
  public function show(string $id)
  {
    $setting = GlobalSetting::find($id);

    if (! $setting) {
      return response()->json([
        'success' => false,
        'message' => 'Global setting not found'
      ], 404);
    }

    return response()->json([
      'success' => true,
      'message' => 'Global setting retrieved successfully',
      'data'    => $setting
    ], 200);
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(Request $request, string $id)
  {
    $setting = GlobalSetting::find($id);

    if (! $setting) {
      return response()->json([
        'success' => false,
        'message' => 'Global setting not found'
      ], 404);
    }

    $validator = Validator::make($request->all(), [
      'key'   => 'sometimes|required|string|max:255|unique:global_settings,key,' . $id,
      'value' => 'sometimes|required|string',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation Errors',
        'errors'  => $validator->errors()
      ], 422);
    }

    $setting->update($request->only(['key', 'value']));

    return response()->json([
      'success' => true,
      'message' => 'Global setting updated successfully',
      'data'    => $setting
    ], 200);
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy(string $id)
  {
    $setting = GlobalSetting::find($id);

    if (! $setting) {
      return response()->json([
        'success' => false,
        'message' => 'Global setting not found'
      ], 404);
    }

    $setting->delete();

    return response()->json([
      'success' => true,
      'message' => 'Global setting deleted successfully'
    ], 200);
  }
}
