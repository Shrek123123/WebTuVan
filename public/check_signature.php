    <?php
    $payosConfig = require __DIR__ . '/../config/payos.php';
    $checksum_key = $payosConfig['checksum_key'];
      $webhookData = array(
          "code" => "00",
          "desc" => "success",
          "success" => true,
          "data" => array(
              "orderCode"           => 123,
              "amount"              => 3000,
              "description"         => "VQRIO123",
              "accountNumber"       => "12345678",
              "reference"           => "TF230204212323",
              "transactionDateTime" => "2023-02-04 18:25:00",
              "currency"            => "VND",
              "paymentLinkId"       => "124c33293c43417ab7879e14c8d9eb18",
              "code"                => "00",
              "desc"                => "Thành công",
              "counterAccountBankId"    => "",
              "counterAccountBankName"  => "",
              "counterAccountName"      => "",
              "counterAccountNumber"    => "",
              "virtualAccountName"      => "",
              "virtualAccountNumber"    => ""
          ),
          "signature"             => "412e915d2871504ed31be63c8f62a149a4410d34c4c42affc9006ef9917eaa03"
      );

      function isValidData($transaction, $transaction_signature, $checksum_key)
      {
          ksort($transaction);
          $transaction_str_arr = [];
          foreach ($transaction as $key => $value) {
              if (in_array($value, ["undefined", "null"]) || gettype($value) == "NULL") {
                  $value = "";
              }

              if (is_array($value)) {
                  $valueSortedElementObj = array_map(function ($ele) {
                      ksort($ele);
                      return $ele;
                  }, $value);
                  $value = json_encode($valueSortedElementObj, JSON_UNESCAPED_UNICODE);
              }
              $transaction_str_arr[] = $key . "=" . $value;
          }
          $transaction_str = implode("&", $transaction_str_arr);
          dump($transaction_str);
          $signature = hash_hmac("sha256", $transaction_str, $checksum_key);
          dump($signature);
          return $signature == $transaction_signature;
      }

      isValidData($webhookData['data'], $webhookData['signature'], $checksum_key);