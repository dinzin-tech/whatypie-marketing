<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\BlogPost;
use App\Services\Mailer;

class BlogGeneratorService
{
    private OpenRouterClient $client;
    private string $logFile;
    private string $alertEmail;

    private const TOPICS = [
        'Why AI Workflow Automation Is the Competitive Edge for SMBs in 2025',
        'How to Automate Your Sales Pipeline with AI Agents',
        'The Real ROI of AI Automation vs. Hiring an Extra Employee',
        'AI Automation for E-Commerce: Cut Costs and Scale Faster',
        'How Law Firms Are Using AI to Automate Document Review and Client Intake',
        'AI in Healthcare Operations: Automating Scheduling, Billing and Follow-Ups',
        'Logistics and Supply Chain Automation: What AI Can Do Today',
        'How to Build an AI-Powered Customer Support System Without a Dev Team',
        'Prompt Engineering for Business: Getting Consistent Results from AI',
        'The 5 Business Processes You Should Automate with AI Right Now',
        'Why Your Competitors Are Already Using AI Automation (And You Should Too)',
        'From Manual to Autonomous: A Step-by-Step AI Transformation Roadmap',
        'AI Strategy Calls: What to Expect and How to Prepare Your Business',
        'How AI Agents Are Replacing Repetitive Back-Office Work',
        'The Hidden Costs of Not Automating Your Business Operations',
        'AI Automation for Marketing: Content, Leads and Campaigns on Autopilot',
        'How to Measure the Success of Your AI Automation Implementation',
        'Small Business, Big AI: Affordable Automation Strategies That Work',
        'Integrating AI Into Your Existing Tech Stack Without Starting From Scratch',
        'The Future of Work: How AI Automation Is Reshaping Every Industry',
    ];

    public function __construct()
    {
        $this->client     = new OpenRouterClient();
        $this->logFile    = BASE_PATH . '/storage/logs/blog_cron.log';
        $this->alertEmail = $_ENV['ALERT_EMAIL'] ?? '';
    }

    public function generate(?string $topic = null): array
    {
        $topic = $topic ?: $this->pickTopic();

        $this->writeLog('INFO', "Starting generation for topic: \"{$topic}\"");

        try {
            $raw = $this->client->chat($this->buildMessages($topic));
            $parsed = $this->parseResponse($raw);
            $post = $this->savePost($parsed);

            $wordCount = str_word_count(strip_tags($post->content));
            $message = "Post published: \"{$post->title}\" (id={$post->id}, slug={$post->slug}, words={$wordCount})";

            $this->writeLog('SUCCESS', $message);
            $this->sendAlert('SUCCESS', $message);

            return ['success' => true, 'post_id' => $post->id, 'title' => $post->title, 'slug' => $post->slug, 'words' => $wordCount];

        } catch (\Throwable $e) {
            $message = $e->getMessage();
            $this->writeLog('FAILURE', $message);
            $this->sendAlert('FAILURE', $message);

            return ['success' => false, 'error' => $message];
        }
    }

    private function buildMessages(string $topic): array
    {
        return $this->buildPublicMessages($topic);
    }

    public function buildPublicMessages(string $topic): array
    {
        $systemPrompt = <<<PROMPT
You are a senior content strategist for WhatyPie, an AI automation agency that helps businesses eliminate manual work, reduce costs, and scale operations using custom AI agents and workflow automation.

Your writing is authoritative, practical, and conversion-focused. Every post subtly positions WhatyPie as the go-to partner for AI transformation and ends with a soft call-to-action encouraging readers to book a free AI Strategy Call.

CRITICAL FORMATTING RULES:
- The "title" must use proper Title Case (capitalize every major word).
- The "excerpt" must be a single sentence under 160 characters.
- The "content" must be full HTML using <h2>, <p>, <ul>, <li>, <strong> tags. Minimum 600 words.
- ALL double quotes inside the "content" HTML string must be escaped as &quot; — never use raw " characters inside the content value.
- Respond ONLY with a raw JSON object. No markdown, no code fences, no explanation before or after.

Exact response structure:
{"title": "...", "excerpt": "...", "content": "..."}
PROMPT;

        return [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user',   'content' => "Write a strategic blog post on this topic: {$topic}"],
        ];
    }

