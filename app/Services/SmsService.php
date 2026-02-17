<?php

namespace App\Services;

class SmsService
{
    private $username = 'emcatechn';
    private $password = 'Emca@#12';
    private $from = 'MauzoLink';
    private $baseUrl = 'https://messaging-service.co.tz/link/sms/v1/text/single';

    /**
     * Send SMS
     *
     * @param string $phoneNumber Phone number (should start with 255)
     * @param string $message Message to send
     * @return array
     */
    public function sendSms($phoneNumber, $message)
    {
        // Ensure phone number starts with 255
        $phone_no = $this->formatPhoneNumber($phoneNumber);
        
        $text = urlencode($message);
        $url = $this->baseUrl . '?username=' . $this->username . '&password=' . urlencode($this->password) . '&from=' . $this->from . '&to=' . $phone_no . '&text=' . $text;

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error) {
            return [
                'success' => false,
                'error' => 'cURL Error: ' . $error,
                'response' => null
            ];
        }

        return [
            'success' => $httpCode == 200,
            'response' => $response,
            'http_code' => $httpCode
        ];
    }

    /**
     * Format phone number to start with 255
     *
     * @param string $phoneNumber
     * @return string
     */
    private function formatPhoneNumber($phoneNumber)
    {
        // Remove all non-digit characters
        $phone = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // If starts with 0, replace with 255
        if (substr($phone, 0, 1) == '0') {
            $phone = '255' . substr($phone, 1);
        }
        
        // If doesn't start with 255, add it
        if (substr($phone, 0, 3) != '255') {
            $phone = '255' . $phone;
        }
        
        return $phone;
    }
}












