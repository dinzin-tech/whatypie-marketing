<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Session;
use App\Services\Mailer;
use App\Models\BlogPost;
use App\Models\ContactSubmission;
use App\Models\StrategyBooking;
use App\Services\OpenRouterClient;

class AdminController extends Controller
{
    private const PER_PAGE = 15;
    private const MAILS_PER_PAGE = 20;

    private function requireAuth(): ?Response
    {
        if (!Session::get('admin_id')) {
            return $this->redirect('/admin/login');
        }
        return null;
    }

    private function adminData(): array
    {
        return [
            'admin_username' => Session::get('admin_username'),
            'admin_role'     => Session::get('admin_role'),
        ];
    }

    private function page(Request $request): int
    {
        return max(1, (int) $request->get('page', 1));
    }

    // ─── Root redirect ────────────────────────────────────────────────────────

    /**
     * @Route(path="/admin", methods="GET", name="admin.root")
     */
    public function adminRoot(Request $request): Response
    {
        if ($redirect = $this->requireAuth()) return $redirect;
        return $this->redirect('/admin/blogs');
    }

    // ─── Blogs tab ────────────────────────────────────────────────────────────

    /**
     * @Route(path="/admin/blogs", methods="GET", name="admin.blogs")
     */
    public function adminBlogs(Request $request): Response
    {
        if ($redirect = $this->requireAuth()) return $redirect;

        $page   = $this->page($request);
        $total  = BlogPost::count();
        $posts  = BlogPost::query()
            ->orderBy('created_at', 'DESC')
            ->limit(self::PER_PAGE, ($page - 1) * self::PER_PAGE)
            ->get();

        return $this->render('admin/dashboard.html.twig', array_merge($this->adminData(), [
            'tab'        => 'blogs',
            'posts'      => $posts,
            'pagination' => $this->paginate($total, $page, self::PER_PAGE, '/admin/blogs'),
        ]));
    }

    // ─── Contacts tab ─────────────────────────────────────────────────────────

    /**
     * @Route(path="/admin/contacts", methods="GET", name="admin.contacts")
     */
    public function adminContacts(Request $request): Response
    {
        if ($redirect = $this->requireAuth()) return $redirect;

        $page     = $this->page($request);
        $total    = ContactSubmission::count();
        $contacts = ContactSubmission::query()
            ->orderBy('created_at', 'DESC')
            ->limit(self::PER_PAGE, ($page - 1) * self::PER_PAGE)
            ->get();

        return $this->render('admin/dashboard.html.twig', array_merge($this->adminData(), [
            'tab'        => 'contacts',
            'contacts'   => $contacts,
            'pagination' => $this->paginate($total, $page, self::PER_PAGE, '/admin/contacts'),
        ]));
    }

    // ─── Bookings tab ─────────────────────────────────────────────────────────

    /**
     * @Route(path="/admin/bookings", methods="GET", name="admin.bookings")
     */
    public function adminBookings(Request $request): Response
    {
        if ($redirect = $this->requireAuth()) return $redirect;

        $page     = $this->page($request);
        $total    = StrategyBooking::count();
        $bookings = StrategyBooking::query()
            ->orderBy('created_at', 'DESC')
            ->limit(self::PER_PAGE, ($page - 1) * self::PER_PAGE)
            ->get();

        return $this->render('admin/dashboard.html.twig', array_merge($this->adminData(), [
            'tab'        => 'bookings',
            'bookings'   => $bookings,
            'pagination' => $this->paginate($total, $page, self::PER_PAGE, '/admin/bookings'),
        ]));
    }

    // ─── Logs tab ─────────────────────────────────────────────────────────────

    /**
     * @Route(path="/admin/logs", methods="GET", name="admin.logs")
     */
    public function adminLogs(Request $request): Response
    {
        if ($redirect = $this->requireAuth()) return $redirect;

        return $this->render('admin/dashboard.html.twig', array_merge($this->adminData(), [
            'tab'  => 'logs',
            'logs' => $this->readLogs(),
        ]));
    }

