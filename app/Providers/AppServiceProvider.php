<?php
namespace App\Providers;
use App\Contracts\AiContentGenerator;
use App\Contracts\PublishingGateway;
use App\Infrastructure\Ai\FakeAiContentGenerator;
use App\Infrastructure\Publishing\FakePublishingGateway;
use Illuminate\Support\ServiceProvider;
class AppServiceProvider extends ServiceProvider {
 public function register():void {$this->app->bind(AiContentGenerator::class,FakeAiContentGenerator::class);
        $this->app->bind(PublishingGateway::class,FakePublishingGateway::class);}
 public function boot():void{}
}