<?php

namespace App\Services;

use App\Mail\DeliveryTrackingUpdateMail;
use App\Models\Delivery;
use App\Models\DeliveryTrackingNotification;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

class DeliveryTrackingEmailService
{
    public function send(Delivery $delivery, User $actor): DeliveryTrackingNotification
    {
        if ($delivery->company_id !== $actor->company_id) {
            throw new AuthorizationException('The delivery must belong to the current company.');
        }

        $customer = $delivery->customer()->firstOrFail();

        if (! $customer->email) {
            throw ValidationException::withMessages(['email' => 'Add a customer email address before sending a tracking update.']);
        }

        if (! $customer->tracking_email_consent_at || $customer->tracking_email_opted_out_at) {
            throw ValidationException::withMessages(['email' => 'Record the customer’s consent for transactional tracking emails before sending.']);
        }

        [$link, $token] = app(DeliveryTrackingService::class)->createOrRotate($delivery, $actor);
        $subject = 'Delivery update: '.$delivery->reference;
        $notification = DeliveryTrackingNotification::query()->create([
            'company_id' => $delivery->company_id,
            'delivery_id' => $delivery->getKey(),
            'delivery_tracking_link_id' => $link->getKey(),
            'created_by_user_id' => $actor->getKey(),
            'channel' => DeliveryTrackingNotification::CHANNEL_EMAIL,
            'recipient_email' => $customer->email,
            'reply_to_email' => $delivery->company()->value('contact_email'),
            'subject' => $subject,
            'status' => DeliveryTrackingNotification::STATUS_PREVIEWED,
        ]);

        if (config('delivery-email.mode') !== 'live') {
            return $notification;
        }

        if (! config('delivery-email.from_address')) {
            $notification->update(['status' => DeliveryTrackingNotification::STATUS_FAILED, 'failure_reason' => 'A verified platform sender is not configured.']);

            return $notification;
        }

        try {
            Mail::to($customer->email)->send(new DeliveryTrackingUpdateMail($delivery, route('tracking.show', $token), $notification->reply_to_email));
            $notification->update(['status' => DeliveryTrackingNotification::STATUS_SENT, 'sent_at' => now()]);
        } catch (Throwable $exception) {
            report($exception);
            $notification->update(['status' => DeliveryTrackingNotification::STATUS_FAILED, 'failure_reason' => 'Email provider did not accept the message.']);
        }

        return $notification->refresh();
    }
}
