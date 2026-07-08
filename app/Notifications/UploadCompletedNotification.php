<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UploadCompletedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $fileName,
        public int $totalRecords,
        public int $successRecords,
        public int $failedRecords
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Shopify Import Completed: '.$this->fileName)
            ->greeting('Import Process Completed')
            ->line("Your CSV import for **{$this->fileName}** has completed processing.")
            ->line('### Import Summary:')
            ->line("- **Total Records:** {$this->totalRecords}")
            ->line("- **Successfully Synced:** {$this->successRecords}")
            ->line("- **Failed Records:** {$this->failedRecords}")
            ->action('View Uploads', route('uploads.index'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'file_name' => $this->fileName,
            'total_records' => $this->totalRecords,
            'success_records' => $this->successRecords,
            'failed_records' => $this->failedRecords,
        ];
    }
}