    // ─── Migrations tab ───────────────────────────────────────────────────────

    /**
     * @Route(path="/admin/migrations", methods="GET", name="admin.migrations")
     */
    public function adminMigrations(Request $request): Response
    {
        if ($redirect = $this->requireAuth()) return $redirect;

        return $this->render('admin/dashboard.html.twig', array_merge($this->adminData(), [
            'tab'        => 'migrations',
            'migrations' => $this->listMigrations(),
        ]));
    }

    /**
     * @Route(path="/admin/migration/run", methods="POST", name="admin.migration.run")
     */
    public function migrationRun(Request $request): Response
    {
        if (!Session::get('admin_id')) {
            return new Response(json_encode(['success' => false, 'error' => 'Unauthorized.']), 401, ['Content-Type' => 'application/json']);
        }

        $file = basename(trim($request->post('file', '')));
        if (!$file || !str_ends_with($file, '.php')) {
            return new Response(json_encode(['success' => false, 'error' => 'Invalid file.']), 422, ['Content-Type' => 'application/json']);
        }

        $path = BASE_PATH . '/migrations/' . $file;
        if (!file_exists($path)) {
            return new Response(json_encode(['success' => false, 'error' => 'Migration file not found.']), 404, ['Content-Type' => 'application/json']);
        }

        try {
            $this->ensureMigrationsTable();
            $executed = $this->getExecutedMigrations();

            if (in_array($file, $executed)) {
                return new Response(json_encode(['success' => false, 'error' => 'Already ran.']), 409, ['Content-Type' => 'application/json']);
            }

            $class = $this->loadMigrationClass($path, $file);
            \Core\Database::getInstance()->query('SET FOREIGN_KEY_CHECKS = 0');
            (new $class())->up();
            \Core\Database::getInstance()->query('SET FOREIGN_KEY_CHECKS = 1');
            $this->recordMigration($file);

            return new Response(json_encode(['success' => true, 'file' => $file]), 200, ['Content-Type' => 'application/json']);
        } catch (\Throwable $e) {
            return new Response(json_encode(['success' => false, 'error' => $e->getMessage()]), 500, ['Content-Type' => 'application/json']);
        }
    }

    /**
     * @Route(path="/admin/migration/rollback", methods="POST", name="admin.migration.rollback")
     */
    public function migrationRollback(Request $request): Response
    {
        if (!Session::get('admin_id')) {
            return new Response(json_encode(['success' => false, 'error' => 'Unauthorized.']), 401, ['Content-Type' => 'application/json']);
        }

        $file = basename(trim($request->post('file', '')));
        if (!$file || !str_ends_with($file, '.php')) {
            return new Response(json_encode(['success' => false, 'error' => 'Invalid file.']), 422, ['Content-Type' => 'application/json']);
        }

        $path = BASE_PATH . '/migrations/' . $file;
        if (!file_exists($path)) {
            return new Response(json_encode(['success' => false, 'error' => 'Migration file not found.']), 404, ['Content-Type' => 'application/json']);
        }

        try {
            $this->ensureMigrationsTable();
            $executed = $this->getExecutedMigrations();

            if (!in_array($file, $executed)) {
                return new Response(json_encode(['success' => false, 'error' => 'Not yet run.']), 409, ['Content-Type' => 'application/json']);
            }

            $class = $this->loadMigrationClass($path, $file);
            \Core\Database::getInstance()->query('SET FOREIGN_KEY_CHECKS = 0');
            (new $class())->down();
            \Core\Database::getInstance()->query('SET FOREIGN_KEY_CHECKS = 1');
            $this->removeMigration($file);

            return new Response(json_encode(['success' => true, 'file' => $file]), 200, ['Content-Type' => 'application/json']);
        } catch (\Throwable $e) {
            return new Response(json_encode(['success' => false, 'error' => $e->getMessage()]), 500, ['Content-Type' => 'application/json']);
        }
    }

    // ─── Crons tab ────────────────────────────────────────────────────────────

