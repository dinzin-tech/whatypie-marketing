# Cron Setup — AI Blog Auto-Generation

## How it works

The route `GET /cron/generate-blog?secret=YOUR_SECRET` triggers one full blog post generation cycle:

1. Picks the next unused topic from the WhatyPie topic pool
2. Calls the OpenRouter API to generate title, excerpt and full HTML content
3. Saves the post to `blog_posts` as `status=published`
4. Writes a timestamped entry to `storage/logs/blog_cron.log`
5. Sends an alert email to `ALERT_EMAIL` on both success and failure
6. Returns a JSON response with the result

## Setup

### 1. Configure `.env`

```env
OPENROUTER_API_KEY=sk-or-your-key-here
OPENROUTER_MODEL=mistralai/mistral-7b-instruct
ALERT_EMAIL=admin@whatypie.in
CRON_SECRET=generate-a-long-random-string-here
```

Generate a secure `CRON_SECRET`:
```bash
php bin/console keygenerate
# or just use any long random string
```

### 2. cPanel Cron Job (Shared Hosting)

Go to **cPanel → Cron Jobs** and add a new job.

**Recommended: Once daily at 9am**
```
0 9 * * *   curl -s "https://yourdomain.com/cron/generate-blog?secret=YOUR_SECRET" > /dev/null
```

**Higher volume: Every 6 hours**
```
0 */6 * * *   curl -s "https://yourdomain.com/cron/generate-blog?secret=YOUR_SECRET" > /dev/null
```

**With wget (if curl is unavailable)**
```
0 9 * * *   wget -q -O /dev/null "https://yourdomain.com/cron/generate-blog?secret=YOUR_SECRET"
```

### 3. Plesk Cron Job

Go to **Plesk → Scheduled Tasks → Add Task** and set the command to:
```
curl -s "https://yourdomain.com/cron/generate-blog?secret=YOUR_SECRET"
```

### 4. Manual trigger (testing)

Open in browser or run:
```bash
curl "http://localhost:8000/cron/generate-blog?secret=YOUR_SECRET"
```

Pass an optional `topic` param to override the auto-picked topic:
```bash
curl "http://localhost:8000/cron/generate-blog?secret=YOUR_SECRET&topic=AI+automation+for+retail"
```

## Log file

All runs are logged to:
```
storage/logs/blog_cron.log
```

Example entries:
```
[2025-07-10 09:00:01] [INFO] Starting generation for topic: "The Real ROI of AI Automation vs. Hiring an Extra Employee"
[2025-07-10 09:00:08] [SUCCESS] Post published: "The Real ROI of AI Automation vs. Hiring an Extra Employee" (id=3, slug=the-real-roi-of-ai-automation, words=712)
[2025-07-10 15:00:01] [INFO] Starting generation for topic: "How to Automate Your Sales Pipeline with AI Agents"
[2025-07-10 15:00:03] [FAILURE] OpenRouter API error 429: {"error":{"message":"Too Many Requests"}}
```

## Alert emails

On every run, an email is sent to `ALERT_EMAIL`:

- **SUCCESS** — includes post title, slug, word count
- **FAILURE** — includes the full error message for debugging

Emails are stored locally under `mails/` (via `LocalMailer`) and also dispatched via `mail()`.

## Topic pool

20 pre-seeded WhatyPie-specific topics are built into `BlogGeneratorService`. The service skips topics whose titles already exist in the database, cycling through all 20 before repeating.

To add custom topics, edit the `TOPICS` constant in `app/Services/BlogGeneratorService.php`.
