<?php

namespace Tests\Unit\Libs\Ocr;

use App\Libs\Ocr\Data\BusinessLicenseResult;
use Tests\TestCase;

class BusinessLicenseResultTest extends TestCase
{
    public function test_from_aliyun_data_parses_nested_data_payload(): void
    {
        $result = BusinessLicenseResult::fromAliyunData('req-1', [
            'data' => [
                'creditCode' => '91310000MA1FL2XX1X',
                'companyName' => '示例科技有限公司',
                'companyType' => '有限责任公司',
                'businessAddress' => '上海市浦东新区',
                'legalPerson' => '张三',
                'businessScope' => '软件开发',
                'registeredCapital' => '1000万元',
                'RegistrationDate' => '2020年01月01日',
                'validPeriod' => '2020年01月01日至长期',
                'validFromDate' => '20200101',
                'validToDate' => '29991231',
                'companyForm' => '有限责任公司',
            ],
        ]);

        $this->assertSame('91310000MA1FL2XX1X', $result->creditCode);
        $this->assertSame('示例科技有限公司', $result->companyName);
        $this->assertSame('2020年01月01日', $result->registrationDate);
        $this->assertSame('req-1', $result->requestId);
    }

    public function test_from_aliyun_data_supports_flat_payload(): void
    {
        $result = BusinessLicenseResult::fromAliyunData('req-2', [
            'creditCode' => '91110000MA01234567',
            'companyName' => '北京示例公司',
            'registrationDate' => '2019年05月10日',
        ]);

        $this->assertSame('91110000MA01234567', $result->creditCode);
        $this->assertSame('北京示例公司', $result->companyName);
        $this->assertSame('2019年05月10日', $result->registrationDate);
    }
}