    /**
     * @Route(path="/admin/crons", methods="GET", name="admin.crons")
     */
    public function adminCrons(Request $request): Response
    {
        if ($redirect = $this->requireAuth()) return $redirect;

        return $this->render('admin/dashboard.html.twig', array_merge($this->adminData(), [
            'tab'   => 'crons',
            'crons' => $this->scanCrons(),
        ]));
    }

    /**
     * @Route(path="/admin/cron/run", methods="POST", name="admin.cron.run")
     */
    public function cronRun(Request $request): Response
    {
        if (!Session::get('admin_id')) {
            return new Response(json_encode(['success' => false, 'error' => 'Unauthorized.']), 401, ['Content-Type' => 'application/json']);
        }

        $path = trim($request->post('path', ''));

        $handlers = [
            '/cron/generate-blog' => function () {
                $service = new \App\Services\BlogGeneratorService();
                return $service->generate();
            },
        ];

        if (!isset($handlers[$path])) {
            return new Response(json_encode(['success' => false, 'error' => 'Unknown cron path.']), 422, ['Content-Type' => 'application/json']);
        }

        try {
            $result = ($handlers[$path])();
            return new Response(json_encode($result), 200, ['Content-Type' => 'application/json']);
        } catch (\Throwable $e) {
            return new Response(json_encode(['success' => false, 'error' => $e->getMessage()]), 500, ['Content-Type' => 'application/json']);
        }
    }

    /**
     * @Route(path="/admin/cron/logs", methods="GET", name="admin.cron.logs")
     */
    public function cronLogs(Request $request): Response
    {
        if (!Session::get('admin_id')) {
            return new Response(json_encode(['error' => 'Unauthorized.']), 401, ['Content-Type' => 'application/json']);
        }

        $lines = $this->readCronLog(100);

        return new Response(json_encode(['lines' => $lines]), 200, ['Content-Type' => 'application/json']);
    }

    // ─── Mails tab ────────────────────────────────────────────────────────────

    /**
     * @Route(path="/admin/mails", methods="GET", name="admin.mails")
     */
    public function adminMails(Request $request): Response
    {
        if ($redirect = $this->requireAuth()) return $redirect;

        $page      = $this->page($request);
        $allMails  = $this->readMails();
        $total     = count($allMails);
        $offset    = ($page - 1) * self::MAILS_PER_PAGE;
        $mails     = array_slice($allMails, $offset, self::MAILS_PER_PAGE);

        // Attach index (global, not page-relative) so view/delete don't use filenames in URL
        foreach ($mails as &$mail) {
            $mail['index'] = array_search($mail['filename'], array_column($allMails, 'filename'));
        }
        unset($mail);

        return $this->render('admin/dashboard.html.twig', array_merge($this->adminData(), [
            'tab'        => 'mails',
            'mails'      => $mails,
            'pagination' => $this->paginate($total, $page, self::MAILS_PER_PAGE, '/admin/mails'),
        ]));
    }

    // ─── Blog CRUD ────────────────────────────────────────────────────────────

    /**
     * @Route(path="/admin/blog/create", methods="GET,POST", name="admin.blog.create")
     */
    public function blogCreate(Request $request): Response
    {
        if ($redirect = $this->requireAuth()) return $redirect;

        if ($request->getMethod() === 'GET') {
            return $this->render('admin/blog_form.html.twig', array_merge($this->adminData(), ['post' => null, 'error' => null]));
        }

        $title    = trim($request->post('title', ''));
        $excerpt  = trim($request->post('excerpt', ''));
        $content  = trim($request->post('content', ''));
        $status   = $request->post('status', 'draft');
        $mediaRaw = trim($request->post('media', ''));

        if (!$title || !$excerpt || !$content) {
            return $this->render('admin/blog_form.html.twig', array_merge($this->adminData(), [
                'post'  => null,
                'error' => 'Title, excerpt and content are required.',
            ]));
        }

        $post = new BlogPost();
        $post->user_id        = (int) Session::get('admin_id');
        $post->title          = $title;
        $post->slug           = BlogPost::generateSlug($title);
        $post->excerpt        = $excerpt;
        $post->content        = $content;
        $post->status         = in_array($status, ['draft', 'published']) ? $status : 'draft';
        $post->featured_image = $this->handleImageUpload() ?? (trim($request->post('featured_image', '')) ?: null);

        if ($mediaRaw) {
            $decoded = json_decode($mediaRaw, true);
            $post->media = is_array($decoded) ? $mediaRaw : null;
        }

        $post->save();

        return $this->redirect('/admin/blogs');
    }

