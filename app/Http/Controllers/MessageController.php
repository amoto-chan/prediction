<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    /** Inbox, sent folder and (for staff) the student compose list. */
    public function index(Request $request)
    {
        $user = $request->user();

        $received = Message::where('recipient_id', $user->id)
            ->with('sender')
            ->latest()
            ->paginate(8)
            ->withQueryString();

        $sent = Message::where('sender_id', $user->id)
            ->with('recipient')
            ->latest()
            ->take(10)
            ->get();

        $candidates = collect();
        if (! $user->isRole('student')) {
            $candidates = User::role('student')
                ->with('records:id,student_id,score')
                ->orderBy('name')
                ->get()
                ->filter(fn (User $student) => $user->canMessageStudent($student))
                ->map(fn (User $student) => [
                    'id' => $student->id,
                    'name' => $student->name,
                    'student_id' => $student->student_id,
                    'section' => $student->section,
                    'status' => $student->academicPerformance(),
                ])
                ->values();
        }

        return view('messages.index', [
            'received' => $received,
            'sent' => $sent,
            'candidates' => $candidates,
            'prefillRecipient' => $request->integer('recipient') ?: null,
            'autoAtRisk' => $request->boolean('at_risk'),
            'unreadCount' => Message::where('recipient_id', $user->id)->whereNull('read_at')->count(),
        ]);
    }

    /** Send a message to a single recipient. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'recipient_id' => 'required|exists:users,id',
            'subject' => 'required|string|max:150',
            'body' => 'required|string|max:2000',
        ]);

        $sender = $request->user();
        $recipient = User::findOrFail($data['recipient_id']);

        abort_unless($sender->canMessageStudent($recipient), 403);

        Message::create([...$data, 'sender_id' => $sender->id]);

        SystemLog::create([
            'user_id' => $sender->id,
            'action' => 'Sent message',
            'description' => "Sent \"{$data['subject']}\" to {$recipient->name}.",
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', "Message sent to {$recipient->name}.");
    }

    /** Bulk send: used to notify every At Risk / Failed student in scope. */
    public function storeBulk(Request $request)
    {
        $data = $request->validate([
            'recipient_ids' => ['required', 'array', 'min:1', 'max:500'],
            'recipient_ids.*' => ['integer', 'distinct', 'exists:users,id'],
            'subject' => 'required|string|max:150',
            'body' => 'required|string|max:2000',
        ]);

        $sender = $request->user();
        $ids = array_values(array_unique($data['recipient_ids']));

        $students = User::role('student')->whereIn('id', $ids)->get()->keyBy('id');

        abort_if($students->count() !== count($ids), 422, 'Messages can only be sent to student accounts.');

        foreach ($ids as $id) {
            abort_unless($sender->canMessageStudent($students[$id]), 403, 'One or more recipients are outside your scope.');
        }

        DB::transaction(function () use ($students, $sender, $data): void {
            foreach ($students as $student) {
                Message::create([
                    'sender_id' => $sender->id,
                    'recipient_id' => $student->id,
                    'subject' => $data['subject'],
                    'body' => $data['body'],
                ]);
            }
        });

        SystemLog::create([
            'user_id' => $sender->id,
            'action' => 'Sent message',
            'description' => sprintf('Sent "%s" to %d student(s).', $data['subject'], count($ids)),
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Message sent to '.count($ids).' student(s).');
    }

    /** Mark an incoming message as read. */
    public function read(Request $request, Message $message)
    {
        abort_unless($message->recipient_id === $request->user()->id, 403);

        $message->update(['read_at' => now()]);

        return back();
    }
}