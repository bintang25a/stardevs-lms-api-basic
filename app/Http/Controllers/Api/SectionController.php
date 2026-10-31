<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SectionController extends Controller
{
  /**
   * Display a listing of the resource.
   */
  public function index(Request $request)
  {
    //
  }

  /**
   * Store a newly created resource in storage.
   */
  public function store(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'date'          => 'nullable|date',
      'title'         => 'required|string|max:255',
      'classroom_id'  => 'required|string|exists:classrooms,id',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation Errors',
        'errors'  => $validator->errors()
      ], 422);
    }

    Section::create([
      'date'        => $request->date,
      'title'        => $request->title,
      'classroom_id' => $request->classroom_id,
    ]);

    return response()->json([
      'success' => true,
      'message' => 'Section created successfully',
    ], 201);
  }

  /**
   * Display the specified resource.
   */
  public function show(string $id)
  {
    //
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(Request $request, string $id)
  {
    $section = Section::find($id);

    if (! $section) {
      return response()->json([
        'success' => false,
        'message' => 'Section not found'
      ], 404);
    }

    $validator = Validator::make($request->all(), [
      'date'          => 'nullable|date',
      'title'         => 'required|string|max:255',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation Errors',
        'errors'  => $validator->errors()
      ], 422);
    }

    $section->update($request->only([
      'date',
      'title',
    ]));

    return response()->json([
      'success' => true,
      'message' => 'Section updated successfully',
    ], 200);
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy(string $id)
  {
    $section = Section::find($id);

    if (! $section) {
      return response()->json([
        'success' => false,
        'message' => 'Section not found'
      ], 404);
    }

    $section->delete();

    return response()->json([
      'success' => true,
      'message' => 'Section deleted successfully'
    ], 200);
  }
}
