<?php

class emailResponse
{
    public $from;
    public $to;
    public $subject;
    public $message;
    public $filename;
    public $filepath;

    public function __construct()
    {
        $config = config::getInstance();
        $this->from = $config->values['email-noreply'];

    }

    public function sponsor($count)
    {
        $config = config::getInstance();
        $this->subject = $config->values['email-messages']['sponsor-subject'];
        if ($count>0) {
            $this->message = file_get_contents($config->values['email-messages']['sponsor-file']);
            $this->message = str_replace("%X%", $count, $this->message);
        }
        else
        {
            $this->message = file_get_contents($config->values['email-messages']['sponsor-help-file']);   
        }
    }

    public function newsite($action,$outcome,$site)
    {
        $config = config::getInstance();
        $this->from = $config->values['email-newsitereply'];
        $this->subject = $site->name;
        $this->message = file_get_contents($config->values['email-messages']['newsite-file']);
        $this->message = str_replace("%OUTCOME%", $outcome, $this->message);
        $this->message = str_replace("%ACTION%", $action, $this->message);
        $this->message = str_replace("%NAME%", $site->name, $this->message);
        $this->message = str_replace("%ATTRIBUTES%", $site->attributesText(), $this->message);
    }
    
    public function newSiteBlank($site)
    {
        $config = config::getInstance();
        $this->subject = $site->name;
        $this->message = file_get_contents($config->values['email-messages']['newsite-help-file']);
    }
    public function verify($code)
    {
        $config = config::getInstance();
        $this->subject = $config->values['email-messages']['verify-subject'];
        $this->message = file_get_contents($config->values['email-messages']['verify-file']);
        $this->message = str_replace("%CODE%", $code, $this->message);
        $this->message = str_replace("%LONGNUMBER%", $config->values['reply-sender'], $this->message);
        $this->message = str_replace("%SHORTNUMBER%", $config->values['shortnumber'], $this->message);

    }
    public function enroll($user)
    {
        $config = config::getInstance();
        $this->subject = $config->values['email-messages']['enrollment-subject'];
        $this->message = file_get_contents($config->values['email-messages']['enrollment-file']);
        $this->message = str_replace("%LOGIN%", $user->login, $this->message);
        $this->message = str_replace("%PASS%", $user->password, $this->message);
        $this->message = str_replace("%SPONSOR%", $user->sponsor->text, $this->message);
        $this->message = str_replace("%THUMBPRINT%", $config->values['radcert-thumbprint'],
            $this->message);
        $this->send();
    }

    public function logrequest()
    {
        $config = config::getInstance();
        $this->subject = $config->values['email-messages']['logrequest-subject'];
        $this->message = file_get_contents($config->values['email-messages']['logrequest-file']);
    }

    public function send()
    {
        $config = config::getInstance();
        $provider = 1;
        $success = false;

        while ($success == false and isset($config->values['email-provider' . $provider]))
        {
            $success = $this->tryEmailProvider($provider);
            $provider++;
        }
    }

    function tryPostMark($provider)
    {
        $config = config::getInstance();
        $conf_index = 'email-provider' . $provider;
        $attachments = array();
        $data = array(
            'From' => $this->from,
            'To' => $this->to,
            'Subject' => $this->subject,
            'TextBody' => $this->message);
            
        if ($this->filename != "")
        {
            $attachments = array(
                'Name' => $this->filename,
                'Content' => base64_encode(file_get_contents($this->filepath)),
                'ContentType' => 'application/octet-stream');
            $data['Attachments'][]= $attachments;
                    }
                    
        $json = json_encode($data);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $config->values[$conf_index]['url']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: application/json',
            'Content-Type: application/json',
            'X-Postmark-Server-Token: ' . $config->values[$conf_index]['key']));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        $response = json_decode(curl_exec($ch), true);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $http_code === 200;

    }

    function tryMailGun($provider)
    {
        $config = config::getInstance();
        $conf_index = 'email-provider' . $provider;
        $key = $config->values[$conf_index]['key'];
        $data['text'] = $this->message;
        $data['from'] = $this->from;
        $data['to'] = $this->to;
        $data['subject'] = $this->subject;
        if ($this->filename != "")
        {
            $data['attachment'][1] = new CURLFile($this->filepath);
        }
        $ch = curl_init($config->values[$conf_index]['url']);
        curl_setopt($ch, CURLOPT_USERPWD, $config->values[$conf_index]['user'] . ":" . $config->values[$conf_index]['key']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        // This is the result from the API
        curl_close($ch);
        if (preg_match('/success', $result))
            return true;
        else
            return false;
    }

    function tryEmailProvider($provider)
    {
        $config = config::getInstance();
        $conf_index = 'email-provider' . $provider;
        $success = false;
        if ($config->values[$conf_index]['enabled'])
        {
            switch ($config->values[$conf_index]['provider'])
            {
                case "postmark":
                    $success = $this->tryPostMark($provider);
                    break;
                case "mailgun":
                    $success = $this->tryMailGun($provider);
                    break;
            }

        }
        return $success;
    }

}

?>
