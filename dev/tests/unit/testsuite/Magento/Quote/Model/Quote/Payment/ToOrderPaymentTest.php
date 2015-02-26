<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Quote\Model\Quote\Payment;

use Magento\Payment\Model\Method\Substitution;
use Magento\TestFramework\Helper\ObjectManager;

/**
 * Class ToOrderPaymentTest tests converter to order payment
 */
class ToOrderPaymentTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Magento\Sales\Api\Data\OrderPaymentInterfaceFactory | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderPaymentFactoryMock;

    /**
     * @var \Magento\Framework\Object\Copy | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectCopyMock;

    /**
     * @var \Magento\Quote\Model\Quote\Payment | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentMock;

    /**
     * @var \Magento\Quote\Model\Quote\Payment\ToOrderPayment
     */
    protected $converter;

    public function setUp()
    {
        $this->paymentMock = $this->getMock(
            'Magento\Quote\Model\Quote\Payment',
            ['getCcNumber', 'getCcCidEnc', 'getMethodInstance', 'getAdditionalInformation'],
            [],
            '',
            false
        );
        $this->objectCopyMock = $this->getMock('Magento\Framework\Object\Copy', [], [], '', false);
        $this->orderPaymentFactoryMock = $this->getMock(
            'Magento\Sales\Api\Data\OrderPaymentInterfaceFactory',
            ['populateWithArray', 'create'],
            [],
            '',
            false
        );
        $dataObjectHelper = $this->getMock('\Magento\Framework\Api\DataObjectHelper', [], [], '', false);
        $objectManager = new ObjectManager($this);
        $this->converter = $objectManager->getObject(
            'Magento\Quote\Model\Quote\Payment\ToOrderPayment',
            [
                'orderPaymentFactory' => $this->orderPaymentFactoryMock,
                'objectCopyService' => $this->objectCopyMock,
                'dataObjectHelper' => $dataObjectHelper
            ]
        );
    }

    /**
     * Tests Convert method in payment to order payment converter
     */
    public function testConvert()
    {
        $methodInterface = $this->getMock('Magento\Payment\Model\MethodInterface', [], [], '', false);

        $paymentData = ['test' => 'test2'];
        $data = ['some_id' => 1];
        $paymentMethodTitle = 'TestTitle';
        $additionalInfo = ['token' => 'TOKEN-123'];
        
        $this->paymentMock->expects($this->once())->method('getMethodInstance')->willReturn($methodInterface);
        $methodInterface->expects($this->once())->method('getTitle')->willReturn($paymentMethodTitle);
        $this->objectCopyMock->expects($this->once())->method('getDataFromFieldset')->with(
            'quote_convert_payment',
            'to_order_payment',
            $this->paymentMock
        )->willReturn($paymentData);
        $this->orderPaymentFactoryMock->expects($this->never())
            ->method('populateWithArray')
            ->with(array_merge($paymentData, $data))
            ->willReturnSelf();

        $this->paymentMock->expects($this->once())
            ->method('getAdditionalInformation')
            ->willReturn($additionalInfo);
        $ccNumber = 123456798;
        $ccCid = 1234;
        $this->paymentMock->expects($this->any())
            ->method('getCcNumber')
            ->willReturn($ccNumber);
        $this->paymentMock->expects($this->any())
            ->method('getCcCid')
            ->willReturn($ccCid);

        $orderPayment = $this->getMockForAbstractClass(
            'Magento\Sales\Api\Data\OrderPaymentInterface',
            [],
            '',
            false,
            true,
            true,
            ['setCcNumber', 'setCcCid', 'setAdditionalInformation']
        );
        $orderPayment->expects($this->once())
            ->method('setAdditionalInformation')
            ->with(serialize(array_merge($additionalInfo, [Substitution::INFO_KEY_TITLE => $paymentMethodTitle])))
            ->willReturnSelf();
        $orderPayment->expects($this->any())
            ->method('setCcNumber')
            ->willReturnSelf();
        $orderPayment->expects($this->any())
            ->method('setCcCid')
            ->willReturnSelf();

        $this->orderPaymentFactoryMock->expects($this->once())->method('create')->willReturn($orderPayment);

        $this->assertSame($orderPayment, $this->converter->convert($this->paymentMock, $data));
    }
}
