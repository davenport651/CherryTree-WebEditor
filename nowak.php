<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Configuration
$NOTES_DIR = '/var/www/domain.tld/notes/';
$INDEX_FILE = $NOTES_DIR . 'index.html';

// Helper function to get all note files
function getAllNotes($dir) {
    $notes = [];
    $files = glob($dir . '*.html');
    
    foreach ($files as $file) {
        $basename = basename($file);
        // Skip index.html and the editor files
        if ($basename === 'index.html' || $basename === 'kowalski.htm' || $basename === 'nowak.php') {
            continue;
        }
        
        // Extract title from file
        $content = file_get_contents($file);
        preg_match('/<title>(.*?)<\/title>/', $content, $titleMatch);
        $title = isset($titleMatch[1]) ? $titleMatch[1] : $basename;
        
        // Extract the main content
        preg_match('/<h1 class=\'title\'>(.*?)<\/h1>/', $content, $h1Match);
        $displayTitle = isset($h1Match[1]) ? $h1Match[1] : $title;
        
        $notes[] = [
            'filename' => $basename,
            'path' => $file,
            'title' => $displayTitle,
            'fullTitle' => $title,
            'modified' => filemtime($file)
        ];
    }
    
    // Sort by filename
    usort($notes, function($a, $b) {
        return strcmp($a['filename'], $b['filename']);
    });
    
    return $notes;
}

// Helper function to extract content from HTML
function extractContent($html) {
    // Extract content between body tags
    preg_match('/<div class=\'page\'>(.*?)<\/div>\s*<\/body>/s', $html, $match);
    if (isset($match[1])) {
        return $match[1];
    }
    return '';
}

// Helper function to create full HTML from content
function createFullHTML($title, $content) {
    return "<!doctype html>
<html>
<head>
  <meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\">
  <title>{$title}</title>
  <meta name=\"generator\" content=\"CherryTree\">
  <link rel=\"stylesheet\" href=\"res/styles4.css\" type=\"text/css\" />
</head>
<body>
<div class='page'>{$content}</div>
</body>
</html>";
}

// Helper function to add entry to index.html
function addToIndex($filename, $displayTitle) {
    global $INDEX_FILE;
    
    if (!file_exists($INDEX_FILE)) {
        return false;
    }
    
    $indexContent = file_get_contents($INDEX_FILE);
    
    // Create the new entry
    $newEntry = "<li class='leaf'><a href='#' onclick=\"changeFrame('$filename')\">$displayTitle</a></li>\n";
    
    // Find the position right before the SECOND closing </ul> (end of Topic Notes subtree)
    // We need to count </ul> tags to find the right one
    // Pattern: find the last </ul> before </ul></div></div>
    
    // Look for the pattern: </ul>\n</ul>\n</div>\n</div>
    // We want to insert before the FIRST </ul> in that sequence
    $pattern = '/(\n<\/ul>\n<\/ul>\n<\/div>\n<\/div>)/';
    
    if (preg_match($pattern, $indexContent)) {
        // Insert the new entry right before the closing </ul></ul></div></div> sequence
        $newIndexContent = preg_replace(
            $pattern,
            "\n" . $newEntry . '$1',
            $indexContent,
            1  // Only replace the first occurrence
        );
        
        // Write back to file
        if (file_put_contents($INDEX_FILE, $newIndexContent)) {
            return true;
        }
    }
    
    return false;
}

// Helper function to remove entry from index.html
function removeFromIndex($filename) {
    global $INDEX_FILE;
    
    if (!file_exists($INDEX_FILE)) {
        return false;
    }
    
    $indexContent = file_get_contents($INDEX_FILE);
    
    // Find and remove the line containing this filename
    // Pattern matches the entire <li> tag with the onclick for this file
    $pattern = '/<li class=\'leaf\'><a href=\'#\' onclick="changeFrame\(\'' . preg_quote($filename, '/') . '\'\)">.*?<\/a><\/li>\n?/';
    
    $newIndexContent = preg_replace($pattern, '', $indexContent);
    
    if ($newIndexContent !== $indexContent) {
        if (file_put_contents($INDEX_FILE, $newIndexContent)) {
            return true;
        }
    }
    
    return false;
}

// Route handling
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    switch ($action) {
        case 'list':
            // List all notes
            if ($method === 'GET') {
                $notes = getAllNotes($NOTES_DIR);
                echo json_encode([
                    'success' => true,
                    'notes' => $notes
                ]);
            }
            break;
            
        case 'read':
            // Read a specific note
            if ($method === 'GET') {
                $filename = isset($_GET['file']) ? $_GET['file'] : '';
                $filepath = $NOTES_DIR . basename($filename);
                
                if (!file_exists($filepath)) {
                    throw new Exception('File not found');
                }
                
                $html = file_get_contents($filepath);
                preg_match('/<title>(.*?)<\/title>/', $html, $titleMatch);
                preg_match('/<h1 class=\'title\'>(.*?)<\/h1>/', $html, $h1Match);
                
                $content = extractContent($html);
                
                echo json_encode([
                    'success' => true,
                    'filename' => basename($filename),
                    'title' => isset($titleMatch[1]) ? $titleMatch[1] : '',
                    'displayTitle' => isset($h1Match[1]) ? $h1Match[1] : '',
                    'content' => $content,
                    'fullHtml' => $html
                ]);
            }
            break;
            
        case 'save':
            // Save a note
            if ($method === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true);
                
                if (!isset($input['filename']) || !isset($input['content'])) {
                    throw new Exception('Missing required fields');
                }
                
                $filename = basename($input['filename']);
                $filepath = $NOTES_DIR . $filename;
                $title = isset($input['title']) ? $input['title'] : '';
                $content = $input['content'];
                
                // Create full HTML
                $html = createFullHTML($title, $content);
                
                // Write file
                $result = file_put_contents($filepath, $html);
                
                if ($result === false) {
                    throw new Exception('Failed to write file');
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'File saved successfully',
                    'filename' => $filename
                ]);
            }
            break;
            
        case 'create':
            // Create a new note
            if ($method === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true);
                
                if (!isset($input['filename']) || !isset($input['title'])) {
                    throw new Exception('Missing required fields');
                }
                
                $filename = basename($input['filename']);
                $filepath = $NOTES_DIR . $filename;
                
                if (file_exists($filepath)) {
                    throw new Exception('File already exists');
                }
                
                $title = $input['title'];
                $content = "<h1 class='title'>{$title}</h1><br/><p></p>";
                
                // Create full HTML
                $html = createFullHTML($title, $content);
                
                // Write file
                $result = file_put_contents($filepath, $html);
                
                if ($result === false) {
                    throw new Exception('Failed to create file');
                }
                
                // Add to index.html
                $indexAdded = addToIndex($filename, $title);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'File created successfully',
                    'filename' => $filename,
                    'indexUpdated' => $indexAdded
                ]);
            }
            break;
            
        case 'delete':
            // Delete a note
            if ($method === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true);
                
                if (!isset($input['filename'])) {
                    throw new Exception('Missing filename');
                }
                
                $filename = basename($input['filename']);
                $filepath = $NOTES_DIR . $filename;
                
                if (!file_exists($filepath)) {
                    throw new Exception('File not found');
                }
                
                if ($filename === 'index.html') {
                    throw new Exception('Cannot delete index file');
                }
                
                $result = unlink($filepath);
                
                if ($result === false) {
                    throw new Exception('Failed to delete file');
                }
                
                // Remove from index.html
                $indexRemoved = removeFromIndex($filename);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'File deleted successfully',
                    'indexUpdated' => $indexRemoved
                ]);
            }
            break;
            
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
