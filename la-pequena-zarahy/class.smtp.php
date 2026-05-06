<?php
/**
 * class.smtp.php - Mini motor SMTP para Hostinger
 */
class SimpleSMTP {
    public static function send($to, $subject, $body, $from, $pass, $fromName = "La Pequeña Zarahy") {
        $timeout = 30;
        $host = "ssl://smtp.hostinger.com";
        $port = 465;
        
        $socket = fsockopen($host, $port, $errno, $errstr, $timeout);
        if (!$socket) return false;

        $res = function($s) {
            $data = "";
            while($str = fgets($s, 515)) {
                $data .= $str;
                if(substr($str, 3, 1) == " ") break;
            }
            return $data;
        };

        $res($socket); // 220
        fwrite($socket, "EHLO " . $_SERVER['HTTP_HOST'] . "\r\n");
        $res($socket);
        
        fwrite($socket, "AUTH LOGIN\r\n");
        $res($socket);
        
        fwrite($socket, base64_encode($from) . "\r\n");
        $res($socket);
        
        fwrite($socket, base64_encode($pass) . "\r\n");
        $res($socket);
        
        fwrite($socket, "MAIL FROM: <$from>\r\n");
        $res($socket);
        
        fwrite($socket, "RCPT TO: <$to>\r\n");
        $res($socket);
        
        fwrite($socket, "DATA\r\n");
        $res($socket);
        
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: $fromName <$from>\r\n";
        $headers .= "To: <$to>\r\n";
        $headers .= "Subject: $subject\r\n";
        $headers .= "Date: " . date('r') . "\r\n";
        
        fwrite($socket, $headers . "\r\n" . $body . "\r\n.\r\n");
        $res($socket);
        
        fwrite($socket, "QUIT\r\n");
        fclose($socket);
        
        return true;
    }
}
