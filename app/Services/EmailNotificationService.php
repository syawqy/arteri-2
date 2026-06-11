<?php

namespace App\Services;

use App\Models\SystemLogModel;
use CodeIgniter\Email\Email;

/**
 * Service untuk handle email notifications.
 * Supports overdue dan requested notifications dengan logging.
 */
class EmailNotificationService
{
    private Email $email;
    private SystemLogModel $systemLog;

    public function __construct(?Email $email = null)
    {
        $this->email     = $email ?? service('email');
        $this->systemLog = new SystemLogModel();
    }

    /**
     * Send overdue notification ke peminjam.
     *
     * @param array $sirkulasi Record sirkulasi dengan arsip info
     * @param string $recipientEmail Email peminjam
     * @return bool Success status
     */
    public function sendOverdueNotification(array $sirkulasi, string $recipientEmail): bool
    {
        $hari_terlambat = $this->calculateOverdueDays($sirkulasi['tgl_haruskembali']);

        if ($hari_terlambat <= 0) {
            return false; // Belum overdue
        }

        $data = [
            'noarsip'           => $sirkulasi['noarsip'],
            'uraian'            => $sirkulasi['uraian'] ?? 'N/A',
            'tgl_pinjam'        => $sirkulasi['tgl_pinjam'],
            'tgl_haruskembali'  => $sirkulasi['tgl_haruskembali'],
            'hari_terlambat'    => $hari_terlambat,
            'peminjam_username' => $sirkulasi['username_peminjam'],
            'peminjam_nama'     => $sirkulasi['peminjam_nama'] ?? $sirkulasi['username_peminjam'],
        ];

        $subject = "⚠️ Arsip Overdue - {$sirkulasi['noarsip']}";
        $message = view('emails/overdue', $data);

        $success = $this->send($recipientEmail, $subject, $message);

        $this->logEmail([
            'type'       => 'overdue',
            'recipient'  => $recipientEmail,
            'sirkulasi_id' => $sirkulasi['id'] ?? null,
            'noarsip'    => $sirkulasi['noarsip'],
            'success'    => $success,
            'overdue_days' => $hari_terlambat,
        ]);

        return $success;
    }

    /**
     * Send requested notification ke peminjam saat ini.
     *
     * @param array $sirkulasi Record sirkulasi (current borrower)
     * @param string $recipientEmail Email current borrower
     * @param string $requesterUsername Username yang request
     * @param string $requesterName Nama yang request
     * @return bool Success status
     */
    public function sendRequestedNotification(
        array $sirkulasi,
        string $recipientEmail,
        string $requesterUsername,
        string $requesterName
    ): bool {
        $data = [
            'noarsip'           => $sirkulasi['noarsip'],
            'uraian'            => $sirkulasi['uraian'] ?? 'N/A',
            'tgl_haruskembali'  => $sirkulasi['tgl_haruskembali'],
            'peminjam_username' => $sirkulasi['username_peminjam'],
            'peminjam_nama'     => $sirkulasi['peminjam_nama'] ?? $sirkulasi['username_peminjam'],
            'requester_username'=> $requesterUsername,
            'requester_nama'    => $requesterName,
            'tgl_request'       => date('Y-m-d H:i:s'),
        ];

        $subject = "📬 Request Arsip - {$sirkulasi['noarsip']}";
        $message = view('emails/requested', $data);

        $success = $this->send($recipientEmail, $subject, $message);

        $this->logEmail([
            'type'       => 'requested',
            'recipient'  => $recipientEmail,
            'sirkulasi_id' => $sirkulasi['id'] ?? null,
            'noarsip'    => $sirkulasi['noarsip'],
            'requester'  => $requesterUsername,
            'success'    => $success,
        ]);

        return $success;
    }

    /**
     * Send email via CodeIgniter Email library.
     */
    private function send(string $to, string $subject, string $message): bool
    {
        try {
            $this->email->clear();
            $this->email->setTo($to);
            $this->email->setSubject($subject);
            $this->email->setMessage($message);

            return $this->email->send();
        } catch (\Exception $e) {
            log_message('error', 'Email send failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Calculate overdue days from due date.
     */
    private function calculateOverdueDays(string $tglHarusKembali): int
    {
        $dueDate = strtotime($tglHarusKembali);
        $today   = strtotime(date('Y-m-d'));

        return (int) (($today - $dueDate) / 86400);
    }

    /**
     * Log email sending to system_log.
     */
    private function logEmail(array $details): void
    {
        $this->systemLog->insert([
            'kode_transaksi'     => 'EMAIL',
            'username_transaksi' => 'system',
            'tgl_transaksi'      => date('Y-m-d H:i:s'),
            'aksi'               => strtoupper($details['type']) . '_EMAIL',
            'tabel'              => 'email_notification',
            'record_id'          => $details['sirkulasi_id'] ?? null,
            'detail'             => json_encode($details),
            'ip_address'         => null,
        ], false);
    }
}
