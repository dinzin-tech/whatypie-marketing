<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Http\Request;
use Core\Http\Response;
use App\Models\BlogPost;

class BlogController extends Controller
{
    /**
     * @Route(path="/blog", methods="GET", name="blog.index")
     */
    public function index(Request $request): Response
    {
        $posts = BlogPost::query()
            ->where('status', 'published')
            ->orderBy('created_at', 'DESC')
            ->get();

        return $this->render('blog/index.html.twig', ['posts' => $posts]);
    }

    /**
     * @Route(path="/blog/{slug}", methods="GET", name="blog.show")
     */
    public function show(Request $request, string $slug): Response
    {
        $post = BlogPost::findOneBy(['slug' => $slug, 'status' => 'published']);

        if (!$post) {
            return new Response($this->twig->render('blog/404.html.twig'), 404);
        }

        $related = BlogPost::query()
            ->where('status', 'published')
            ->where('id', '!=', (string) $post->id)
            ->orderBy('created_at', 'DESC')
            ->limit(3)
            ->get();

        return $this->render('blog/show.html.twig', [
            'post'    => $post,
            'related' => $related,
            'media'   => $post->getMedia(),
        ]);
    }
}
