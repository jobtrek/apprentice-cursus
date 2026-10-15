<?php

use App\Models\AverageNode;
use App\Models\FormationPlan;
use App\Models\Grade;
use App\Models\User;
use App\Notifications\GradeAddedNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\Header;
use Webklex\PHPIMAP\Message;
use Webklex\PHPIMAP\Support\AttachmentCollection;

beforeEach(function () {
    Storage::fake();
    Notification::fake();
    config(['imap.trusted_authserv_id' => 'mx.example.com', 'imap.accounts.default.username' => 'grades@example.com']);

    $this->plan = section(FormationPlan::EC);
    $root = AverageNode::query()->create(['name' => 'Root']);
    $this->leaf = AverageNode::query()->create(['name' => 'TPI - Documentation', 'parent_id' => $root->id, 'weight' => 1]);
    $this->plan->averageNodes()->attach([$root->id, $this->leaf->id]);

    $this->apprentice = makeApprentice($this->plan, User::factory()->coach()->create());
    $this->apprentice->forceFill(['email' => 'anna@school.ch'])->save();
});

function intakeMail(array $overrides = []): Message
{
    $o = [
        'from' => 'anna@school.ch',
        'subject' => 'TPI - Documentation 5.5 14.10.2026',
        'auth' => "Authentication-Results: mx.example.com;\r\n dkim=pass; dmarc=pass (p=REJECT) header.from=school.ch",
        'attachments' => [],
        'uid' => 1,
        ...$overrides,
    ];

    $header = Mockery::mock(Header::class);
    $header->raw = "From: {$o['from']}\r\n{$o['auth']}\r\n";

    $message = Mockery::mock(Message::class);
    $message->shouldReceive('getFrom')->andReturn([(object) ['mail' => $o['from']]]);
    $message->shouldReceive('getSubject')->andReturn($o['subject']);
    $message->shouldReceive('getHeader')->andReturn($header);
    $message->shouldReceive('getAttachments')->andReturn(new AttachmentCollection($o['attachments']));
    $message->shouldReceive('getUid')->andReturn($o['uid']);
    $message->shouldReceive('setFlag')->andReturnTrue()->byDefault();

    return $message;
}

function pdfAttachment(string $content, string $name = 'scan.pdf'): object
{
    return new class($content, $name)
    {
        public ?string $disposition = 'attachment';

        public function __construct(private string $content, public string $filename) {}

        public function getContent(): string
        {
            return $this->content;
        }
    };
}

function fetchMails(Message ...$messages): void
{
    $client = Mockery::mock(Client::class);
    $client->shouldReceive('connect');
    $client->shouldReceive('getFolder->messages->unseen->get->all')->andReturn($messages);
    app()->instance(Client::class, $client);

    test()->artisan('emails:fetch')->assertSuccessful();
}

test('imports a grade with a multi-word branch, its PDF and notifies supervisors', function () {
    fetchMails(intakeMail(['attachments' => [pdfAttachment('%PDF-1.4 fake')]]));

    $grade = Grade::query()->sole();
    expect($grade->average_node_id)->toBe($this->leaf->id)
        ->and($grade->source_uid)->toBe(1)
        ->and(Storage::exists($grade->file_path))->toBeTrue();
    Notification::assertSentTo($this->apprentice->supervisors(), GradeAddedNotification::class);
});

test('rejects a From the receiving server did not authenticate', function (string $auth) {
    fetchMails(intakeMail(['auth' => $auth]));

    expect(Grade::query()->count())->toBe(0);
})->with([
    'no header' => 'X-Other: 1',
    'dmarc fail' => 'Authentication-Results: mx.example.com; dmarc=fail header.from=school.ch',
    'other server' => 'Authentication-Results: evil.test; dmarc=pass header.from=school.ch',
    'other domain' => 'Authentication-Results: mx.example.com; dmarc=pass header.from=evil.test',
]);

test('rejects a sender who may not create grades', function () {
    User::factory()->coach()->create(['email' => 'coach@school.ch']);

    fetchMails(intakeMail(['from' => 'coach@school.ch', 'auth' => 'Authentication-Results: mx.example.com; dmarc=pass header.from=school.ch']));

    expect(Grade::query()->count())->toBe(0);
});

test('rejects a branch outside the apprentice plan', function () {
    AverageNode::query()->create(['name' => 'Anglais']);

    fetchMails(intakeMail(['subject' => 'Anglais 5.0 14.10.2026']));

    expect(Grade::query()->count())->toBe(0);
});

test('ignores non-PDF attachments and rejects oversized PDFs', function () {
    fetchMails(
        intakeMail(['uid' => 1, 'attachments' => [pdfAttachment('a,b,c', 'notes.csv')]]),
        intakeMail(['uid' => 2, 'attachments' => [pdfAttachment('%PDF-'.str_repeat('x', 10240 * 1024))]]),
    );

    expect(Grade::query()->sole()->file_path)->toBeNull();
});

test('leaves the mail unread when the attachment cannot be stored', function () {
    Storage::shouldReceive('put')->andReturnFalse();
    $message = intakeMail(['attachments' => [pdfAttachment('%PDF-1.4 fake')]]);
    $message->shouldNotReceive('setFlag');

    fetchMails($message);

    expect(Grade::query()->count())->toBe(0);
});

test('does not import the same mail twice', function () {
    fetchMails(intakeMail());
    fetchMails(intakeMail());

    expect(Grade::query()->count())->toBe(1);
});
