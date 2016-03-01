<?php


class PDF
{
    public $filename;
    public $subject;
    public $message;
    public $landscape;
    public $password;

    public function populateNewsite($site)
    {
         $config = config::getInstance();
        $this->message = file_get_contents($config->values['pdf-contents']['newsite-file']);
        $this->message = str_replace("%ORG%", $site->org_name, $this->message);
        $this->message = str_replace("%RADKEY%", $site->radkey, $this->message);
        $this->message = str_replace("%DESCRIPTION%", $site->name, $this->message);
        $this->filename = $site->org_name . "-" . $site->name;
        $this->filename = preg_replace("/[^a-zA-Z0-9]/", "_", $this->filename);
        $this->filename .= ".pdf";
        $this->filename = $config->values['pdftemp-path'] . $this->filename;
        $this->subject = $config->values['email-messages']['newsite-subject'];
    }

    public function populateLogrequest($org_admin)
    {
         $config = config::getInstance();
        $this->filename = date("Ymd") . $org_admin->org_name . "-" . $org_admin->name .
            "-Logs";
        $this->filename = preg_replace("/[^a-zA-Z0-9]/", "_", $this->filename);
        $this->filename .= ".pdf";
        $this->filename = $config->values['pdftemp-path'] . $this->filename;
        $this->subject = "Generated on: " . date("d-m-Y") . " Requestor: " . $org_admin->
            name;
    }

    public function generatePDF($handle = null)
    {
        // Generate PDF with the site details
        // Encrypts the file then returns the password
        $un_filename = $this->filename . "-unencrypted";
        if ($self->landscape)
            $pdf = new FPDF("L");
        else
            $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Courier', 'B', 16);
        $pdf->Cell(40, 10, 'user.wifi Service');
        $pdf->Ln(20);
        $pdf->Cell(80, 10, $subject);
        $pdf->Ln(20);
        $pdf->SetFont('Arial', '', 12);
        // Write Body

        foreach (preg_split("/((\r?\n)|(\r\n?))/", $message) as $line) {
            if ($line == "%TABLE%")
                $this->PdfSqlTable($pdf, $handle);
            else
                $pdf->Write(5, $line . "\n");
        }
        $pdf->Output($un_filename);
        $this->encryptPdf($un_filename);
    }

    private function encryptPdf($filename)
    {
        $self->password = generate_random_pdf_password();
        exec("/usr/bin/qpdf --encrypt " . $self->password . " - 256 -- " . $filename .
            " " . $self->filename);
        unlink($filename);
    }

    private function PdfSqlTable($pdf, $handle)
    {
        global $dblink;
        $handle->execute();
        $result = $handle->fetchAll(\PDO::FETCH_NUM);
        $totalrows = 0;
        $w = array(
            0,
            0,
            0,
            0,
            0,
            0,
            0,
            0,
            0,
            0,
            0,
            0,
            0);

        foreach ($result as $row[$totalrows]) {
            $column = 0;

            while (isset($row[$totalrows][$column])) {
                $collength = strlen($row[$totalrows][$column]);
                if ($w[$column] < $collength)
                    $w[$column] = $collength * 4;
                $column++;
            }
            $totalrows++;
        }
        for ($rownum = 0; $rownum <= $totalrows; $rownum++) {
            $column = 0;

            while (isset($row[$rownum][$column])) {
                $pdf->Cell($w[$column], 6, $row[$rownum][$column], 1, 0, 'C');
                $column++;
            }
            $pdf->Ln();
        }
    }

    private function GenerateRandomPdfPassword()
    {
         $config = config::getInstance();
        $length = $config->values['pdf-password']['length'];
        $pattern = $config->values['pdf-password']['regex'];
        $pass = preg_replace($pattern, "", base64_encode(strong_random_bytes($length * 4)));
        return substr($pass, 0, $length);
    }
}

?>
