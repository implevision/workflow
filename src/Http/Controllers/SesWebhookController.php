<?php

namespace Taurus\Workflow\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Taurus\Workflow\Models\EmailDeliveryEvent;

class SesWebhookController extends Controller
{
    private const EVENT_STATUS_MAP = [
        'delivery'          => EmailDeliveryEvent::STATUS_COMPLETED,
        'hard_bounce'       => EmailDeliveryEvent::STATUS_ERROR,
        'complaint'         => EmailDeliveryEvent::STATUS_ERROR,
        'reject'            => EmailDeliveryEvent::STATUS_ERROR,
        'rendering_failure' => EmailDeliveryEvent::STATUS_ERROR,
        'send'              => EmailDeliveryEvent::STATUS_IN_PROGRESS,
        'open'              => EmailDeliveryEvent::STATUS_IN_PROGRESS,
        'click'             => EmailDeliveryEvent::STATUS_IN_PROGRESS,
        'delivery_delay'    => EmailDeliveryEvent::STATUS_IN_PROGRESS,
        'subscription'      => EmailDeliveryEvent::STATUS_IN_PROGRESS,
    ];

    public function handle(Request $request)
    {
        $snsMessage = json_decode($request->getContent(), true);

        if (! $snsMessage) {
            return response('Invalid payload', 400);
        }

        if ($request->header('x-amz-sns-message-type') === 'SubscriptionConfirmation') {
            file_get_contents($snsMessage['SubscribeURL']);
            return response('OK', 200);
        }

        $event = json_decode($snsMessage['Message'] ?? '{}', true);

        if (empty($event['mail']['messageId'])) {
            return response('OK', 200);
        }

        $messageId = $event['mail']['messageId'];
        $eventType = strtolower($event['eventType'] ?? 'unknown');
        $status    = self::EVENT_STATUS_MAP[$eventType] ?? EmailDeliveryEvent::STATUS_IN_PROGRESS;

        $workflowLog = DB::table(getTablePrefix().'_workflow_logs')
            ->where('action_track_id', $messageId)
            ->first();

        // Har event ka naya record insert hoga
        $eventTimestamp = ! empty($event['mail']['timestamp'])
            ? \Carbon\Carbon::parse($event['mail']['timestamp'])
            : now();

        EmailDeliveryEvent::create([
            'message_id'      => $messageId,
            'event_type'      => strtoupper($eventType),
            'workflow_id'     => $workflowLog->workflow_id ?? 0,
            'event_timestamp' => $eventTimestamp,
            'payload'         => $event,
        ]);

        return response('OK', 200);
    }
}
