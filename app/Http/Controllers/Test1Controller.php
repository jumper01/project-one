<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ZipCodesExport;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

ini_set('max_execution_time', 1000); // Set the maximum execution time to 120 seconds (2 minutes)


class Test1Controller extends Controller
{
    // using overpassApi

    public function getSurroundingZipCodesOverpassApi($zipCode, $radius)
    {
        $surroundingZipCodes = $this->getSurroundingZipCodesFromOverpassAPI($zipCode, $radius);
        if ($surroundingZipCodes) {
            return response()->json($surroundingZipCodes);
        } else {
            return response()->json(['error' => 'Zip code not found'], 404);
        }
    }

    private function getSurroundingZipCodesFromOverpassAPI($zipCode, $radius)
    {
        $radius = $radius * 1000; // 5,10,15 km radius
        $baseUrl = 'https://overpass-api.de/api/';
        $query = "[out:json];rel[boundary=postal_code][postal_code='{$zipCode}'];rel(around:{$radius})[boundary=postal_code][postal_code];out;";
        $endpoint = 'interpreter';

        $client = new Client();
        $response = $client->get("{$baseUrl}{$endpoint}", [
            'query' => [
                'data' => $query,
            ],
        ]);

        $data = json_decode($response->getBody(), true);
        $zipCodes = [];

        foreach ($data['elements'] as $element) {
            if (isset($element['tags']['postal_code'])) {
                $zipCodes[] = $element['tags']['postal_code'];
            }
        }
        return array_values($zipCodes);



    }

    public function batchApiRequests()
    {
        $baseUrl = 'https://overpass-api.de/api/';
        $batchQueryParts = [];

        $radius_=[3,5,10];
        $zipCodes_=[40212,60311];
        $i=0;
        foreach ($zipCodes_ as $zipCode) {
            for($r=0;$r<=2;$r++) {
                $radius = $radius_[$r] * 1000; // Convert km to meters

                // Add each query for the current zip code and radius to the $batchQueryParts array
                if($i==0) {
                    $query = "[out:json];(rel[boundary=postal_code][postal_code='{$zipCode}'];rel(around:{$radius})[boundary=postal_code][postal_code]);";
                } else {
                    $query = "(rel[boundary=postal_code][postal_code='{$zipCode}'];rel(around:{$radius})[boundary=postal_code][postal_code];);";
                }
                $batchQueryParts[] = $query;
                $i++;

            }
        }
        $batchQuery = implode('union', $batchQueryParts) . 'out;';
        dd($batchQuery);
        $endpoint = 'interpreter';
        $client = new Client();
        $response = $client->get("{$baseUrl}{$endpoint}", [
            'query' => [
                'data' => $batchQuery,
            ],
        ]);

        $data = json_decode($response->getBody(), true);
        dd($data);
        // Process the data and return the results as needed
        // ...
    }

    public function fillZipCodesFromExcel(Request $request)
    {
        $file = $request->file('excelFile');

        if (!$file) {
            return response()->json(['error' => 'No file uploaded'], 400);
        }

        $filePath = $file->getPathname();
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $i=0;
        foreach ($worksheet->getRowIterator(2) as $row) {

            $zipCode = $worksheet->getCell('C' . $row->getRowIndex())->getValue();
            $radiusColumns = ['E', 'F', 'G', 'H', 'I'];
            $i++;
            foreach ($radiusColumns as $index => $column) {
                $radiusString = $worksheet->getCell($column . '1')->getValue();
                $radius = intval(str_replace('km', '', $radiusString));
                $surroundingZipCodes = $this->getSurroundingZipCodesFromOverpassAPI($zipCode, $radius);
                $combinedZipCodes = implode(', ', $surroundingZipCodes);
                $worksheet->setCellValue($column . $row->getRowIndex(), $combinedZipCodes);

            }

        }

        //Save the updated data back to the Excel file

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $tempFile = tempnam(sys_get_temp_dir(), 'updated_excel_');
        $writer->save($tempFile);

        // Download the updated Excel file
        $fileName = 'updated_file.xlsx';
        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];

        return response()->download($tempFile, $fileName, $headers)->deleteFileAfterSend(true);
    }

}