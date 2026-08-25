<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Http\Request;
use Core\Http\Response;
use App\Services\Mailer;
use App\Services\EmailTemplate;
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

        $htmlBody = EmailTemplate::contactConfirmation($name, $message);
        Mailer::send($email, 'We received your message — WhatyPie', $htmlBody);

        return new Response(json_encode(['success' => true]), 200, ['Content-Type' => 'application/json']);
    }

    /**
     * @Route(path="/booking/submit", methods="POST", name="booking.submit")
     */
    public function submitBooking(Request $request): Response
    {
        $fullName     = trim($request->post('full_name', ''));
        $workEmail    = trim($request->post('work_email', ''));
        $phoneNumber  = trim($request->post('phone_number', ''));
        $companyName  = trim($request->post('company_name', ''));
        $website      = trim($request->post('website', ''));
        $whatYouNeed  = trim($request->post('what_you_need', ''));
        $selectedPlan = trim($request->post('selected_plan', ''));

        if (!$fullName || !$phoneNumber || !$workEmail) {
            return new Response(json_encode(['success' => false, 'message' => 'Full Name, Work Email, and Phone Number are required.']), 422, ['Content-Type' => 'application/json']);
        }

        $booking = new StrategyBooking();
        $booking->full_name    = $fullName;
        $booking->work_email   = $workEmail;
        $booking->phone_number = $phoneNumber;
        $booking->company_name = $companyName;
        
        $notes = [];
        if ($website)     $notes[] = "Website: {$website}";
        if ($whatYouNeed) $notes[] = "Needs: {$whatYouNeed}";
        if ($selectedPlan) $notes[] = "Plan: {$selectedPlan}";
        if (!empty($notes)) {
            $booking->admin_notes = implode(" | ", $notes);
        }
        
        $booking->save();

        if ($workEmail) {
            $detailsArray = [
                'Full Name'     => $fullName,
                'Work Email'    => $workEmail,
                'Phone Number'  => $phoneNumber,
                'Company Name'  => $companyName,
                'Website'       => $website,
                'Requirements'  => $whatYouNeed,
                'Selected Plan' => $selectedPlan,
            ];

            $htmlBody = EmailTemplate::bookingConfirmation($fullName, $detailsArray);

            Mailer::send(
                $workEmail,
                'AI Strategy Call Requested — WhatyPie',
                $htmlBody
            );
        }

        return new Response(json_encode(['success' => true]), 200, ['Content-Type' => 'application/json']);
    }
}
