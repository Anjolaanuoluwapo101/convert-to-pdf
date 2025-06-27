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
    $cmd = "(libreoffice --headless --convert-to pdf --outdir storage/converted '$uploadPath' && echo 'done' > '$statusPath') > /dev/null 2>&1 &";
    shell_exec($cmd);

    echo json_encode([
        "status_url" => "status.php?file=" . urlencode(basename($statusPath)),
        "file_url" => "storage/converted/" . basename($outputPdf)
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
  <form method="POST" enctype="multipart/form-data">
    <input type="file" name="document" required />
    <button type="submit">Convert</button>
  </form>
</body>
</html>
