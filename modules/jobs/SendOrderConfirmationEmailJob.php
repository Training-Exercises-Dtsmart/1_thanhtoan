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
        $itemsHtml = '<table style="width: 50%; border-collapse: collapse; margin: 20px auto;">';
        $itemsHtml .=
            '<thead>
            <tr>
                <th style="border: 1px solid #dddddd; text-align: left; padding: 8px; background-color: #f2f2f2;">Name</th>
                <th style="border: 1px solid #dddddd; text-align: left; padding: 8px; background-color: #f2f2f2;">Quantity</th>
                <th style="border: 1px solid #dddddd; text-align: left; padding: 8px; background-color: #f2f2f2;">Price</th>
            </tr>
        </thead><tbody>';
        foreach ($listItems as $item) {
            $itemsHtml .= '<tr style="background-color: #f9f9f9;">';
            $itemsHtml .= '<td style="border: 1px solid #dddddd; text-align: left; padding: 8px;">' . $item['name'] . '</td>';
            $itemsHtml .= '<td style="border: 1px solid #dddddd; text-align: left; padding: 8px;">' . $item['quantity'] . '</td>';
            $itemsHtml .= '<td style="border: 1px solid #dddddd; text-align: left; padding: 8px;">' . number_format($item['price'],
                    2) . '</td>';
            $itemsHtml .= '</tr>';
        }
        $itemsHtml .= '</tbody></table>';
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