<?php

namespace Klsandbox\SiteModel;

trait SiteExtensions
{
    public static function forSite()
    {
        return self::where('site_id', '=', Site::id());
    }
}
