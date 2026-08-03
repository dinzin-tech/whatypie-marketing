<?php

namespace App\Controllers;

use Core\Controller;
use Core\Http\Response;
use Core\Http\Request;
use App\Models\User;

class HomeController extends Controller
{
    /**
     * @Route(path="/", methods="GET,post", name="home")
     */
    public function index($request)
    {
        $form = new \Core\Form();
        $form->add('text', 'name', null, ['label' => 'Name', 'required' => true]);

        $form->add('email', 'email', null, ['label' => 'Email', 'required' => true]);

        $form->add('password', 'password', null, ['label' => 'Password', 'required' => true]);

        // $form->add('submit', 'submit', null, ['label' => 'Submit']);
        // if ($request->isPost()) {
            $form->handle($request);
            if ($form->validate($request) && $form->isSubmitted()) {
                // $user = new User();
                // $user->name = $form->get('name');
                // $user->email = $form->get('email');
                // $user->password = $form->get('password');
                // $user->save();
                print_r($form->getFormData());
            }
        // }
        
        // print $form->render();
        // exit;


        return $this->render('home/index.html.twig', [
            'name' => 'John Doe',
            'form' => $form->render()
        ]);
    }

    /**
     * @Route(path="/greet", methods="GET,post", name="greeetings")
     */
    public function greet(Request $request)
    {
        $name = $request->get('name', 'Guest');
        $greeting = "Hello, " . htmlspecialchars($name) . "!";

        return new Response($greeting);
    }
    
    /**
     * @Route(path="/privacy-policy", methods="GET", name="privacy_policy")
     */
    public function privacyPolicy(Request $request)
    {
        return $this->render('home/privacy_policy.html.twig');
    }

    /**
     * @Route(path="/terms", methods="GET", name="terms")
     */
    public function terms(Request $request)
    {
        return $this->render('home/terms.html.twig');
    }

    /**
     * @Route(path="/services", methods="GET", name="services")
     */
    public function services(Request $request)
    {
        return $this->render('home/services.html.twig');
    }

    /**
     * @Route(path="/industries", methods="GET", name="industries")
     */
    public function industries(Request $request)
    {
        return $this->render('home/industries.html.twig');
    }

    /**
     * @Route(path="/case-studies", methods="GET", name="case_studies")
     */
    public function caseStudies(Request $request)
    {
        return $this->render('home/case_studies.html.twig');
    }

    /**
     * @Route(path="/pricing", methods="GET", name="pricing")
     */
    public function pricing(Request $request)
    {
        return $this->render('home/pricing.html.twig');
    }

    /**
     * @Route(path="/about", methods="GET", name="about")
     */
    public function about(Request $request)
    {
        return $this->render('home/about.html.twig');
    }

    /**
     * @Route(path="/industries/real-estate", methods="GET", name="industries.real_estate")
     */
    public function realEstateIndustry(Request $request)
    {
        return $this->render('home/industries/real_estate.html.twig');
    }

    /**
     * @Route(path="/industries/healthcare", methods="GET", name="industries.healthcare")
     */
    public function healthcareIndustry(Request $request)
    {
        return $this->render('home/industries/healthcare.html.twig');
    }

    /**
     * @Route(path="/industries/education", methods="GET", name="industries.education")
     */
    public function educationIndustry(Request $request)
    {
        return $this->render('home/industries/education.html.twig');
    }

    /**
     * @Route(path="/industries/ecommerce", methods="GET", name="industries.ecommerce")
     */
    public function ecommerceIndustry(Request $request)
    {
        return $this->render('home/industries/ecommerce.html.twig');
    }

    /**
     * @Route(path="/industries/travel", methods="GET", name="industries.travel")
     */
    public function travelIndustry(Request $request)
    {
        return $this->render('home/industries/travel.html.twig');
    }

    /**
     * @Route(path="/industries/finance", methods="GET", name="industries.finance")
     */
    public function financeIndustry(Request $request)
    {
        return $this->render('home/industries/finance.html.twig');
    }
}