    /**
     * @Route(path="/admin/blog/edit/{id}", methods="GET,POST", name="admin.blog.edit")
     */
    public function blogEdit(Request $request, string $id): Response
    {
        if ($redirect = $this->requireAuth()) return $redirect;

        $post = BlogPost::find((int) $id);

        if (!$post) return $this->redirect('/admin/blogs');

        if ($request->getMethod() === 'GET') {
            return $this->render('admin/blog_form.html.twig', array_merge($this->adminData(), ['post' => $post, 'error' => null]));
        }

        $title    = trim($request->post('title', ''));
        $excerpt  = trim($request->post('excerpt', ''));
        $content  = trim($request->post('content', ''));
        $status   = $request->post('status', 'draft');
        $mediaRaw = trim($request->post('media', ''));

        if (!$title || !$excerpt || !$content) {
            return $this->render('admin/blog_form.html.twig', array_merge($this->adminData(), [
                'post'  => $post,
                'error' => 'Title, excerpt and content are required.',
            ]));
        }

        $post->title          = $title;
        $post->excerpt        = $excerpt;
        $post->content        = $content;
        $post->status         = in_array($status, ['draft', 'published']) ? $status : 'draft';
        $post->featured_image = $this->handleImageUpload() ?? (trim($request->post('featured_image', '')) ?: $post->featured_image);
        $post->media          = null;

        if ($mediaRaw) {
            $decoded = json_decode($mediaRaw, true);
            $post->media = is_array($decoded) ? $mediaRaw : null;
        }

        $post->save();

        return $this->redirect('/admin/blogs');
    }

    /**
     * @Route(path="/admin/blog/delete/{id}", methods="POST", name="admin.blog.delete")
     */
    public function blogDelete(Request $request, int $id): Response
    {
        if ($redirect = $this->requireAuth()) return $redirect;

        $post = BlogPost::find($id);
        if ($post) $post->delete();

        return $this->redirect('/admin/blogs');
    }

    /**
     * @Route(path="/admin/blog/ai-generate", methods="POST", name="admin.blog.ai_generate")
     */
    public function blogAiGenerate(Request $request): Response
    {
        if ($redirect = $this->requireAuth()) return $redirect;

        $topic = trim($request->post('topic', ''));

        if (!$topic) {
            return new Response(json_encode(['success' => false, 'error' => 'Topic is required.']), 422, ['Content-Type' => 'application/json']);
        }

        try {
            $service  = new \App\Services\BlogGeneratorService();
            $messages = $service->buildPublicMessages($topic);
            $client   = new OpenRouterClient();
            $raw      = $client->chat($messages);
            $parsed   = $service->parsePublicResponse($raw);

            return new Response(json_encode(['success' => true] + $parsed), 200, ['Content-Type' => 'application/json']);
        } catch (\Throwable $e) {
            return new Response(json_encode(['success' => false, 'error' => $e->getMessage()]), 500, ['Content-Type' => 'application/json']);
        }
    }

    // ─── Contact actions ──────────────────────────────────────────────────────

