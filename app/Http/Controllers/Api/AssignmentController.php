<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AssignmentController extends Controller
{
  /**
   * Display a listing of the resource.
   */
  public function index(Request $request)
  {
    $search = $request->query('search') ?? $request->query('keyword');
    $perPage = $request->query('per_page', 10);

    $query = Assignment::all();

    if (!empty($search)) {
      $query->where(function ($q) use ($search) {
        $q->where('title', 'LIKE', "%{$search}%")
          ->orWhere('description', 'LIKE', "%{$search}%");
      });
    }

    $assignments = $query->paginate($perPage);

    return response()->json([
      'success' => true,
      'message' => 'Assignments retrieved successfully',
      'data'    => $assignments->through(function ($assignment) {
        return [
          'id'              => $assignment->id,
          'title'           => $assignment->title,
          'description'     => $assignment->description,
          'started_at'      => $assignment->started_at,
          'ended_at'        => $assignment->ended_at,
          'allow_overtime'  => $assignment->allow_overtime,
          'classroom_id'    => $assignment->classroom_id,
          'created_at'      => $assignment->created_at,
          'updated_at'      => $assignment->updated_at,
        ];
      })
    ], 200);
  }

  /**
   * Store a newly created resource in storage.
   */
  public function store(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'title'           => 'required|string|max:255',
      'description'     => 'nullable|string',
      'started_at'      => 'required|date',
      'ended_at'        => 'required|date',
      'allow_overtime'  => 'nullable|boolean',
      'classroom_id'    => 'required|string|exists:classrooms,id',
      'section_id'      => 'nullable|string|exists:sections,id',
      'order'           => 'nullable|numeric',

      'files'         => 'nullable|array',
      'files.*.name'  => 'nullable|string',
      'files.*.file'  => 'nullable|file|extensions:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif,c,cpp,java,py,ipynb,js,zip,rar|max:20480',
      'files.*.url'   => 'nullable|string',
      'files.*.type'  => 'required|string|max:255',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation Errors',
        'errors'  => $validator->errors()
      ], 422);
    }

    DB::transaction(function () use ($request) {
      $assignment = Assignment::create([
        'title'           => $request->title,
        'description'     => $request->description,
        'started_at'      => $request->started_at,
        'ended_at'        => $request->ended_at,
        'allow_overtime'  => $request->allow_overtime,
        'classroom_id'    => $request->classroom_id,
        'section_id'      => $request->section_id,
        'order'           => $request->order,
      ]);

      if ($request->has('files') && is_array($request->files)) {
        foreach ($request->files as $item) {
          $name = $item['name'] ?? null;
          $file = $item['file'] ?? null;
          $url  = $item['url'] ?? null;
          $type = $item['type'] ?? 'assignment';

          $filePath = null;
          $fileUrl  = null;
          $originalName = null;

          if ($file && $file->isValid()) {
            $originalName = $file->getClientOriginalName();
            $fileName = time() . '_' . str_replace(' ', '_', $originalName);

            $filePath = $file->storeAs($type . 's', $fileName, 'public');
            $fileUrl  = Storage::url($filePath);

            $name = $name ? $name : $originalName;
          } else if ($url) {
            $parsedUrl = parse_url($url, PHP_URL_PATH);
            $name = $parsedUrl ? basename($parsedUrl) : 'External Link';
            $fileUrl = $url;
          }

          Attachment::create([
            'file_name'   => $name,
            'file_path'   => $filePath,
            'file_url'    => $fileUrl ?? $url,
            'class_model' => 'App\Models\Assignment',
            'class_id'    => $assignment->id,
            'type'        => $type,
          ]);
        }
      }
    });

    return response()->json([
      'success' => true,
      'message' => 'Assignment created successfully',
    ], 201);
  }

  /**
   * Display the specified resource.
   */
  public function show(Request $request, string $id)
  {
    $user = $request->user()->currentAccessToken()->abilities;
    $assignment = Assignment::with('classroom')->find($id);

    if (! $assignment) {
      return response()->json([
        'success' => false,
        'message' => 'Assignment not found'
      ], 404);
    }

    if ($user->role === 'praktikan') {
      $type = ['assignments', 'testcases'];
    } else {
      $type = ['assignments', 'testcases', 'answerkeys'];
    }

    $files = Attachment::where('class_id', $id)
      ->where('class_model', 'App\Models\Assignment')
      ->where('type', $type)
      ->get();

    return response()->json([
      'success' => true,
      'message' => 'Assignment retrieved successfully',
      'data'    => [
        'id'                => $assignment->id,
        'title'             => $assignment->title,
        'description'       => $assignment->description,
        'started_at'        => $assignment->started_at,
        'ended_at'          => $assignment->ended_at,
        'allow_overtime'    => $assignment->allow_overtime,
        'classroom_id'      => $assignment->classroom_id,
        'section_id'        => $assignment->section_id,
        'order'             => $assignment->order,
        'classroom'         => $assignment->classroom,
        'files'             => $files,
        'created_at'        => $assignment->created_at,
        'updated_at'        => $assignment->updated_at,
      ]
    ], 200);
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(Request $request, string $id)
  {
    $assignment = Assignment::find($id);

    if (! $assignment) {
      return response()->json([
        'success' => false,
        'message' => 'Assignment not found'
      ], 404);
    }

    $validator = Validator::make($request->all(), [
      'title'           => 'required|string|max:255',
      'description'     => 'nullable|string',
      'section_id'      => 'nullable|string|exists:sections,id',
      'order'           => 'nullable|numeric',
      'started_at'      => 'required|date',
      'ended_at'        => 'required|date',
      'allow_overtime'  => 'nullable|boolean',

      'files'         => 'nullable|array',
      'files.*.name'  => 'nullable|string',
      'files.*.file'  => 'nullable|file|extensions:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif,c,cpp,java,py,ipynb,js,zip,rar|max:20480',
      'files.*.url'   => 'nullable|string',
      'files.*.type'  => 'required|string|max:255',

      'files_remove_ids'    => 'nullable|array',
      'files_remove_ids.*'  => 'string|exists:attachments,id',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation Errors',
        'errors'  => $validator->errors()
      ], 422);
    }

    DB::transaction(function () use ($request, $assignment) {
      $assignment->update($request->only([
        'title'           => $request->title,
        'description'     => $request->description,
        'started_at'      => $request->started_at,
        'ended_at'        => $request->ended_at,
        'allow_overtime'  => $request->allow_overtime,
        'section_id'      => $request->section_id,
        'order'           => $request->order,
      ]));

      if ($request->has('files') && is_array($request->files)) {
        foreach ($request->files as $item) {
          $name = $item['name'] ?? null;
          $file = $item['file'] ?? null;
          $url  = $item['url'] ?? null;
          $type = $item['type'] ?? 'assignment';

          $filePath = null;
          $fileUrl  = null;
          $originalName = null;

          if ($file && $file->isValid()) {
            $originalName = $file->getClientOriginalName();
            $fileName = time() . '_' . str_replace(' ', '_', $originalName);

            $filePath = $file->storeAs($type . 's', $fileName, 'public');
            $fileUrl  = Storage::url($filePath);

            $name = $name ? $name : $originalName;
          } else if ($url) {
            $parsedUrl = parse_url($url, PHP_URL_PATH);
            $name = $parsedUrl ? basename($parsedUrl) : 'External Link';
            $fileUrl = $url;
          }

          Attachment::create([
            'file_name'   => $name,
            'file_path'   => $filePath,
            'file_url'    => $fileUrl ?? $url,
            'class_model' => 'App\Models\Assignment',
            'class_id'    => $assignment->id,
            'type'        => $type,
          ]);
        }
      }

      if ($request->has('files_remove_ids') && is_array($request->files_remove_ids)) {

        $attachmentsToRemove = Attachment::whereIn('id', $request->files_remove_ids)
          ->where('class_model', 'App\Models\Assignment')
          ->get();

        foreach ($attachmentsToRemove as $attachment) {
          if ($attachment->file_path && Storage::disk('public')->exists($attachment->file_path)) {
            Storage::disk('public')->delete($attachment->file_path);
          }

          $attachment->delete();
        }
      }
    });

    return response()->json([
      'success' => true,
      'message' => 'Assignment updated successfully',
    ], 200);
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy(string $id)
  {
    $assignment = Assignment::find($id);

    if (! $assignment) {
      return response()->json([
        'success' => false,
        'message' => 'Assignment not found'
      ], 404);
    }

    $attachmentsToRemove = Attachment::where('class_id', $assignment->id)
      ->where('class_model', 'App\Models\Assignment')
      ->get();

    foreach ($attachmentsToRemove as $attachment) {
      if ($attachment->file_path && Storage::disk('public')->exists($attachment->file_path)) {
        Storage::disk('public')->delete($attachment->file_path);
      }

      $attachment->delete();
    }

    $assignment->delete();

    return response()->json([
      'success' => true,
      'message' => 'Assignment deleted successfully'
    ], 200);
  }
}
