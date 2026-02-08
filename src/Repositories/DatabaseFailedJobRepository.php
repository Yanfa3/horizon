<?php

namespace Laravel\Horizon\Repositories;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Support\Facades\DB;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\JobPayload;

class DatabaseFailedJobRepository extends RedisJobRepository
{
  /**
   * Get the total count of failed jobs from database.
   */
  public function totalFailed()
  {
    return DB::table('failed_jobs')->count();
  }

  /**
   * Get the count of failed jobs.
   */
  public function countFailed()
  {
    return $this->totalFailed();
  }

  /**
   * Get the count of the recently failed jobs.
   */
  public function countRecentlyFailed()
  {
    return DB::table('failed_jobs')
      ->where('failed_at', '>=', now()->subDays(7))
      ->count();
  }

  /**
   * Get a chunk of failed jobs from database.
   */
  public function getFailed($afterIndex = null)
  {
    $query = DB::table('failed_jobs')
      ->orderByDesc('failed_at')
      ->orderByDesc('id')
      ->limit(50);

    if ($afterIndex !== null && $afterIndex != -1) {
      $query->where('id', '<', $afterIndex);
    }

    $jobs = $query->get();

    return $jobs->map(function ($job) {
      return $this->mapFailedJob($job);
    });
  }

  /**
   * Retrieve the jobs with the given IDs.
   */
  public function getJobs(array $ids, $indexFrom = 0)
  {
    // First, try to get them from Redis (for recent/pending/completed jobs)
    $redisJobs = parent::getJobs($ids, $indexFrom);

    // Find which IDs were missing from Redis
    $missingIds = array_diff($ids, $redisJobs->pluck('id')->all());

    if (empty($missingIds)) {
      return $redisJobs;
    }

    // Fetch missing jobs from the database (failed jobs)
    $dbJobs = DB::table('failed_jobs')
      ->whereIn('uuid', $missingIds)
      ->orWhereIn('id', $missingIds)
      ->get();

    $mappedDbJobs = $dbJobs->map(function ($job) use (&$indexFrom) {
      $mapped = $this->mapFailedJob($job);
      $mapped->index = $indexFrom++;
      return $mapped;
    });

    return $redisJobs->concat($mappedDbJobs)->sortBy('index')->values();
  }

  /**
   * Map a database failed job to a Horizon job object.
   */
  protected function mapFailedJob($job)
  {
    $payload = json_decode($job->payload, true);

    return (object) [
      'id' => $job->uuid ?? $job->id,
      'connection' => $job->connection,
      'queue' => $job->queue,
      'name' => $payload['displayName'] ?? 'Unknown Job',
      'status' => 'failed',
      'payload' => $job->payload,
      'exception' => $job->exception,
      'failed_at' => CarbonImmutable::parse($job->failed_at)->getTimestamp(),
      'retried_by' => null,
      'index' => $job->id, // Use ID as index for pagination
    ];
  }

  /**
   * Find a failed job by ID from database.
   */
  public function findFailed($id)
  {
    $job = DB::table('failed_jobs')
      ->where('uuid', $id)
      ->orWhere('id', $id)
      ->first();

    if (!$job) {
      return null;
    }

    return $this->mapFailedJob($job);
  }

  /**
   * Delete a failed job by ID from database.
   */
  public function deleteFailed($id)
  {
    return DB::table('failed_jobs')
      ->where('uuid', $id)
      ->orWhere('id', $id)
      ->delete();
  }

  /**
   * No-op: Failed jobs are stored by Laravel's default handler.
   * We don't need to write to Redis anymore.
   */
  public function failed($exception, $connection, $queue, JobPayload $payload)
  {
    // Do nothing - Laravel's database failed job provider handles this
    // Just update the job status in Redis for the recent jobs view
    $this->connection()->pipeline(function ($pipe) use ($payload) {
      $this->removeJobReference($pipe, 'pending_jobs', $payload);
      $this->removeJobReference($pipe, 'completed_jobs', $payload);

      $pipe->hmset($payload->id(), [
        'status' => 'failed',
        'failed_at' => str_replace(',', '.', microtime(true)),
      ]);
    });
  }
}