    /**
     * @Route(path="/admin/contact/reply/{id}", methods="POST", name="admin.contact.reply")
     */
    public function contactReply(Request $request, int $id): Response
    {
        if ($redirect = $this->requireAuth()) return $redirect;

        $contact = ContactSubmission::find($id);

        if (!$contact) return $this->redirect('/admin/contacts');

        $replyMessage = trim($request->post('reply_message', ''));

        if ($replyMessage) {
            Mailer::send(
                $contact->email,
                'Re: ' . ($contact->subject ?: 'Your Inquiry'),
                $replyMessage
            );
            $contact->status = 'replied';
        } else {
            $contact->status = 'read';
        }

        $contact->save();

        return $this->redirect('/admin/contacts');
    }

    // ─── Booking actions ──────────────────────────────────────────────────────

    /**
     * @Route(path="/admin/booking/update/{id}", methods="POST", name="admin.booking.update")
     */
    public function bookingUpdate(Request $request, int $id): Response
    {
        if ($redirect = $this->requireAuth()) return $redirect;

        $booking = StrategyBooking::find($id);

        if (!$booking) return $this->redirect('/admin/bookings');

        $status = $request->post('status', $booking->status);
        $notes  = trim($request->post('admin_notes', ''));

        $booking->status      = in_array($status, ['pending', 'contacted', 'scheduled']) ? $status : $booking->status;
        $booking->admin_notes = $notes ?: null;
        $booking->save();

        return $this->redirect('/admin/bookings');
    }

    // ─── Mail actions ─────────────────────────────────────────────────────────

    /**
     * @Route(path="/admin/mail/view/{index}", methods="GET", name="admin.mail.view")
     */
    public function mailView(Request $request, string $index): Response
    {
        if ($redirect = $this->requireAuth()) return $redirect;

        $allMails = $this->readMails();
        $idx      = (int) $index;

        if (!isset($allMails[$idx])) {
            return new Response('Mail not found.', 404);
        }

        $path = BASE_PATH . '/mails/' . $allMails[$idx]['filename'];

        if (!file_exists($path)) {
            return new Response('Mail not found.', 404);
        }

        return new Response(file_get_contents($path), 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    /**
     * @Route(path="/admin/mail/delete/{index}", methods="POST", name="admin.mail.delete")
     */
    public function mailDelete(Request $request, string $index): Response
    {
        if ($redirect = $this->requireAuth()) return $redirect;

        $allMails = $this->readMails();
        $idx      = (int) $index;

        if (isset($allMails[$idx])) {
            $path = BASE_PATH . '/mails/' . $allMails[$idx]['filename'];
            if (file_exists($path)) {
                unlink($path);
            }
        }

        return $this->redirect('/admin/mails');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function paginate(int $total, int $page, int $perPage, string $baseUrl): array
    {
        $totalPages = max(1, (int) ceil($total / $perPage));
        return [
            'total'       => $total,
            'per_page'    => $perPage,
            'current'     => $page,
            'total_pages' => $totalPages,
            'has_prev'    => $page > 1,
            'has_next'    => $page < $totalPages,
            'prev_url'    => $baseUrl . '?page=' . ($page - 1),
            'next_url'    => $baseUrl . '?page=' . ($page + 1),
            'base_url'    => $baseUrl,
        ];
    }

    private function handleImageUpload(): ?string
    {
        if (empty($_FILES['featured_image_file']['tmp_name'])) {
            return null;
        }

        $file    = $_FILES['featured_image_file'];
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $maxSize = 5 * 1024 * 1024;

        if (!in_array($file['type'], $allowed) || $file['size'] > $maxSize) {
            return null;
        }

        $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'blog_' . uniqid() . '.' . strtolower($ext);
        $dest     = BASE_PATH . '/public/uploads/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $dest)) {
            return '/public/uploads/' . $filename;
        }

        return null;
    }

    private function listMigrations(): array
    {
        $dir   = BASE_PATH . '/migrations/';
        $files = glob($dir . '*.php');
        if (!$files) return [];

        sort($files);
        $this->ensureMigrationsTable();
        $executed = $this->getExecutedMigrations();
        $ranAt    = $this->getMigrationDates();

        $result = [];
        foreach ($files as $file) {
            $filename  = basename($file);
            $ran       = in_array($filename, $executed);
            $label     = str_replace(['_', '.php'], [' ', ''], preg_replace('/^m\d+_\d+_/', '', $filename));
            $result[]  = [
                'file'   => $filename,
                'label'  => ucwords($label),
                'ran'    => $ran,
                'ran_at' => $ran ? ($ranAt[$filename] ?? '') : null,
            ];
        }

        return $result;
    }

    private function ensureMigrationsTable(): void
    {
        \Core\Database::getInstance()->query(
            'CREATE TABLE IF NOT EXISTS migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                filename VARCHAR(255) NOT NULL UNIQUE,
                ran_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            )'
        );
    }

    private function getExecutedMigrations(): array
    {
        $rows = \Core\Database::getInstance()->fetchAll('SELECT filename FROM migrations');
        return array_column($rows, 'filename');
    }

    private function getMigrationDates(): array
    {
        $rows = \Core\Database::getInstance()->fetchAll('SELECT filename, ran_at FROM migrations');
        return array_column($rows, 'ran_at', 'filename');
    }

    private function recordMigration(string $filename): void
    {
        \Core\Database::getInstance()->query(
            'INSERT INTO migrations (filename) VALUES (:filename)',
            ['filename' => $filename]
        );
    }

    private function removeMigration(string $filename): void
    {
        \Core\Database::getInstance()->query(
            'DELETE FROM migrations WHERE filename = :filename',
            ['filename' => $filename]
        );
    }

    private function loadMigrationClass(string $path, string $file): string
    {
        require_once $path;
        $className = 'Migrations\\' . pathinfo($file, PATHINFO_FILENAME);
        if (!class_exists($className)) {
            throw new \RuntimeException("Migration class {$className} not found in {$file}");
        }
        return $className;
    }

    private function scanCrons(): array
    {
        $crons = [];
        $reflection = new \ReflectionClass(\App\Controllers\CronController::class);

        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $doc = $method->getDocComment();
            if (!$doc) continue;

            if (!preg_match('/@Route\(([^)]+)\)/', $doc, $ann)) continue;

            $path = $name = null;
            if (preg_match('/path="([^"]+)"/', $ann[1], $m)) $path = $m[1];
            if (preg_match('/name="([^"]+)"/', $ann[1], $m)) $name = $m[1];

            if (!$path) continue;

            $label = ucwords(str_replace(['-', '_', '/cron/'], [' ', ' ', ''], $path));

            $crons[] = [
                'path'   => $path,
                'name'   => $name ?? $path,
                'label'  => $label,
                'method' => $method->getName(),
            ];
        }

        return $crons;
    }

