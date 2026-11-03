<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SubmissionController extends Controller
{
  /**
   * Display a listing of the resource.
   */
  public function index(Request $request)
  {
    $search = $request->query('search') ?? $request->query('keyword');
    $perPage = $request->query('per_page', 10);

    $query = Submission::all();

    if (!empty($search)) {
      $query->where(function ($q) use ($search) {
        $q->where('title', 'LIKE', "%{$search}%")
          ->orWhere('description', 'LIKE', "%{$search}%");
      });
    }

    $submissions = $query->paginate($perPage);

    return response()->json([
      'success' => true,
      'message' => 'Submissions retrieved successfully',
      'data'    => $submissions->through(function ($submission) {
        return [
          'id'            => $submission->id,
          'student_name'  => $submission->student_name,
          'answer'        => $submission->answer,
          'support'       => $submission->support,
          'score'         => $submission->score,
          'created_at'    => $submission->created_at,
          'updated_at'    => $submission->updated_at,
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
      'answer'        => 'nullable|string',
      'support'       => 'nullable|string',
      'classroom_id'  => 'required|string|exists:classrooms,id',
      'assignment_id' => 'required|string|exists:assignments,id',
      'section_id'    => 'nullable|string|exists:sections,id',
      'order'         => 'nullable|numeric',

      'files'         => 'nullable|array|min:1',
      'files.*.name'  => 'nullable|string',
      'files.*.type'   => 'nullable|file|extensions:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif,c,cpp,java,py,ipynb,js,zip,rar|max:20480',
      'files.*.url'   => 'nullable|string',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation Errors',
        'errors'  => $validator->errors()
      ], 422);
    }

    DB::transaction(function () use ($request) {
      $submission = Submission::create([
        'answer'        => $request->title,
        'support'       => $request->description,
        'assignment_id' => $request->assignment_id,
        'classroom_id'  => $request->classroom_id,
        'section_id'    => $request->section_id,
        'order'         => $request->order,
      ]);

      $type = 'submission';

      if ($request->has('files') && is_array($request->files)) {
        foreach ($request->files as $item) {
          $name = $item['name'] ?? null;
          $file = $item['file'] ?? null;
          $url  = $item['url'] ?? null;

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
            'class_model' => 'App\Models\Submission',
            'class_id'    => $submission->id,
            'type'        => $type,
          ]);
        }
      }
    });

    return response()->json([
      'success' => true,
      'message' => 'Submission created successfully',
    ], 201);
  }

  /**
   * Display the specified resource.
   */
  public function show(string $id)
  {
    $submission = Submission::with(['classroom', 'assignment'])->find($id);

    if (! $submission) {
      return response()->json([
        'success' => false,
        'message' => 'Submission not found'
      ], 404);
    }

    $files = Attachment::where('class_id', $id)
      ->where('class_model', 'App\Models\Submission')
      ->where('type', 'submission')
      ->get();

    return response()->json([
      'success' => true,
      'message' => 'Submission retrieved successfully',
      'data'    => [
        'id'                => $submission->id,
        'title'             => $submission->title,
        'description'       => $submission->description,
        'classroom_id'      => $submission->classroom_id,
        'section_id'        => $submission->section_id,
        'order'             => $submission->order,
        'classroom'         => $submission->classroom,
        'assignment'        => $submission->submission,
        'files'             => $files,
        'created_at'        => $submission->created_at,
        'updated_at'        => $submission->updated_at,
      ]
    ], 200);
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(Request $request, string $id)
  {
    $submission = Submission::find($id);

    if (! $submission) {
      return response()->json([
        'success' => false,
        'message' => 'Submission not found'
      ], 404);
    }

    $validator = Validator::make($request->all(), [
      'answer'        => 'nullable|string',
      'support'       => 'nullable|string',
      'section_id'    => 'nullable|string|exists:sections,id',
      'order'         => 'nullable|numeric',

      'files'         => 'nullable|array|min:1',
      'files.*.name'  => 'nullable|string',
      'files.*.file'  => 'nullable|file|extensions:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif,c,cpp,java,py,ipynb,js,zip,rar|max:20480',
      'files.*.url'   => 'nullable|string',
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

    DB::transaction(function () use ($request, $submission) {
      $submission->update($request->only([
        'answer'      => $request->answer,
        'support'     => $request->support,
        'section_id'  => $request->section_id,
        'order'       => $request->order,
      ]));

      $type = 'submission';

      if ($request->has('files') && is_array($request->files)) {
        foreach ($request->files as $item) {
          $name = $item['name'] ?? null;
          $file = $item['file'] ?? null;
          $url  = $item['url'] ?? null;

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
            'class_model' => 'App\Models\Submission',
            'class_id'    => $submission->id,
            'type'        => $type,
          ]);
        }
      }

      if ($request->has('files_remove_ids') && is_array($request->files_remove_ids)) {

        $attachmentsToRemove = Attachment::whereIn('id', $request->files_remove_ids)
          ->where('class_model', 'App\Models\Submission')
          ->where('type', $type)
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
      'message' => 'Submission updated successfully',
    ], 200);
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy(string $id)
  {
    $submission = Submission::find($id);

    if (! $submission) {
      return response()->json([
        'success' => false,
        'message' => 'Submission not found'
      ], 404);
    }

    $attachmentsToRemove = Attachment::where('class_id', $submission->id)
      ->where('class_model', 'App\Models\Submission')
      ->get();

    foreach ($attachmentsToRemove as $attachment) {
      if ($attachment->file_path && Storage::disk('public')->exists($attachment->file_path)) {
        Storage::disk('public')->delete($attachment->file_path);
      }

      $attachment->delete();
    }

    $submission->delete();

    return response()->json([
      'success' => true,
      'message' => 'Submission deleted successfully'
    ], 200);
  }
}
