<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Http\Request;
use Core\Http\Response;
use App\Services\Mailer;
use App\Models\ContactSubmission;
use App\Models\StrategyBooking;

class ContactController extends Controller
{
    /**
     * @Route(path="/contact/submit", methods="POST", name="contact.submit")
     */
    public function submitContact(Request $request): Response
    {
        $name    = trim($request->post('name', ''));
        $email   = trim($request->post('email', ''));
        $subject = trim($request->post('subject', ''));
        $message = trim($request->post('message', ''));

        if (!$name || !$email || !$message) {
            return new Response(json_encode(['success' => false, 'message' => 'Name, email and message are required.']), 422, ['Content-Type' => 'application/json']);
        }

        $submission = new ContactSubmission();
        $submission->name    = $name;
        $submission->email   = $email;
        $submission->subject = $subject ?: null;
        $submission->message = $message;
        $submission->save();

        Mailer::send(
            $email,
            'We received your message',
            "Hi {$name},\n\nThank you for reaching out. We'll get back to you shortly.\n\nYour message:\n{$message}"
        );

        return new Response(json_encode(['success' => true]), 200, ['Content-Type' => 'application/json']);
    }

    /**
     * @Route(path="/booking/submit", methods="POST", name="booking.submit")
     */
    public function submitBooking(Request $request): Response
    {
        $fullName    = trim($request->post('full_name', ''));
        $workEmail   = trim($request->post('work_email', ''));
        $phoneNumber = trim($request->post('phone_number', ''));
        $companyName = trim($request->post('company_name', ''));
        $selectedPlan = trim($request->post('selected_plan', ''));

        if (!$fullName || !$phoneNumber) {
            return new Response(json_encode(['success' => false, 'message' => 'Full Name and Phone Number are required.']), 422, ['Content-Type' => 'application/json']);
        }

        $booking = new StrategyBooking();
        $booking->full_name    = $fullName;
        $booking->work_email   = $workEmail;
        $booking->phone_number = $phoneNumber;
        $booking->company_name = $companyName;
        if ($selectedPlan) {
            $booking->admin_notes = "Interested Plan: " . $selectedPlan;
        }
        $booking->save();

        if ($workEmail) {
            $planDetail = $selectedPlan ? "\nSelected Plan: {$selectedPlan}" : "";
            Mailer::send(
                $workEmail,
                'AI Strategy Call Requested',
                "Hi {$fullName},\n\nYour AI Strategy Call request has been received. Our lead AI solutions architect will contact you within 2 hours to confirm your booking.\n\nCompany: {$companyName}\nPhone: {$phoneNumber}{$planDetail}"
            );
        }

        return new Response(json_encode(['success' => true]), 200, ['Content-Type' => 'application/json']);
    }
}
