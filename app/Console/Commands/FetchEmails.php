<?php

namespace App\Console\Commands;

use App\Http\Requests\GradeRequest;
use App\Models\AverageNode;
use App\Models\Grade;
use App\Models\User;
use App\Notifications\GradeAddedNotification;
use App\Notifications\GradesReceivedNotification;
use Carbon\CarbonImmutable;
use finfo;
use Illuminate\Console\Command;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\Message;

class FetchEmails extends Command
{
    protected $signature = 'emails:fetch';

    protected $description = 'Fetch unread emails from the configured IMAP server.';

    public function handle(Client $client): int
    {
        try {
            $client->connect();
            $emails = $client->getFolder('INBOX')
                ->messages()
                ->unseen()
                ->get()
                ->all();
        } catch (Throwable $e) {
            $this->error("Failed to fetch emails: {$e->getMessage()}");

            return self::FAILURE;
        }

        if (count($emails) === 0) {
            $this->info('No unread emails found.');

            return self::SUCCESS;
        }

        $this->info(sprintf('Fetched %d unread email(s).', count($emails)));

        $created = collect();

        foreach ($emails as $email) {
            try {
                $grade = $this->import($email);
            } catch (Throwable $e) {
                // Left unread: the next run retries it.
                $this->warn("Failed to import email: {$e->getMessage()}");

                continue;
            }

            if ($grade !== null) {
                $created->push($grade);

                try {
                    Notification::send($grade->user->supervisors(), new GradeAddedNotification($grade));
                } catch (Throwable $e) {
                    $this->warn("Failed to notify supervisors: {$e->getMessage()}");
                }
            }

            $email->setFlag('Seen');
        }

        $mailbox = config('imap.accounts.default.username');

        foreach ($created->groupBy('user_id') as $grades) {
            try {
                Notification::route('mail', $mailbox)
                    ->notify(new GradesReceivedNotification($grades->first()->user, $grades));
            } catch (Throwable $e) {
                $this->warn("Failed to send summary mail: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }

    /**
     * Null when the mail breaks a rule (or was already imported) and is just marked read;
     * throws when it should stay unread for a retry.
     */
    private function import(Message $email): ?Grade
    {
        $sender = $this->trustedSender($email);

        if ($sender === null) {
            return null;
        }

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [strtolower($sender)])
            ->first();

        if ($user === null || ! $user->is_active || ! $user->can('create', Grade::class)) {
            return null;
        }

        // Grade and date are the last two words; the branch name may contain spaces.
        if (preg_match('/^(.+?)\s+(\S+)\s+(\S+)$/', trim((string) $email->getSubject()), $parts) !== 1) {
            return null;
        }

        [, $courseName, $value, $date] = $parts;

        // A leaf of the apprentice's own plan, as GradeRequest requires.
        $averageNode = AverageNode::query()
            ->whereRaw('LOWER(name) = ?', [strtolower($courseName)])
            ->whereHas('formationPlans', fn ($plans) => $plans->whereKey($user->formation_plan_id))
            ->whereDoesntHave('children')
            ->first();

        if (
            $averageNode === null
            || preg_match('/^(?:[1-5](?:\.0|\.5)?|6(?:\.0)?)$/', $value) !== 1
        ) {
            return null;
        }

        $parsedDate = \DateTimeImmutable::createFromFormat('!d.m.Y', $date);

        if ($parsedDate === false || $parsedDate->format('d.m.Y') !== $date) {
            return null;
        }

        $testDate = CarbonImmutable::instance($parsedDate);

        if ($testDate->isFuture()) {
            return null;
        }

        // Same file rules as GradeRequest: the first PDF, 10 MB at most. Other attachments
        // (signatures, images) are ignored.
        $file = null;

        foreach ($email->getAttachments() as $attachment) {
            if (! is_string($attachment->filename) || $attachment->filename === '' || $attachment->disposition === 'inline') {
                continue;
            }

            $content = $attachment->getContent();

            if ((new finfo(FILEINFO_MIME_TYPE))->buffer($content) !== 'application/pdf') {
                continue;
            }

            if (strlen($content) > GradeRequest::MAX_FILE_KB * 1024) {
                return null;
            }

            $file = $content;
            break;
        }

        $path = null;

        try {
            return DB::transaction(function () use ($email, $user, $averageNode, $value, $testDate, $file, &$path): Grade {
                // The unique source makes a retry after a crash, or an overlapping manual run, a no-op.
                $grade = Grade::query()->create([
                    'user_id' => $user->id,
                    'average_node_id' => $averageNode->id,
                    'value' => (float) $value,
                    'test_date' => $testDate,
                    'source_mailbox' => config('imap.accounts.default.username'),
                    'source_uid' => $email->getUid(),
                ]);

                if ($file !== null) {
                    $path = "grades/{$user->id}/".Str::uuid().'.pdf';

                    if (! Storage::put($path, $file)) {
                        throw new RuntimeException('Could not store the attachment.');
                    }

                    $grade->update(['file_path' => $path]);
                }

                return $grade->load('user', 'averageNode');
            });
        } catch (UniqueConstraintViolationException) {
            return null;
        } catch (Throwable $e) {
            if ($path !== null) {
                Storage::delete($path);
            }

            throw $e;
        }
    }

    /**
     * The From address, only when the receiving server vouches for it: its own (topmost)
     * Authentication-Results header must report dmarc=pass for the From domain.
     * Without IMAP_TRUSTED_AUTHSERV_ID, From is trusted as-is in local only (GreenMail).
     */
    private function trustedSender(Message $email): ?string
    {
        $sender = ($email->getFrom()[0] ?? null)?->mail;

        if (! is_string($sender) || ! filter_var($sender, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $authservId = config('imap.trusted_authserv_id');

        if (! is_string($authservId) || $authservId === '') {
            return app()->isLocal() ? $sender : null;
        }

        // Unfold, then take the first Authentication-Results: the receiving server prepends it.
        $raw = preg_replace('/\r?\n[ \t]+/', ' ', (string) $email->getHeader()?->raw);

        if (preg_match('/^Authentication-Results:\s*([^;\s]+)[^;]*;(.*)$/mi', (string) $raw, $results) !== 1) {
            return null;
        }

        $domain = strtolower(Str::after($sender, '@'));

        $trusted = strcasecmp($results[1], $authservId) === 0
            && preg_match('/(?:^|;)\s*dmarc=pass\b[^;]*header\.from=([^\s;]+)/i', $results[2], $dmarc) === 1
            && strtolower($dmarc[1]) === $domain;

        return $trusted ? $sender : null;
    }
}
