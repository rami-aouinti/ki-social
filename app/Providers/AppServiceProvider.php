<?php
namespace App\Providers;
use App\Contracts\AiContentGenerator;
use App\Infrastructure\Ai\FakeAiContentGenerator;
use Illuminate\Support\ServiceProvider;
class AppServiceProvider extends ServiceProvider {
 public function register():void {$this->app->bind(AiContentGenerator::class,FakeAiContentGenerator::class);}
 public function boot():void{}
}