    private function readCronLog(int $lines = 100): array
    {
        $path = BASE_PATH . '/storage/logs/blog_cron.log';
        if (!file_exists($path)) return [];
        $all = array_filter(explode("\n", file_get_contents($path)));
        return array_values(array_slice(array_reverse($all), 0, $lines));
    }

    private function readLogs(): array
    {
        $logs     = [];
        $logFiles = [
            'blog_cron' => BASE_PATH . '/storage/logs/blog_cron.log',
            'access'    => BASE_PATH . '/storage/logs/access.log',
        ];

        foreach ($logFiles as $name => $path) {
            if (!file_exists($path)) {
                $logs[$name] = [];
                continue;
            }
            $lines        = array_filter(explode("\n", file_get_contents($path)));
            $logs[$name]  = array_values(array_slice(array_reverse($lines), 0, 200));
        }

        return $logs;
    }

    private function readMails(): array
    {
        $dir   = BASE_PATH . '/mails/';
        $mails = [];

        if (!is_dir($dir)) return $mails;

        $files = glob($dir . '*.html');
        if (!$files) return $mails;

        usort($files, fn($a, $b) => filemtime($b) - filemtime($a));

        foreach ($files as $file) {
            $mails[] = [
                'filename' => basename($file),
                'size'     => round(filesize($file) / 1024, 1) . ' KB',
                'date'     => date('Y-m-d H:i:s', filemtime($file)),
            ];
        }

        return $mails;
    }
}
