<?php

namespace app\modules\jobs;

use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;

class SendOrderConfirmationEmailJob extends BaseObject implements JobInterface
{
    public $orderDetails;
    public $email;
    public $listItems;

    public function execute($queue)
    {
        $htmlBody = $this->generateEmailContent($this->orderDetails, $this->listItems);
        Yii::$app->mailer->compose()
            ->setFrom('thanhtoan28740@gmail.com')
            ->setTo($this->email)
            ->setSubject('Order Confirmation')
            ->setHtmlBody($htmlBody)
            ->send();
    }

    protected function generateEmailContent($orderDetails, $listItems): string
    {
        $orderCode = $orderDetails['order_code'];
        $shippingAddress = $orderDetails['shipping_address'];
        $totalAmount = $orderDetails['total_amount'];
        // Generate the HTML for order items
        $itemsHtml = '<ul>';
        foreach ($listItems as $item) {
            $itemsHtml .= '<li>';
            $itemsHtml .= 'Product ID: ' . $item['product_id'];
            $itemsHtml .= ', Quantity: ' . $item['quantity'];
            $itemsHtml .= ', Price: ' . number_format($item['price'], 2);
            $itemsHtml .= '</li>';
        }
        $itemsHtml .= '</ul>';
        // Create content HTML
        return "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; }
                    .container { width: 80%; margin: 0 auto; }
                    .header { background-color: #f4f4f4; padding: 10px; text-align: center; }
                    .content { margin: 20px 0; }
                    .footer { background-color: #f4f4f4; padding: 10px; text-align: center; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>Order Confirmation</h1>
                    </div>
                    <div class='content'>
                        <p>Thank you for your order!</p>
                        <p><strong>Order Code:</strong> $orderCode</p>
                        <p><strong>Shipping Address:</strong> $shippingAddress</p>
                        <p><strong>Total Amount:</strong> $totalAmount</p>
                        <p><strong>Items:</strong>$itemsHtml</p>
                    </div>
                    <div class='footer'>
                        <p>&copy; 2024 Nguyen Thanh Toan</p>
                    </div>
                </div>
            </body>
            </html>
        ";
    }
}