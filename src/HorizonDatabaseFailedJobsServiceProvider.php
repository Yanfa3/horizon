<?php

namespace Laravel\Horizon;

use Illuminate\Support\ServiceProvider;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Repositories\DatabaseFailedJobRepository;

class HorizonDatabaseFailedJobsServiceProvider extends ServiceProvider
{
  public function register(): void
  {
    // Only override if configured to use database for failed jobs
    if (config('horizon.failed_jobs.driver') === 'database') {
      $this->app->singleton(JobRepository::class, function ($app) {
        return new DatabaseFailedJobRepository(
          $app->make('redis')
        );
      });
    }
  }
}
