<?php

namespace Laravel\Horizon\Http\Controllers;

use Illuminate\Support\Facades\Redis;

class RedisController extends Controller
{
    /**
     * Get all Redis keys.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        // Get keys and map to an array of objects for easier handling in frontend
        $keys = array_map(function ($key) {
            // Redis::keys() often returns full keys including prefix, depending on driver/config
            return ['id' => $key];
        }, Redis::keys('*'));

        return response()->json([
            'keys' => $keys,
        ]);
    }

    /**
     * Get the content of a Redis key.
     *
     * @param  string  $key
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($key)
    {
        $typeRaw = Redis::type($key);
        // Handle both PhpRedis (int or string depending on version) and Predis (Status object)
        $type = is_object($typeRaw) && method_exists($typeRaw, 'getPayload') 
            ? $typeRaw->getPayload() 
            : (string) $typeRaw;

        // PhpRedis returns ints for types. 1=string, 2=set, 3=list, 4=zset, 5=hash, 0=none
        if (is_numeric($type)) {
            $types = [
                0 => 'none',
                1 => 'string',
                2 => 'set',
                3 => 'list',
                4 => 'zset',
                5 => 'hash',
            ];
            $type = $types[$type] ?? 'unknown';
        }

        $value = null;

        switch ($type) {
            case 'string':
                $value = Redis::get($key);
                break;
            case 'list':
                $value = Redis::lrange($key, 0, -1);
                break;
            case 'set':
                $value = Redis::smembers($key);
                break;
            case 'zset':
                $value = Redis::zrange($key, 0, -1);
                break;
            case 'hash':
                $value = Redis::hgetall($key);
                break;
            default:
                $value = Redis::get($key); // fallback
        }

        return response()->json([
            'key' => $key,
            'type' => $type,
            'value' => $value,
        ]);
    }

    /**
     * Delete a Redis key.
     *
     * @param  string  $key
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($key)
    {
        Redis::del($key);

        return response()->json([], 204);
    }
}
