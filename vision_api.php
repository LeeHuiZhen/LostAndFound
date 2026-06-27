<?php
function getVisionTags($text, $image_path = null) {
    $tags = array();

    // 1. Check if Google Vision API key is defined and a valid image path is provided
    if (defined('GOOGLE_VISION_API_KEY') && !empty(GOOGLE_VISION_API_KEY) && $image_path !== null && file_exists($image_path)) {
        // Read the image file and base64-encode it
        $imageData = base64_encode(file_get_contents($image_path));
        
        // Prepare the Google Cloud Vision request payload
        $request_payload = json_encode(array(
            'requests' => array(
                array(
                    'image' => array(
                        'content' => $imageData
                    ),
                    'features' => array(
                        array(
                            'type' => 'LABEL_DETECTION',
                            'maxResults' => 10
                        )
                    )
                )
            )
        ));

        // API Endpoint
        $api_url = 'https://vision.googleapis.com/v1/images:annotate?key=' . GOOGLE_VISION_API_KEY;

        // Perform cURL request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request_payload);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For local XAMPP SSL issues
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 200) {
            $response_data = json_decode($response, true);
            if (isset($response_data['responses'][0]['labelAnnotations'])) {
                foreach ($response_data['responses'][0]['labelAnnotations'] as $annotation) {
                    if (isset($annotation['description'])) {
                        $tags[] = strtolower(trim($annotation['description']));
                    }
                }
            }
        }
    }

    // 2. Local Keyword Extraction Fallback (runs if no API key, or if API fails/returns empty)
    if (empty($tags)) {
        $text = strtolower($text);
        
        // Mapping of keywords to tags
        $tag_rules = array(
            'matric' => array('card', 'matric', 'identity', 'utm'),
            'card' => array('card', 'identity'),
            'id' => array('card', 'identity'),
            'student' => array('identity'),
            
            'phone' => array('electronics', 'phone', 'mobile'),
            'iphone' => array('electronics', 'phone', 'mobile', 'apple'),
            'samsung' => array('electronics', 'phone', 'mobile', 'samsung'),
            
            'laptop' => array('electronics', 'computer', 'laptop'),
            'macbook' => array('electronics', 'computer', 'laptop', 'apple'),
            'computer' => array('electronics', 'computer'),
            
            'headphones' => array('electronics', 'audio', 'headphones'),
            'headphone' => array('electronics', 'audio', 'headphones'),
            'earbuds' => array('electronics', 'audio', 'headphones'),
            'earbud' => array('electronics', 'audio', 'headphones'),
            'airpods' => array('electronics', 'audio', 'headphones', 'apple'),
            'sony' => array('electronics', 'audio', 'sony'),
            
            'keys' => array('keys', 'keychain', 'lanyard'),
            'key' => array('keys', 'keychain'),
            'lanyard' => array('keys', 'keychain', 'lanyard'),
            'keychain' => array('keys', 'keychain'),
            
            'wallet' => array('wallet', 'personal-item'),
            'purse' => array('wallet', 'personal-item'),
            'bag' => array('bag', 'backpack', 'personal-item'),
            'backpack' => array('bag', 'backpack', 'personal-item'),
            'bagpack' => array('bag', 'backpack', 'personal-item'),
            
            'bottle' => array('bottle', 'tumbler', 'personal-item'),
            'flask' => array('bottle', 'tumbler', 'personal-item'),
            'tumbler' => array('bottle', 'tumbler', 'personal-item'),
            
            'book' => array('stationery', 'book', 'education'),
            'notebook' => array('stationery', 'book', 'education'),
            'pen' => array('stationery', 'pen')
        );

        // Scan text for keywords
        foreach ($tag_rules as $keyword => $associated_tags) {
            if (strpos($text, $keyword) !== false) {
                $tags = array_merge($tags, $associated_tags);
            }
        }
    }

    // De-duplicate tags
    $tags = array_unique($tags);

    // Fallback if no tags detected at all
    if (empty($tags)) {
        $tags = array('personal-item', 'campus-item');
    }

    return implode(', ', $tags); 
}
?>