    private function parseResponse(string $raw): array
    {
        return $this->parsePublicResponse($raw);
    }

    public function parsePublicResponse(string $raw): array
    {
        // Extract the first {...} JSON block from anywhere in the response
        if (!preg_match('/\{[\s\S]*\}/u', $raw, $matches)) {
            throw new \RuntimeException("No JSON object found in AI response. Raw: " . substr($raw, 0, 300));
        }

        $data = json_decode($matches[0], true);

        // If json_decode failed (likely due to unescaped quotes in content), extract fields individually
        if (!is_array($data)) {
            $data = $this->extractFieldsFallback($matches[0]);
        }

        if (empty($data['title']) || empty($data['excerpt']) || empty($data['content'])) {
            throw new \RuntimeException("Parsed JSON is missing required fields. Raw: " . substr($raw, 0, 300));
        }

        // Enforce Title Case on the title
        $data['title'] = $this->toTitleCase($data['title']);

        return $data;
    }

    private function extractFieldsFallback(string $json): array
    {
        $data = [];

        // Extract title — value between first "title": " and the next "
        if (preg_match('/"title"\s*:\s*"((?:[^\\"]|\\.)*)"/u', $json, $m)) {
            $data['title'] = stripslashes($m[1]);
        }

        // Extract excerpt
        if (preg_match('/"excerpt"\s*:\s*"((?:[^\\"]|\\.)*)"/u', $json, $m)) {
            $data['excerpt'] = stripslashes($m[1]);
        }

        // Extract content — everything between "content": " and the final "} at end of string
        if (preg_match('/"content"\s*:\s*"([\s\S]*?)"\s*\}\s*$/u', $json, $m)) {
            $data['content'] = stripslashes($m[1]);
        }

        return $data;
    }

    private function toTitleCase(string $title): string
    {
        $minorWords = ['a','an','the','and','but','or','for','nor','on','at','to','by','in','of','up','as','is','vs'];
        $words = explode(' ', strtolower(trim($title)));
        foreach ($words as $i => &$word) {
            if ($i === 0 || !in_array($word, $minorWords, true)) {
                $word = ucfirst($word);
            }
        }
        return implode(' ', $words);
    }

    private function savePost(array $data): BlogPost
    {
        $adminUser = \App\Models\User::findOneBy(['role' => 'admin']);

        if (!$adminUser) {
            throw new \RuntimeException("No admin user found in the database to attribute the post to.");
        }

        $post = new BlogPost();
        $post->user_id  = $adminUser->id;
        $post->title    = $data['title'];
        $post->slug     = BlogPost::generateSlug($data['title']);
        $post->excerpt  = $data['excerpt'];
        $post->content  = $data['content'];
        $post->status   = 'published';
        $post->save();

        return $post;
    }

    public function pickTopic(): string
    {
        $existing = BlogPost::query()->select('title')->get();
        $existingTitles = array_map(fn($r) => strtolower($r['title'] ?? ''), $existing);

        $available = array_filter(self::TOPICS, function (string $topic) use ($existingTitles) {
            return !in_array(strtolower($topic), $existingTitles, true);
        });

        if (empty($available)) {
            // All topics used — reset and start over
            $available = self::TOPICS;
        }

        return $available[array_key_first($available)];
    }

    public function writeLog(string $level, string $message): void
    {
        $line = '[' . date('Y-m-d H:i:s') . '] [' . $level . '] ' . $message . PHP_EOL;
        file_put_contents($this->logFile, $line, FILE_APPEND | LOCK_EX);
    }

    public function sendAlert(string $level, string $message): void
    {
        if (!$this->alertEmail) return;

        $subject = "WhatyPie Blog Cron {$level} " . date('Y-m-d H:i:s');
        $body    = "Blog Auto-Generation Run Report\n\nStatus: {$level}\nTime: " . date('Y-m-d H:i:s') . "\n\nDetail:\n{$message}";

        try {
            Mailer::send($this->alertEmail, $subject, $body);
        } catch (\Throwable $e) {
            $this->writeLog('WARN', "Alert email failed to send: " . $e->getMessage());
        }
    }
}
