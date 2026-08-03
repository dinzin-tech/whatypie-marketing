<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Http\Request;
use Core\Http\Response;
use App\Services\BlogGeneratorService;

class CronController extends Controller
{
    /**
     * @Route(path="/cron/generate-blog", methods="GET", name="cron.generate_blog")
     */
    public function generateBlog(Request $request): Response
    {
        $secret = $request->get('secret', '');

        if (!$secret || $secret !== ($_ENV['CRON_SECRET'] ?? '')) {
            return new Response(json_encode(['error' => 'Forbidden.']), 403, ['Content-Type' => 'application/json']);
        }

        $topic   = $request->get('topic') ?: null;
        $service = new BlogGeneratorService();
        $result  = $service->generate($topic);

        $status = $result['success'] ? 200 : 500;

        return new Response(json_encode($result), $status, ['Content-Type' => 'application/json']);
    }
}
