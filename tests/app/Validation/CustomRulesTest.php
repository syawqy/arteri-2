<?php

namespace Tests\App\Validation;

use App\Validation\CustomRules;
use CodeIgniter\Test\CIUnitTestCase;

final class CustomRulesTest extends CIUnitTestCase
{
    private CustomRules $rules;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rules = new CustomRules();
    }

    public function testValidFkPasses(): void
    {
        $this->assertTrue($this->rules->valid_fk('1', 'master_kode,id', []));
    }

    public function testValidFkFails(): void
    {
        $this->assertFalse($this->rules->valid_fk('99999', 'master_kode,id', []));
    }

    public function testValidFkWithEmptyValue(): void
    {
        $this->assertTrue($this->rules->valid_fk('', 'master_kode,id', []));
    }

    public function testValidDateRangePasses(): void
    {
        $this->assertTrue($this->rules->valid_date_range('2025-02-01', 'tgl_pinjam,tgl_haruskembali', [
            'tgl_pinjam' => '2025-01-01',
            'tgl_haruskembali' => '2025-02-01',
        ]));
    }

    public function testValidDateRangeFailsWhenEndBeforeStart(): void
    {
        $this->assertFalse($this->rules->valid_date_range('2025-01-01', 'tgl_pinjam,tgl_haruskembali', [
            'tgl_pinjam' => '2025-01-01',
            'tgl_haruskembali' => '2024-12-01',
        ]));
    }

    public function testPasswordStrengthPasses(): void
    {
        $this->assertTrue($this->rules->valid_password_strength('Str0ngP4ss'));
    }

    public function testPasswordStrengthFailsWeak(): void
    {
        $this->assertFalse($this->rules->valid_password_strength('weak'));
    }
}
