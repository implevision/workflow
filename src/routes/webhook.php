<?php

use Illuminate\Support\Facades\Route;
use Taurus\Workflow\Http\Controllers\SesWebhookController;

Route::post('/workflow/webhook/ses', [SesWebhookController::class, 'handle']);
