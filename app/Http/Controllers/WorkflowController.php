<?php

namespace App\Http\Controllers;

use App\Models\SocialPost;
use App\Services\ApprovalService;
use App\Services\PublishingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class WorkflowController
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'idea_id'=>'required|string|max:40',
            'title'=>'required|string|max:180',
            'body'=>'required|string',
            'media_reference'=>'required|string|max:255',
            'channels'=>'required|array|min:1',
            'channels.*'=>'in:instagram,facebook,tiktok,linkedin',
            'scheduled_at'=>'required|date',
            'assigned_person'=>'required|in:A,B',
        ]);

        return response()->json(SocialPost::create($data + ['status'=>'draft','version'=>1]), 201);
    }

    public function edit(Request $request, SocialPost $post): JsonResponse
    {
        $data = $request->validate([
            'title'=>'sometimes|required|string|max:180',
            'body'=>'sometimes|required|string',
            'media_reference'=>'sometimes|required|string|max:255',
            'channels'=>'sometimes|required|array|min:1',
            'channels.*'=>'in:instagram,facebook,tiktok,linkedin',
            'scheduled_at'=>'sometimes|required|date',
        ]);

        DB::transaction(function () use ($post, $data): void {
            $locked = SocialPost::query()->lockForUpdate()->findOrFail($post->id);
            $locked->fill($data);
            $locked->version++;
            $locked->status = 'draft';
            $locked->save();
        });

        return response()->json($post->refresh());
    }

    public function submit(SocialPost $post, ApprovalService $service): JsonResponse
    {
        try { return response()->json($service->submit($post)); }
        catch (RuntimeException $exception) { return response()->json(['error'=>$exception->getMessage()], 422); }
    }

    public function approve(Request $request, SocialPost $post, ApprovalService $service): JsonResponse
    {
        $data = $request->validate(['reviewer'=>'required|string|max:100']);
        try { return response()->json($service->approve($post, $data['reviewer'])); }
        catch (RuntimeException $exception) { return response()->json(['error'=>$exception->getMessage()], 422); }
    }

    public function handoff(SocialPost $post, PublishingService $service): JsonResponse
    {
        try { return response()->json($service->handoff($post)); }
        catch (RuntimeException $exception) { return response()->json(['error'=>$exception->getMessage()], 422); }
    }
}
