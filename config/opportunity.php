<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Scheduled collection
    |--------------------------------------------------------------------------
    |
    | cPanel cron can call `php artisan opportunities:collect` directly.
    | When this flag is true, `php artisan schedule:run` also runs that
    | command once a day. It stays off until collection is ready.
    |
    */

    'schedule_collection' => (bool) env('OPPORTUNITY_SCHEDULE_COLLECTION', false),

];
