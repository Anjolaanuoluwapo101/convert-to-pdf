<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Headers: Content-Type");

set_time_limit(300);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!isset($_FILES['document'])) {
    http_response_code(400);
    echo json_encode(["error" => "No file uploaded."]);
    exit;
  }

  $file = $_FILES['document'];
  $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
  $filename = uniqid('doc_') . "." . $ext;

  $uploadPath = __DIR__ . "/storage/uploads/$filename";
  move_uploaded_file($file['tmp_name'], $uploadPath);

  $statusPath = __DIR__ . "/storage/converted/{$filename}.status";
  file_put_contents($statusPath, 'queued');

  $outputPdf = __DIR__ . "/storage/converted/" . basename($filename, ".$ext") . ".pdf";

  // Use special filter for ppt/pptx
  $extLower = strtolower($ext);
  if ($extLower === 'ppt' || $extLower === 'pptx') {
    $convertCmd = "libreoffice --headless --convert-to pdf:impress_pdf_Export --outdir storage/converted '$uploadPath'";
  } else {
    $convertCmd = "libreoffice --headless --convert-to pdf --outdir storage/converted '$uploadPath'";
  }
  $cmd = "($convertCmd && echo 'done' > '$statusPath') > /dev/null 2>&1 &";
  shell_exec($cmd);

  // Detect if request is from cURL (or add ?curl=1 for manual override)
  $isCurl = (isset($_SERVER['HTTP_USER_AGENT']) && stripos($_SERVER['HTTP_USER_AGENT'], 'curl') !== false)
    || (isset($_GET['curl']) && $_GET['curl'] == '1');

  if ($isCurl) {
    // Wait for conversion to finish (max 30s)
    $waitTime = 0;
    while (!file_exists($outputPdf) && $waitTime < 30) {
      sleep(1);
      $waitTime++;
    }
    if (file_exists($outputPdf)) {
      header('Content-Type: application/pdf');
      header('Content-Disposition: attachment; filename="' . basename($outputPdf) . '"');
      header('Content-Length: ' . filesize($outputPdf));
      readfile($outputPdf);
      exit;
    } else {
      http_response_code(500);
      echo "Conversion timed out or failed.";
      exit;
    }
  }

  echo json_encode([
    "status_url" => "status.php?file=" . urlencode(basename($statusPath)),
    "file_url" => "download.php?file=" . urlencode(basename($outputPdf))
  ]);
  exit;
}
?>
<!DOCTYPE html>
<html>

<head>
  <title>Upload DOCX/PPTX</title>
</head>

<body>
  <form id="uploadForm" method="POST" enctype="multipart/form-data">
    <input type="file" name="document" required />
    <button type="submit">Convert</button>
  </form>
  <div id="downloadLink" style="margin-top:20px;"></div>
  <script>
    document.getElementById('uploadForm').addEventListener('submit', function(e) {
      e.preventDefault();
      const form = e.target;
      const data = new FormData(form);
      fetch('', { method: 'POST', body: data })
        .then(r => r.json())
        .then(data => {
          if (data.file_url) {
            // Show download link and auto-download
            const linkDiv = document.getElementById('downloadLink');
            linkDiv.innerHTML = `<a href="${data.file_url}" download>Download PDF</a>`;
            // Auto-download
            const a = document.createElement('a');
            a.href = data.file_url;
            a.download = '';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
          } else if (data.error) {
            alert(data.error);
          }
        });
    });
  </script>
</body>

</html>