<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Sales\Test\Unit\Setup;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Serialize\Serializer\Serialize;
use Magento\Sales\Setup\SerializedDataConverter;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class SerializedDataConverterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Serialize|\PHPUnit_Framework_MockObject_MockObject
     */
    private $serializeMock;

    /**
     * @var Json|\PHPUnit_Framework_MockObject_MockObject
     */
    private $jsonMock;

    /**
     * @var SerializedDataConverter
     */
    private $serializedDataConverter;

    protected function setUp()
    {
        $objectManager = new ObjectManager($this);
        $this->serializeMock = $this->getMock(Serialize::class, [], [], '', false);
        $this->jsonMock = $this->getMock(Json::class, [], [], '', false);
        $this->serializedDataConverter = $objectManager->getObject(
            SerializedDataConverter::class,
            [
                'serialize' => $this->serializeMock,
                'json' => $this->jsonMock
            ]
        );
    }

    public function testConvert()
    {
        $serializedData = 'serialized data';
        $jsonEncodedData = 'json encoded data';
        $data = [
            'info_buyRequest' => [
                'product' => 1,
                'qty' => 2
            ]
        ];
        $this->serializeMock->expects($this->once())
            ->method('unserialize')
            ->with($serializedData)
            ->willReturn($data);
        $this->jsonMock->expects($this->once())
            ->method('serialize')
            ->with($data)
            ->willReturn($jsonEncodedData);
        $this->assertEquals(
            $jsonEncodedData,
            $this->serializedDataConverter->convert($serializedData)
        );
    }

    public function testConvertBundleAttributes()
    {
        $serializedData = 'serialized data';
        $serializedBundleAttributes = 'serialized bundle attributes';
        $bundleAttributes = ['foo' => 'bar'];
        $jsonEncodedBundleAttributes = 'json encoded bundle attributes';
        $jsonEncodedData = 'json encoded data';
        $data = [
            'info_buyRequest' => [
                'product' => 1,
                'qty' => 2
            ],
            'bundle_selection_attributes' => $serializedBundleAttributes
        ];
        $dataWithJsonEncodedBundleAttributes = [
            'info_buyRequest' => [
                'product' => 1,
                'qty' => 2
            ],
            'bundle_selection_attributes' => $jsonEncodedBundleAttributes
        ];
        $this->serializeMock->expects($this->at(0))
            ->method('unserialize')
            ->with($serializedData)
            ->willReturn($data);
        $this->serializeMock->expects($this->at(1))
            ->method('unserialize')
            ->with($serializedBundleAttributes)
            ->willReturn($bundleAttributes);
        $this->jsonMock->expects($this->at(0))
            ->method('serialize')
            ->with($bundleAttributes)
            ->willReturn($jsonEncodedBundleAttributes);
        $this->jsonMock->expects($this->at(1))
            ->method('serialize')
            ->with($dataWithJsonEncodedBundleAttributes)
            ->willReturn($jsonEncodedData);
        $this->assertEquals(
            $jsonEncodedData,
            $this->serializedDataConverter->convert($serializedData)
        );
    }

    public function testConvertCustomOptionsTypeFile()
    {
        $serializedData = 'serialized data';
        $serializedOptionValue = 'serialized option value';
        $optionValue = ['foo' => 'bar'];
        $jsonEncodedOptionValue = 'json encoded option value';
        $jsonEncodedData = 'json encoded data';
        $data = [
            'info_buyRequest' => [
                'product' => 1,
                'qty' => 2
            ],
            'options' => [
                [
                    'option_type' => 'file',
                    'option_value' => $serializedOptionValue
                ],
                [
                    'option_type' => 'text',
                    'option_value' => 'option 2'
                ]
            ]
        ];
        $dataWithJsonEncodedOptionValue = [
            'info_buyRequest' => [
                'product' => 1,
                'qty' => 2
            ],
            'options' => [
                [
                    'option_type' => 'file',
                    'option_value' => $jsonEncodedOptionValue
                ],
                [
                    'option_type' => 'text',
                    'option_value' => 'option 2'
                ]
            ]
        ];
        $this->serializeMock->expects($this->at(0))
            ->method('unserialize')
            ->with($serializedData)
            ->willReturn($data);
        $this->serializeMock->expects($this->at(1))
            ->method('unserialize')
            ->with($serializedOptionValue)
            ->willReturn($optionValue);
        $this->jsonMock->expects($this->at(0))
            ->method('serialize')
            ->with($optionValue)
            ->willReturn($jsonEncodedOptionValue);
        $this->jsonMock->expects($this->at(1))
            ->method('serialize')
            ->with($dataWithJsonEncodedOptionValue)
            ->willReturn($jsonEncodedData);
        $this->assertEquals(
            $jsonEncodedData,
            $this->serializedDataConverter->convert($serializedData)
        );
    }

    /**
     * @expectedException \Magento\Framework\DB\DataConverter\DataConversionException
     */
    public function testConvertCorruptedData()
    {
        $this->serializeMock->expects($this->once())
            ->method('unserialize')
            ->willReturnCallback(
                function () {
                    trigger_error('Can not unserialize string message', E_NOTICE);
                }
            );
        $this->serializedDataConverter->convert('serialized data');
    }

    public function testConvertSkipConversion()
    {
        $serialized = '[]';
        $this->serializeMock->expects($this->never())
            ->method('unserialize');
        $this->jsonMock->expects($this->never())
            ->method('serialize');
        $this->serializedDataConverter->convert($serialized);
    }

    public function testConvertVaultTokenMetadata()
    {
        $serializedData = 'serialized data';
        $unserializedData = [
            'token_metadata' => [
                \Magento\Vault\Api\Data\PaymentTokenInterface::CUSTOMER_ID => 1,
                \Magento\Vault\Api\Data\PaymentTokenInterface::PUBLIC_HASH => 'someHash'
            ]
        ];
        $convertedUnserializedData = [
            \Magento\Vault\Api\Data\PaymentTokenInterface::CUSTOMER_ID => 1,
            \Magento\Vault\Api\Data\PaymentTokenInterface::PUBLIC_HASH => 'someHash'
        ];
        $jsonEncodedData = 'json encoded data';

        $this->serializeMock->expects($this->once())
            ->method('unserialize')
            ->with($serializedData)
            ->willReturn($unserializedData);
        $this->jsonMock->expects($this->once())
            ->method('serialize')
            ->with($convertedUnserializedData)
            ->willReturn($jsonEncodedData);

        $this->assertEquals($jsonEncodedData, $this->serializedDataConverter->convert($serializedData));
    }
}
