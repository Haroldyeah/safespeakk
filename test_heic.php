<?php
// Debug test for HEIC/HEIF uploads
// Create this as a separate file (e.g., test_heic.php) to debug the issue

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_file'])) {
    echo "<h3>Debug Information:</h3>";
    
    $file = $_FILES['test_file'];
    
    // Display all file information
    echo "<h4>File Information:</h4>";
    echo "Name: " . $file['name'] . "<br>";
    echo "Type (browser detected): " . $file['type'] . "<br>";
    echo "Size: " . $file['size'] . " bytes<br>";
    echo "Error code: " . $file['error'] . "<br>";
    echo "Temp name: " . $file['tmp_name'] . "<br>";
    
    // Get file extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    echo "Extension: " . $extension . "<br>";
    
    // Check if file exists
    if (file_exists($file['tmp_name'])) {
        echo "Temp file exists: YES<br>";
        
        // Get MIME type using finfo
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            echo "MIME type (finfo): " . $mimeType . "<br>";
        } else {
            echo "finfo_open function not available<br>";
        }
        
        // Read file header (first 32 bytes)
        $handle = fopen($file['tmp_name'], 'rb');
        if ($handle) {
            $header = fread($handle, 32);
            fclose($handle);
            echo "File header (hex): " . bin2hex($header) . "<br>";
            echo "File header (readable): " . $header . "<br>";
            
            // Check for HEIC/HEIF signatures
            $signatures = [
                'ftypheic' => 'HEIC image',
                'ftypmif1' => 'HEIF image', 
                'ftypheix' => 'HEIC sequence',
                'ftyphevc' => 'HEVC-based HEIC',
                'ftypheim' => 'HEIF sequence'
            ];
            
            $fileSignature = substr($header, 4, 8);
            echo "File signature at offset 4: " . $fileSignature . "<br>";
            
            $isHeicHeif = false;
            foreach ($signatures as $sig => $desc) {
                if (strpos($fileSignature, substr($sig, 0, 4)) === 0) {
                    echo "Detected: " . $desc . "<br>";
                    $isHeicHeif = true;
                    break;
                }
            }
            
            if (!$isHeicHeif) {
                echo "No HEIC/HEIF signature detected<br>";
            }
        }
        
    } else {
        echo "Temp file exists: NO<br>";
    }
    
    // Test the upload function
    echo "<h4>Upload Function Test:</h4>";
    
    // Make sure upload directory exists
    $uploadDir = 'test_uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Simple upload test function
    function testUploadFile($file, $uploadDir = 'test_uploads/') {
        if (!isset($file['error']) || is_array($file['error'])) {
            return ['success' => false, 'error' => 'Invalid file upload'];
        }
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Upload error code: ' . $file['error']];
        }
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Check file type using MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        // Extended MIME type mapping with HEIC/HEIF support
        $allowedMimeTypes = [
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'gif' => ['image/gif'],
            'heic' => [
                'image/heic', 
                'image/heic-sequence', 
                'image/heif',
                'image/heif-sequence',
                'application/octet-stream' // Fallback
            ],
            'heif' => [
                'image/heif', 
                'image/heif-sequence', 
                'image/heic',
                'image/heic-sequence',
                'application/octet-stream' // Fallback
            ],
        ];

        // Special handling for HEIC/HEIF files
        if (in_array($extension, ['heic', 'heif'])) {
            // Check file signature
            $fileHandle = fopen($file['tmp_name'], 'rb');
            if ($fileHandle) {
                $header = fread($fileHandle, 12);
                fclose($fileHandle);
                
                $isHeicHeif = false;
                if (strlen($header) >= 12) {
                    $signatures = [
                        'ftypheic',
                        'ftypmif1', 
                        'ftypheix',
                        'ftyphevc',
                        'ftypheim'
                    ];
                    
                    $fileSignature = substr($header, 4, 8);
                    foreach ($signatures as $signature) {
                        if (strpos($fileSignature, substr($signature, 0, 4)) === 0) {
                            $isHeicHeif = true;
                            break;
                        }
                    }
                }
                
                if (!$isHeicHeif && !in_array($mimeType, $allowedMimeTypes[$extension])) {
                    return ['success' => false, 'error' => 'Invalid HEIC/HEIF file format. MIME: ' . $mimeType];
                }
            }
        } else {
            if (!isset($allowedMimeTypes[$extension]) || !in_array($mimeType, $allowedMimeTypes[$extension])) {
                $expectedTypes = isset($allowedMimeTypes[$extension]) ? implode(' or ', $allowedMimeTypes[$extension]) : 'N/A';
                return ['success' => false, 'error' => 'Invalid file type. MIME: ' . $mimeType . '. Expected: ' . $expectedTypes];
            }
        }
        
        // Generate unique filename
        $fileName = uniqid() . '_' . $file['name'];
        $localPath = $uploadDir . $fileName;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $localPath)) {
            return ['success' => false, 'error' => 'Failed to move uploaded file'];
        }

        return [
            'success' => true,
            'file_name' => $fileName,
            'file_path' => $localPath,
            'file_size' => $file['size']
        ];
    }
    
    $result = testUploadFile($file);
    
    if ($result['success']) {
        echo "<div style='color: green;'>✓ Upload SUCCESS!</div>";
        echo "File saved as: " . $result['file_name'] . "<br>";
        echo "File path: " . $result['file_path'] . "<br>";
    } else {
        echo "<div style='color: red;'>✗ Upload FAILED!</div>";
        echo "Error: " . $result['error'] . "<br>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>HEIC/HEIF Upload Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 600px; margin: 0 auto; }
        .debug-info { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }
        input[type="file"] { margin: 10px 0; }
        button { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #005a87; }
    </style>
</head>
<body>
    <div class="container">
        <h1>HEIC/HEIF Upload Debug Test</h1>
        
        <form method="POST" enctype="multipart/form-data">
            <h3>Select a HEIC/HEIF file to test:</h3>
            <input type="file" name="test_file" accept=".heic,.heif,.jpg,.jpeg,.png" required>
            <br><br>
            <button type="submit">Test Upload</button>
        </form>
        
        <div class="debug-info">
            <h4>Instructions:</h4>
            <ol>
                <li>Save this code as a separate PHP file (e.g., test_heic.php)</li>
                <li>Place it in your web directory</li>
                <li>Try uploading a HEIC/HEIF file</li>
                <li>Check the debug information to see what's happening</li>
            </ol>
        </div>
        
        <div class="debug-info">
            <h4>What this test shows:</h4>
            <ul>
                <li>Actual file information received by PHP</li>
                <li>MIME type detection</li>
                <li>File signature analysis</li>
                <li>Whether the upload function accepts the file</li>
            </ul>
        </div>
    </div>
</body>
</html>