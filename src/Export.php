<?php
/**
 * Export Utility
 * 
 * Handles data export to CSV and JSON formats
 */

class Export {
    /**
     * Export data to CSV
     */
    public static function toCSV($data, $filename = 'export.csv', $download = true) {
        if (empty($data)) {
            return ['success' => false, 'error' => 'No data to export'];
        }

        $output = fopen('php://memory', 'r+');
        $headers = array_keys($data[0]);
        fputcsv($output, $headers);

        foreach ($data as $row) {
            fputcsv($output, array_values($row));
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        if ($download) {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($csv));
            echo $csv;
            exit;
        }

        return ['success' => true, 'data' => $csv];
    }

    /**
     * Export data to JSON
     */
    public static function toJSON($data, $filename = 'export.json', $download = true, $pretty = true) {
        $options = $pretty ? JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES : 0;
        $json = json_encode($data, $options);

        if ($download) {
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($json));
            echo $json;
            exit;
        }

        return ['success' => true, 'data' => $json];
    }

    /**
     * Export to XML
     */
    public static function toXML($data, $filename = 'export.xml', $download = true) {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><root></root>');
        self::arrayToXml($data, $xml);
        $xmlString = $xml->asXML();

        if ($download) {
            header('Content-Type: application/xml');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($xmlString));
            echo $xmlString;
            exit;
        }

        return ['success' => true, 'data' => $xmlString];
    }

    /**
     * Convert array to XML
     */
    private static function arrayToXml($data, &$xml) {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $subnode = $xml->addChild(is_numeric($key) ? 'item' : $key);
                self::arrayToXml($value, $subnode);
            } else {
                $xml->addChild(is_numeric($key) ? 'item' : $key, htmlspecialchars($value));
            }
        }
    }
}

?>
