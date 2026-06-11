<?php

namespace Tests\App\Services;

use App\Services\EmailNotificationService;
use CodeIgniter\Email\Email;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class EmailNotificationServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate   = true;
    protected $seed      = \App\Database\Seeds\ArteriSeeder::class;
    protected $basePath  = APPPATH . 'Database';
    protected $namespace = 'App';

    public function testSendOverdueNotificationWithValidData(): void
    {
        $mockEmail = $this->getMockBuilder(Email::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['clear', 'setTo', 'setSubject', 'setMessage', 'send'])
            ->getMock();

        $mockEmail->expects($this->once())->method('clear');
        $mockEmail->expects($this->once())->method('setTo')->with('user@example.com');
        $mockEmail->expects($this->once())->method('setSubject')->with($this->stringContains('Overdue'));
        $mockEmail->expects($this->once())->method('setMessage')->with($this->stringContains('terlambat'));
        $mockEmail->expects($this->once())->method('send')->willReturn(true);

        $service = new EmailNotificationService($mockEmail);

        $sirkulasi = [
            'id'                => 1,
            'noarsip'           => 'TEST-001',
            'uraian'            => 'Test Document',
            'tgl_pinjam'        => date('Y-m-d', strtotime('-10 days')),
            'tgl_haruskembali'  => date('Y-m-d', strtotime('-3 days')),
            'username_peminjam' => 'testuser',
        ];

        $result = $service->sendOverdueNotification($sirkulasi, 'user@example.com');

        $this->assertTrue($result);
    }

    public function testSendOverdueNotificationReturnsFalseWhenNotOverdue(): void
    {
        $mockEmail = $this->getMockBuilder(Email::class)
            ->disableOriginalConstructor()
            ->getMock();

        $service = new EmailNotificationService($mockEmail);

        $sirkulasi = [
            'noarsip'           => 'TEST-002',
            'tgl_haruskembali'  => date('Y-m-d', strtotime('+3 days')),
            'username_peminjam' => 'testuser',
        ];

        $result = $service->sendOverdueNotification($sirkulasi, 'user@example.com');

        $this->assertFalse($result);
    }

    public function testSendRequestedNotificationWithValidData(): void
    {
        $mockEmail = $this->getMockBuilder(Email::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['clear', 'setTo', 'setSubject', 'setMessage', 'send'])
            ->getMock();

        $mockEmail->expects($this->once())->method('send')->willReturn(true);

        $service = new EmailNotificationService($mockEmail);

        $sirkulasi = [
            'id'                => 1,
            'noarsip'           => 'TEST-003',
            'uraian'            => 'Requested Document',
            'tgl_haruskembali'  => date('Y-m-d', strtotime('+7 days')),
            'username_peminjam' => 'borrower',
        ];

        $result = $service->sendRequestedNotification(
            $sirkulasi,
            'borrower@example.com',
            'requester',
            'Requester Name'
        );

        $this->assertTrue($result);
    }
}
