<?php

namespace Klsandbox\SiteModel;

use Illuminate\Database\Eloquent\Model;
use App;
use Config;

/**
 * App\Models\Site
 *
 * @property integer $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $key
 * @property string $name
 * @property string $description
 * @property string $host
 * @property string $status
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Site whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Site whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Site whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Site whereKey($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Site whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Site whereDescription($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Site whereHost($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Site whereStatus($value)
 * @mixin \Eloquent
 */
class Site extends Model {

    //
    protected $fillable = ['key', 'name', 'description', 'status', 'host'];
    private static $siteId;
    private static $siteKey;

    public static function setSite($site) {
        self::$siteId = $site->id;
        self::$siteKey = $site->key;
    }

    public static function setSiteByHost($host) {
        $site = Site::where(['host' => $host])->first();
        if ($site) {
            self::setSite($site);
        }
    }

    public static function DevSite() {
        $site = Site::where(['Status' => 'Dev'])->first();
        return $site;
    }

    public static function ProdSite() {
        $site = Site::where(['Status' => 'Production'])->first();
        return $site;
    }

    public static function id() {
        if (self::$siteId) {
            return self::$siteId;
        }

        return Config::get('site.fallback_id');
    }

    public static function key() {
        if (self::$siteKey) {
            return self::$siteKey;
        }

        return Config::get('site.fallback_key');
    }

    public static function protect($item, $label = "Item") {
        if (!$item) {
            App::abort(404, "$label not found.");
        }

        if ($item->site_id != Site::id()) {
            App::abort(403, 'Unauthorized action.');
        }
    }

}
