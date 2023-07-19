<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Laravel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <!-- Fonts -->
    <link href="https://fonts.bunny.net/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- Styles -->

    <style>

    </style>


</head>

<body class="antialiased">
    <!-- Input element to select Excel file -->
    <div class="container">
        <h2>Test : fill in an excel file</h2>
        <div class="row">
            <div class="col-10">
                <input class="form-control form-control-lg" id="excelFileInput" type="file">

            </div>
            <div class="col-2">
                <!-- Button to trigger the process -->
                <button onclick="processExcelFile()">Process Excel File</button>
            </div>
        </div>
        <br />
        <div class="progress">
            <div class="progress-bar" id="progress" role="progressbar" aria-valuenow="60" aria-valuemin="0"
                aria-valuemax="100" style="width: 0%;">
                <span class="sr-only">0%</span>
            </div>
        </div>
    </div>





    <script>
    function processExcelFile() {
        const fileInput = document.getElementById("excelFileInput");
        const selectedFile = fileInput.files[0];

        if (!selectedFile) {
            alert("Please select an Excel file.");
            return;
        }

        fillDistancesFromOverpass(selectedFile);
    }
    </script>

    <!-- Include necessary libraries -->
    <!-- SheetJS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.16.9/xlsx.full.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Axios -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    <!-- Latest compiled and minified JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous">
    </script>

    <script>
    async function fillDistancesFromOverpass(file) {
        const reader = new FileReader();

        reader.onload = async function(e) {
            const data = e.target.result;
            const workbook = XLSX.read(data, {
                type: "binary"
            });
            const sheetName = workbook.SheetNames[0];
            const worksheet = workbook.Sheets[sheetName];
            const range = XLSX.utils.decode_range(worksheet['!ref']);

            // Function to fetch surrounding zip codes using Overpass API
            async function fetchSurroundingZipCodes(zipCode, radius) {
                const apiUrl = `http://overpass-api.de/api/interpreter`;
                const query =
                    `[out:json];rel[boundary=postal_code][postal_code='${zipCode}'];rel(around:${radius * 1000})[boundary=postal_code][postal_code];out;`;

                try {
                    const response = await axios.get(apiUrl, {
                        params: {
                            data: query
                        }
                    });
                    return response.data.elements.map((element) => element.tags.postal_code).join(", ");
                } catch (error) {
                    console.error(`Error while fetching data for zip code ${zipCode} at ${radius}km:`,
                        error);
                    return "Error";
                }
            }

            // Loop through each row in the Excel file
            for (let row = range.s.r + 1; row <= range.e.r; row++) {
                const zipCode = worksheet[`C${row}`]?.v; // Assuming zip code is in column C

                if (zipCode && !isNaN(zipCode)) {
                    console.log(zipCode)
                    const distances = [3, 5, 10]; // Distances to query

                    // Array to store all the async API requests
                    const apiRequests = distances.map(radius => fetchSurroundingZipCodes(zipCode, radius));

                    // Wait for all API requests to complete
                    const results = await Promise.all(apiRequests);

                    // Fill the corresponding columns with the surrounding zip codes
                    worksheet[`E${row}`] = {
                        t: "s",
                        v: results[0],
                        w: results[0]
                    };
                    worksheet[`F${row}`] = {
                        t: "s",
                        v: results[1],
                        w: results[1]
                    };
                    worksheet[`G${row}`] = {
                        t: "s",
                        v: results[2],
                        w: results[2]
                    };
                    const progress = ((row - range.s.r) / (range.e.r - range.s.r)) * 100;
                    updateProgress(progress);
                }
            }
            updateProgress(100);
            // Save the modified workbook to a new Excel file
            XLSX.writeFile(workbook, "path/to/your/output/file.xlsx");
        };

        reader.readAsBinaryString(file);
    }

    function updateProgress(progress) {
        const progressBar = $("#progress");
        progressBar.css("width", `${progress}%`);
        progressBar.html(`${progress}%`);

    }
    </script>

</body>

</html>