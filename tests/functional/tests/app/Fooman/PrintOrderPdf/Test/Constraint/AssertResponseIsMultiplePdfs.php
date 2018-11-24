<?php

namespace Fooman\PrintOrderPdf\Test\Constraint;

use Magento\Mtf\Util\Protocol\CurlInterface;
use Magento\Mtf\Util\Protocol\CurlTransport;
use Magento\Mtf\Util\Protocol\CurlTransport\BackendDecorator;
use Magento\Sales\Test\Fixture\OrderInjectable;
use Fooman\PhpunitAssertBridge\CompatAssert;

class AssertResponseIsMultiplePdfs extends AbstractAssertPdf
{
    /**
     * @param \Magento\Mtf\Config\DataInterface $config
     * @param CurlTransport                     $transport
     * @param OrderInjectable[]                 $orders
     * @param string                            $pdfMarkerExpected
     *
     * @throws \Exception
     */
    public function processAssert(
        \Magento\Mtf\Config\DataInterface $config,
        \Magento\Mtf\Util\Protocol\CurlTransport $transport,
        array $orders,
        $pdfMarkerExpected = '%PDF-1.'
    ) {
        $url = $_ENV['app_backend_url'] . 'fooman_printorderpdf/order/pdforders/';

        $curl = new BackendDecorator($transport, $config);
        $curl->addOption(CURLOPT_HEADER, 1);
        $curl->write($url, $this->convertIdsToSelected($orders), CurlInterface::POST);
        $response = $curl->read();

        $headerSize = $transport->getInfo(CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $headerSize);
        $pdfMarkerActual = substr($response, $headerSize, strlen($pdfMarkerExpected));

        $contentType = $this->getHeaderValue($header, 'Content-Type');
        $curl->close();

        CompatAssert::assertEquals(
            'application/pdf',
            $contentType,
            'Response is not a pdf.'
        );

        CompatAssert::assertEquals(
            $pdfMarkerExpected,
            $pdfMarkerActual,
            'Pdf is not the expected version'
        );
    }

    /**
     * @param $orders
     *
     * @return array
     */
    protected function convertIdsToSelected($orders)
    {
        $data = [];
        $i = 0;
        foreach ($orders as $order) {
            //getEntityId returns an array with products
            //getId returns the increment id
            //fortunately the increment id converts to the real id easily
            $data['selected'][$i++] = (int)$order->getId();
        }
        $data['search'] = '';
        $data['namespace'] = 'sales_order_grid';
        $data['filters']['placeholder'] = 'true';

        return $data;
    }
}
