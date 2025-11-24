<?php
/**
 * Chart to Image Converter
 * Converts Chart.js charts to base64-encoded PNG images for PDF embedding
 * 
 * This script uses a headless browser (Puppeteer via Node.js or similar) to render
 * Chart.js canvases and convert them to PNG images.
 * 
 * Usage:
 * $chartImage = chartToImage($chartHtml, $canvasId, $width, $height);
 */

/**
 * Convert a Chart.js canvas to a base64-encoded PNG image
 * 
 * @param string $chartHtml HTML containing the chart canvas and Chart.js script
 * @param string $canvasId The ID of the canvas element
 * @param int $width Width of the image in pixels
 * @param int $height Height of the image in pixels
 * @return string Base64-encoded PNG image data (data:image/png;base64,...)
 */
function chartToImage($chartHtml, $canvasId, $width = 1200, $height = 600) {
    // Create a temporary HTML file with the chart
    $tempDir = sys_get_temp_dir();
    $tempHtmlFile = $tempDir . '/chart_' . uniqid() . '.html';
    $tempImageFile = $tempDir . '/chart_' . uniqid() . '.png';
    
    // Build the HTML with dimensions
    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
    <style>
        body { margin: 0; padding: 20px; background: white; }
        canvas { max-width: 100%; }
    </style>
</head>
<body>
' . $chartHtml . '
<script>
// Give charts time to render
setTimeout(function() {
    // Use html2canvas to capture the chart
    var canvas = document.getElementById("' . $canvasId . '");
    if (canvas) {
        var ctx = canvas.getContext("2d");
        // The chart is already rendered, just convert to PNG
    }
}, 500);
</script>
</body>
</html>';
    
    file_put_contents($tempHtmlFile, $html);
    
    // Use wkhtmltoimage or similar tool if available
    // For now, return a placeholder that will be replaced by client-side capture
    
    @unlink($tempHtmlFile);
    
    return null; // Will implement using client-side approach
}

/**
 * Generate JavaScript code to capture Chart.js and send to server
 * This is a helper for the AJAX approach
 * 
 * @param string $chartCanvasId The canvas ID to capture
 * @param string $targetInput The input field ID where base64 data will be stored
 * @return string JavaScript code
 */
function getChartCaptureJs($chartCanvasId, $targetInput) {
    return "
    (function() {
        // Wait for chart to render
        setTimeout(function() {
            const canvas = document.getElementById('" . $chartCanvasId . "');
            if (canvas) {
                const imageData = canvas.toDataURL('image/png');
                document.getElementById('" . $targetInput . "').value = imageData;
            }
        }, 500);
    })();
    ";
}

/**
 * Embed a base64 image in HTML/PDF
 * 
 * @param string $base64Data Base64-encoded image data (without data:image/png;base64, prefix)
 * @param int $width Width in pixels
 * @param int $height Height in pixels
 * @return string HTML img tag
 */
function embedBase64Image($base64Data, $width = 1200, $height = 600) {
    if (strpos($base64Data, 'data:') === false) {
        $base64Data = 'data:image/png;base64,' . $base64Data;
    }
    
    return '<img src="' . $base64Data . '" style="width: ' . $width . 'px; height: ' . $height . 'px; max-width: 100%;" />';
}

?>
