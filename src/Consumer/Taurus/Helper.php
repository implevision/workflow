<?php

namespace Taurus\Workflow\Consumer\Taurus;

use Carbon\Carbon;

class Helper
{
    public static function getHoldingCompanyDetail()
    {
        $holdingCompanyDetail = \DB::table('tb_holdingcompanies')->first();

        return ['logo' => $holdingCompanyDetail->logo_url];
    }

    public static function formatDate($dateToFormat)
    {
        return Carbon::parse($dateToFormat)->format('m/d/Y');
    }
}
