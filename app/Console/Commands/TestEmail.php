<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestEmail extends Command
{
    protected $signature = 'test:email {email}';
    protected $description = 'Test email sending';

    public function handle()
    {
        $email = $this->argument('email');
        $this->info("Sending test email to $email...");

        try {
            Mail::raw('This is a test email from Leirad Massage.', function ($message) use ($email) {
                $message->to($email)
                        ->subject('Test Email');
            });
            $this->info('Email sent successfully!');
        } catch (\Exception $e) {
            $this->error('Failed to send email: ' . $e->getMessage());
        }
    }
}
