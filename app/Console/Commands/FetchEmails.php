<?php

namespace App\Console\Commands;

use App\Models\AverageNode;
use App\Models\Grade;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;
use Webklex\PHPIMAP\Client;

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

        foreach ($emails as $email) {
            do {
                $from = $email->getFrom()[0] ?? null;
                $sender = $from?->mail;

                if (! is_string($sender) || ! filter_var($sender, FILTER_VALIDATE_EMAIL)) {
                    // we need decide what to do with invalid email addresses, for now we will just skip them
                    continue;
                }

                $user = User::query()
                    ->whereRaw('LOWER(email) = ?', [strtolower($sender)])
                    ->first();

                if ($user === null) {
                    continue;
                }

                $parts = preg_split('/\s+/', trim((string) $email->getSubject()));

                if ($parts === false || count($parts) !== 3) {
                    continue;
                }

                [$courseName, $value, $date] = $parts;
                $averageNode = AverageNode::query()
                    ->whereRaw('LOWER(name) = ?', [strtolower($courseName)])
                    ->first();

                if (
                    $averageNode === null
                    || preg_match('/^(?:[1-5](?:\.0|\.5)?|6(?:\.0)?)$/', $value) !== 1
                ) {
                    continue;
                }

                // witch format of date we will use dd.mm.yyyy or yy.mm.dd???
                $parsedDate = \DateTimeImmutable::createFromFormat('!d.m.Y', $date);
                $dateErrors = \DateTimeImmutable::getLastErrors();

                if (
                    $parsedDate === false
                    || ($dateErrors !== false && (
                        $dateErrors['warning_count'] > 0
                        || $dateErrors['error_count'] > 0
                    ))
                    || $parsedDate->format('d.m.Y') !== $date
                ) {
                    continue;
                }

                $testDate = CarbonImmutable::instance($parsedDate);

                if ($testDate->isFuture()) {
                    continue;
                }

                $gradeData = [
                    'user_id' => $user->id,
                    'average_node_id' => $averageNode->id,
                    'value' => (float) $value,
                    'test_date' => $testDate,
                ];

                foreach ($email->getAttachments() as $attachment) {
                    if (
                        ! is_string($attachment->filename)
                        || $attachment->filename === ''
                        || $attachment->disposition === 'inline'
                    ) {
                        continue;
                    }

                    $filename = basename($attachment->filename);
                    $path = "grades/{$user->id}/".Str::uuid().'-'.$filename;

                    if (! Storage::put($path, $attachment->getContent())) {
                        $this->warn("Failed to store attachment [{$filename}].");
                        break;
                    }

                    $gradeData['file_path'] = $path;
                    break;
                }

                Grade::query()->create($gradeData);
            } while (false); // @phpstan-ignore doWhile.alwaysFalse

            $email->setFlag('Seen');
        }

        return self::SUCCESS;
    }
}
