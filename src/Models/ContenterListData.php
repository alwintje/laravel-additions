<?php

namespace Kroesen\LaravelAdditions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Kroesen\LaravelAdditions\Middleware\EncryptCookies;

/**
 * @property-read int $id
 * @property int $user_id
 * @property string $cookie
 * @property string $key
 * @property string $list_data
 * @property int $requests
 * @property int $updates
 * @property Carbon $last_request
 */
class ContenterListData extends Model
{

    protected $fillable = [
        'id',
        'user_id',
        'cookie',
        'key',
        'list_data',
        'requests',
        'updates',
        'last_request',
    ];

    protected $casts = [
        'last_request' => 'datetime',
    ];

    protected static ?string $contenterKeyCache = null;
    public static function getContenterKey(): string
    {
        if(static::$contenterKeyCache !== null) {
            return static::$contenterKeyCache;
        }
        $cookieKey = config('laravel_additions.cookie_key');
        $contenterKey = Cookie::get($cookieKey, '');
        if(!empty($contenterKey)){
            if(str_starts_with($contenterKey, 'v1:')){
                return static::$contenterKeyCache = $contenterKey;
            }

            try{
                $contenterKey = app(EncryptCookies::class)->decryptEncryptedCookie($cookieKey, $contenterKey);
            }catch (\Exception $exception){}
            if(!empty($contenterKey) && str_starts_with($contenterKey, 'v1:')){
                return static::$contenterKeyCache = $contenterKey;
            }
        }

        $contenterKey = 'v1:'.Str::random(64);
        // Set key for 5 years
        Cookie::queue(
            $cookieKey, // name
            $contenterKey, // value
            60*24*365*5, // minutes (5 years)
            null, // path
            null, // domain
            null, // secure
            true, // httpOnly
            true, // raw
            null  // sameSite
        );

        return static::$contenterKeyCache = $contenterKey;
    }

    protected static array $contenterCache = [];
    public static function getContenter(string $key): ?static
    {
        $contenterKey = self::getContenterKey();
        $cacheKey = $contenterKey . '|' . $key;

        if(array_key_exists($cacheKey, static::$contenterCache)){
            return static::$contenterCache[$cacheKey];
        }

        return static::$contenterCache[$cacheKey] = ContenterListData::query()
            ->where('cookie', $contenterKey)
            ->where('key', $key)
            ->first()
        ;
    }

    public static function getListData(string $key): array
    {
        $contenter = static::getContenter($key);
        if(null === $contenter) {

            // Get old cookie data
            if(Cookie::has($key)) {

                $cookie = Cookie::get($key, '{}');
                try{
                    try {
                        return json_decode($cookie, true);
                    }catch (\Throwable){
                        $data = app(EncryptCookies::class)->decryptEncryptedCookie($key, $cookie);
                        return json_decode($data, true);
                    }
                }catch (\Throwable){
                    return [];
                }
            }

            return [];
        }
        if ($contenter->last_request === null ||
            $contenter->last_request->lt(now()->subMinutes(30))
        ) {
            $contenter->requests = $contenter->requests + 1;
            $contenter->last_request = now();
            $contenter->save();
        }

        return json_decode($contenter->list_data, true);
    }

    public static function saveListData(string $key, array $data): void
    {
        $contenter = static::getContenter($key);
        if(null === $contenter) {
            $contenterKey = self::getContenterKey();
            $contenter = static::$contenterCache[$contenterKey . '|' . $key] = new static([
                'cookie' => $contenterKey,
                'key' => $key,
                'requests' => 0,
                'updates' => 0,
            ]);
        }else{
            $contenter->updates = $contenter->updates + 1;
        }

        if ($contenter->user_id === null && auth()->check()) {
            $contenter->user_id = auth()->id();
        }

        $contenter->list_data = json_encode($data);
        $contenter->save();

    }

}
