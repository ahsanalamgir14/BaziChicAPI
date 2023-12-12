<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Helper
{
    public function checkConfigurations(Request $request, Response $response)
    {
        return $response->withRedirect('/coming-soon');
    }

    public function isMaintenanceModeOn()
    {
        require_once "dbmodels/site_settings.crud.php";
        $settingsCRUD = new SiteSettingsCRUD(getConnection());
        if ($settingsCRUD->isMaintenanceModeOn()) {
            return true;
        } else {
            return false;
        }
    }

    public function canMembersAuthor()
    {
        $id = 1;
        $stmt = $this->db->prepare("SELECT allow_member_authoring FROM site_settings WHERE id=:id");
        $stmt->execute(array(":id" => $id));
        $result = $stmt->fetchColumn();
        return $result;
    }

    public function sendEmail($to, $subject, $body)
    {
        require "php-mailer/PHPMailerAutoload.php";
        $name = "Bazichic Chinese Metaphysics Consultancy";
        $from = "no-reply@bazichic.com";
        $headers = array("From: $from",
            "Reply-To: $from",
            "X-Mailer: PHP/" . PHP_VERSION,
        );
        $headers = implode("\r\n", $headers);
        $response = array();
        try {
            $mail = new PHPMailer();
            $mail->IsHTML(true);
            $mail->SetFrom($from);
            $mail->From = $from;
            $mail->FromName = "Bazichic Chinese Metaphysics Consultancy";
            $mail->AddAddress($to);
            $mail->Subject = $subject;
            
            //$mail->SMTPDebug = 2;
            $mail->isSMTP();
            $mail->SMTPAuth = true;
            $mail->Host = 'bazichic.com';
            $mail->Username = 'no-reply@bazichic.com';
            $mail->Password = "xwit;!@?bW?~";
            $mail->SMTPSecure = 'tls';
            //$mail->SMTPSecure = 'ssl';
            $mail->Port = 587; //465
            //Other details
            $mail->Body = $body;
            $mail->AltBody = "This is an automated e-mail from BaziChic Chinese Metaphysics Consultancy.";
            $mail->addReplyTo($from, "Reply");

            if ($mail->Send()) {
                $response["error"] = false;
                $response["message"] = "Mail sent successfully.";
            } else {
                $response["error"] = true;
                $response["message"] = "Mail failed to sent.";
            }
        } catch (phpmailerException $e) {
            $response["error"] = true;
            $response["message"] = "phpmailerException: ".$e->getMessage();
            //$response["message"] = "Oops! Failed to send mail. Please try again.";
            return $response;
        }
        return $response;
    }

}
