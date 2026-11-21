<?php
use App\Http\Controllers\WorkflowController;
use Illuminate\Support\Facades\Route;
Route::get('/',fn()=>response()->json(['name'=>'KI Social','mode'=>'prototype','ai'=>'simulated','publishing'=>'simulated']));
Route::post('/api/posts',[WorkflowController::class,'store']);
Route::post('/api/posts/{post}/edit',[WorkflowController::class,'edit']);
Route::post('/api/posts/{post}/submit',[WorkflowController::class,'submit']);
Route::post('/api/posts/{post}/approve',[WorkflowController::class,'approve']);
Route::post('/api/posts/{post}/handoff',[WorkflowController::class,'handoff']);