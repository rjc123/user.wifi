<?php

class site
{
    public $radKey;
    public $kioskKey;
    public $name;
    public $org_id;
    public $org_name;
    public $id;
    public $activationRegex;
    public $activationDays;
    public $postcode;
    public $dataController;
    public $address;
    public $dailyCode;


    public function writeRecord() {
        $db = DB::getInstance();
        $dblink = $db->getConnection();
        $handle = $dblink->prepare('insert into site (id, radkey, kioskkey, datacontroller, address, postcode, activation_regex, activation_days, org_id)
         VALUES (:id, :radkey, :kioskkey, :datacontroller, :address, :postcode, :activation_regex, :activation_days, :org_id)
                on duplicate key update radkey=:radkey, kioskkey=:kioskkey, datacontroller=:datacontroller, address=:address
                ,postcode=:postcode, activation_regex=:activation_regex, activation_days=:activation_days, org_id = :org_id, dailycode=:dailycode');
        $handle->bindValue(':id', $this->id, PDO::PARAM_INT);
        $handle->bindValue(':radkey', $this->$radKey, PDO::PARAM_STR);
        $handle->bindValue(':kioskkey', $this->kioskKey, PDO::PARAM_STR);
        $handle->bindValue(':datacontroller', $this->dataController, PDO::PARAM_STR);
        $handle->bindValue(':address', $this->name, PDO::PARAM_STR); 
        $handle->bindValue(':postcode', $this->postcode, PDO::PARAM_STR); 
        $handle->bindValue(':activation_regex', $this->activationRegex, PDO::PARAM_STR); 
        $handle->bindValue(':activation_days', $this->activationDays, PDO::PARAM_STR); 
        $handle->bindValue(':org_id', $this->org_id, PDO::PARAM_INT); 
        $handle->bindValue(':dailycode', $this->dailyCode, PDO::PARAM_STR); 
        $handle->execute();
        if (!$this->id)
            $this->id = $dblink->lastInsertId();

    }

    public function getDailyCode() {
        if (!$this->dailyCode) {
            $config = config::getInstance();
            $length = $config->values['daily-code']['length'];
            $pattern = $config->values['daily-code']['regex'];
            $pass = preg_replace($pattern, "", base64_encode($this->strongRandomBytes($length * 4)));
            $this->dailyCode = substr($pass, 0, $length);
            $this->writeRecord();
        }

        return $this->dailyCode;
    }

    private function loadRow($row) {
        $this->name = $row['address'];
        $this->postcode = $row['postcode'];
        $this->org_id = $row['org_id'];
        $this->dataController = $row['datacontroller'];
        $this->activationRegex = $row['activation_regex'];
        $this->activationDays = $row['activation_days'];
        $this->id = $row['id'];
        $this->radKey = $row['radkey'];
        $this->kioskKey = $row['kioskkey'];
        $this->org_name = $row['name'];
        $this->dailyCode = $row['dailycode'];
    }

    public function updateFromEmail($emailBody) {
        foreach (preg_split("/((\r?\n)|(\r\n?))/", $emailBody) as $line)
        {
            $updated = FALSE;
            $line = trim($line);
            $parameter = strtolower(trim(substr($line, 0, strpos($line,":"))));
            $value = substr($line, strpos($line,":"));
    
            switch ($parameter) {
                case "postcode":
                $this->postcode = $value;
                $updated = TRUE;
                break;
                case "activationregex":
                $this->activationRegex = $value;
                $updated = TRUE;
                break;
                case "activationdays":
                $this->activationDays = $value;
                $updated = TRUE;
                break;
                case "datacontroller":
                $this->dataController = $value;
                $updated = TRUE;
                break;


            }
        }
        return $updated;
    }
    public function loadByKioskIp($ipAddr)
    {
        $db = DB::getInstance();
        $dblink = $db->getConnection();
        $handle = $dblink->prepare('select * from site, organisation, sourceip WHERE organisation.id = site.org_id and site.id=siteip.site_id and ? between sourceip.min and sourceip.max');
        $handle->bindValue(1, ip2long($ipAddr), PDO::PARAM_int);
        $handle->execute();
        $row = $handle->fetch(\PDO::FETCH_ASSOC);
        $this->loadRow($row);


    }

    public function loadByIp($ipAddr)
    {
        $db = DB::getInstance();
        $dblink = $db->getConnection();
        $handle = $dblink->prepare('select * from site, organisation, siteip WHERE organisation.id = site.org_id and site.id=siteip.site_id and siteip.ip = ?');
        $handle->bindValue(1, $ipAddr, PDO::PARAM_STR);
        $handle->execute();
        $row = $handle->fetch(\PDO::FETCH_ASSOC);
        $this->loadRow($row);
    }
    
    public function loadByAddress($address)
    {
        $db = DB::getInstance();
        $dblink = $db->getConnection();
        $handle = $dblink->prepare('select * from site, organisation WHERE organisation.id = site.org_id and site.address = ?');
        $handle->bindValue(1, $address, PDO::PARAM_STR);
        $handle->execute();
        $row = $handle->fetch(\PDO::FETCH_ASSOC);
        loadRow($row);
    }

    public function addIPs($iplist)
    {
        $db = DB::getInstance();
        $dblink = $db->getConnection();
        foreach ($iplist as $ip_addr)
        {
            $handle = $dblink->prepare('insert into siteip (ip, site_id) VALUES (?,?)');
            $handle->bindValue(1, $ip_addr, PDO::PARAM_STR);
            $handle->bindValue(2, $this->id, PDO::PARAM_INT);
            try
            {
                $handle->execute();
            }
            catch (PDOException $e)
            {
                // if it already exists the insert will fail, silently continue.
            }


        }

    }

    public function setRadkey()
    {
        $db = DB::getInstance();
        $dblink = $db->getConnection();
        $handle = $dblink->prepare('select secret, kioskkey from nas WHERE shortname=? and org_id=?');
        $handle->bindValue(1, $this->name, PDO::PARAM_STR);
        $handle->bindValue(2, $this->org_id, PDO::PARAM_INT);
        $handle->execute();
        $row = $handle->fetch(\PDO::FETCH_ASSOC);
        if ($row)
            {
            $this->radKey = $row['secret'];
            $this->kioskKey = $row['kioskkey'];
            }
        else
            {
            $this->generateRandomRadKey();
            $this->generateRandomKioskKey();
            }
    }

    private function generateRandomRadKey()
    {
        $config = config::getInstance();
        $length = $config->values['radius-password']['length'];
        $pattern = $config->values['radius-password']['regex'];
        $pass = preg_replace($pattern, "", base64_encode($this->strongRandomBytes($length *
            4)));
        $this->radKey = substr($pass, 0, $length);
    }
    private function generateRandomKioskKey()
    {
        $config = config::getInstance();
        $length = $config->values['kiosk-password']['length'];
        $pattern = $config->values['kiosk-password']['regex'];
        $pass = preg_replace($pattern, "", base64_encode($this->strongRandomBytes($length *
            4)));
        $this->kioskKey = substr($pass, 0, $length);
    }
    private function strongRandomBytes($length)
    {
        $strong = false; // Flag for whether a strong algorithm was used
        $bytes = openssl_random_pseudo_bytes($length, $strong);

        if (!$strong)
        {
            // System did not use a cryptographically strong algorithm
            throw new Exception('Strong algorithm not available for PRNG.');
        }

        return $bytes;
    }


}

?>
