<?php
// Temporary file to check PHP configuration
echo "<h2>PHP Upload Configuration</h2>";
echo "<p><strong>upload_max_filesize:</strong> " . ini_get('upload_max_filesize') . "</p>";
echo "<p><strong>post_max_size:</strong> " . ini_get('post_max_size') . "</p>";
echo "<p><strong>max_execution_time:</strong> " . ini_get('max_execution_time') . "</p>";
echo "<p><strong>max_input_time:</strong> " . ini_get('max_input_time') . "</p>";
echo "<p><strong>memory_limit:</strong> " . ini_get('memory_limit') . "</p>";
echo "<p><strong>max_file_uploads:</strong> " . ini_get('max_file_uploads') . "</p>";

echo "<h2>Upload Error Codes Reference</h2>";
echo "<ul>";
echo "<li><strong>UPLOAD_ERR_OK (0):</strong> No error</li>";
echo "<li><strong>UPLOAD_ERR_INI_SIZE (1):</strong> File exceeds upload_max_filesize</li>";
echo "<li><strong>UPLOAD_ERR_FORM_SIZE (2):</strong> File exceeds MAX_FILE_SIZE directive</li>";
echo "<li><strong>UPLOAD_ERR_PARTIAL (3):</strong> File was only partially uploaded</li>";
echo "<li><strong>UPLOAD_ERR_NO_FILE (4):</strong> No file was uploaded</li>";
echo "<li><strong>UPLOAD_ERR_NO_TMP_DIR (6):</strong> Missing temporary folder</li>";
echo "<li><strong>UPLOAD_ERR_CANT_WRITE (7):</strong> Failed to write file to disk</li>";
echo "<li><strong>UPLOAD_ERR_EXTENSION (8):</strong> PHP extension stopped upload</li>";
echo "</ul>";

echo "<h2>Current Request Info</h2>";
if (!empty($_FILES)) {
    echo "<pre>";
    print_r($_FILES);
    echo "</pre>";
} else {
    echo "<p>No files uploaded in this request</p>";
}
?>