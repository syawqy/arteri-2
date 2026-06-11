<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\SirkulasiModel;
use App\Models\UserModel;
use App\Services\EmailNotificationService;

/**
 * Send email notifications untuk arsip yang overdue.
 * Jalankan via cron daily untuk check & notify peminjam.
 *
 * Usage:
 *   php spark notify:overdue
 *   php spark notify:overdue --dry-run
 */
class NotifyOverdue extends BaseCommand
{
    protected $group       = 'Notifications';
    protected $name        = 'notify:overdue';
    protected $description = 'Send email notifications untuk arsip overdue';
    protected $usage       = 'notify:overdue [--dry-run]';
    protected $options     = ['--dry-run' => 'Simulate tanpa send email'];

    public function run(array $params)
    {
        $dryRun = isset($params['dry-run']);

        if ($dryRun) {
            CLI::write('=== DRY RUN MODE ===', 'yellow');
        }

        CLI::write('Checking overdue arsip...', 'cyan');

        $sirkulasiModel = new SirkulasiModel();
        $userModel      = new UserModel();
        $emailService   = new EmailNotificationService();

        // Get all active loans (belum dikembalikan)
        $activeLoans = $sirkulasiModel
            ->where('tgl_pengembalian', null)
            ->where('tgl_haruskembali <', date('Y-m-d'))
            ->findAll();

        if (empty($activeLoans)) {
            CLI::write('No overdue arsip found.', 'green');
            return 0;
        }

        CLI::write('Found ' . count($activeLoans) . ' overdue arsip.', 'yellow');

        $sent = 0;
        $failed = 0;

        foreach ($activeLoans as $loan) {
            $username = $loan['username_peminjam'];
            $user = $userModel->where('username', $username)->first();

            if ($user === null) {
                CLI::write("  [{$loan['noarsip']}] User not found: {$username}", 'red');
                $failed++;
                continue;
            }

            // Check if user has email
            if (empty($user['email'])) {
                CLI::write("  [{$loan['noarsip']}] No email for user: {$username}", 'yellow');
                $failed++;
                continue;
            }

            $overdueDays = $this->calculateOverdueDays($loan['tgl_haruskembali']);

            if ($dryRun) {
                CLI::write("  [{$loan['noarsip']}] Would send to {$user['email']} ({$overdueDays} days overdue)", 'cyan');
                $sent++;
            } else {
                // Enrich loan data with arsip info if needed
                $loanData = array_merge($loan, [
                    'peminjam_nama' => $user['username'],
                ]);

                if ($emailService->sendOverdueNotification($loanData, $user['email'])) {
                    CLI::write("  [{$loan['noarsip']}] Sent to {$user['email']} ({$overdueDays} days overdue)", 'green');
                    $sent++;
                } else {
                    CLI::write("  [{$loan['noarsip']}] Failed to send to {$user['email']}", 'red');
                    $failed++;
                }
            }
        }

        CLI::write('', '');
        CLI::write("Summary: {$sent} sent, {$failed} failed.", $failed > 0 ? 'yellow' : 'green');

        return $failed > 0 ? 1 : 0;
    }

    private function calculateOverdueDays(string $tglHarusKembali): int
    {
        $dueDate = strtotime($tglHarusKembali);
        $today   = strtotime(date('Y-m-d'));

        return (int) (($today - $dueDate) / 86400);
    }
}
